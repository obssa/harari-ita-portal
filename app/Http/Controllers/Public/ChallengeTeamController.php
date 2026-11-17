<?php

namespace App\Http\Controllers\Public;

use App\Domain\Events\Models\EventRegistration;
use App\Domain\Innovation\Actions\ChallengeEntry;
use App\Domain\Innovation\Models\Challenge;
use App\Domain\Innovation\Models\ChallengeTeam;
use App\Domain\Innovation\Models\ChallengeTranslation;
use App\Http\Controllers\Controller;
use App\Support\PhoneNumber;
use App\Support\SpamGuard;
use App\Support\Turnstile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Registering a team and sending its submission (ADR-0022). The team page opens with the team reference plus the
 * contact's email or phone (ADR-0003); the contact's own browser opens it straight after registering.
 */
final class ChallengeTeamController extends Controller
{
    private const SESSION_KEY = 'teams.verified';

    public function create(string $slug, Turnstile $turnstile): View
    {
        [$challenge, $translation] = $this->challenge($slug);

        return view('pages.challenges.register', [
            'challenge' => $challenge,
            'translation' => $translation,
            'open' => $challenge->phase() === Challenge::PHASE_REGISTRATION,
            'turnstileKey' => $turnstile->siteKey(),
        ]);
    }

    public function store(Request $request, string $slug, ChallengeEntry $entry, Turnstile $turnstile): RedirectResponse
    {
        [$challenge] = $this->challenge($slug);
        SpamGuard::check($request);
        $turnstile->check($request);

        $team = $entry->register($challenge, $request->only(['name', 'contact_name', 'contact_email', 'contact_phone', 'members', 'show_in_results', 'consent']), app()->getLocale());
        $this->remember($request, $team);

        return redirect()->route('teams.show', ['reference' => $team->reference])->with('status', __('innovation.public.team_registered'));
    }

    public function lookup(Request $request): View
    {
        return view('pages.challenges.lookup', ['reference' => ChallengeTeam::normaliseReference((string) $request->query('reference'))]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $reference = ChallengeTeam::normaliseReference((string) $request->input('reference'));
        $contact = trim((string) $request->input('contact'));

        $errors = [];
        if ($reference === '') {
            $errors['reference'] = __('innovation.track.team_reference_invalid');
        }
        if ($contact === '') {
            $errors['contact'] = __('support.track.contact_required');
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $team = ChallengeTeam::query()->where('reference', $reference)->whereNull('erased_at')->first();
        if ($team === null) {
            throw ValidationException::withMessages(['reference' => __('support.track.not_found')]);
        }
        $matches = str_contains($contact, '@')
            ? $team->email_hash !== null && hash_equals($team->email_hash, EventRegistration::hash(mb_strtolower($contact)))
            : ($phone = PhoneNumber::normalise($contact)) !== null && $team->phone_hash !== null && hash_equals($team->phone_hash, EventRegistration::hash($phone));
        if (! $matches) {
            throw ValidationException::withMessages(['contact' => __('support.track.contact_mismatch')]);
        }

        $this->remember($request, $team);

        return redirect()->route('teams.show', ['reference' => $team->reference]);
    }

    public function show(Request $request, string $reference): View
    {
        $team = $this->proved($request, $reference);
        if ($team === null) {
            return view('pages.challenges.lookup', ['reference' => $reference]);
        }

        return view('pages.challenges.team', ['team' => $team, 'challenge' => $team->challenge, 'phase' => $team->challenge->phase()]);
    }

    public function submit(Request $request, string $reference, ChallengeEntry $entry): RedirectResponse
    {
        $team = $this->proved($request, $reference);
        abort_if($team === null, 403);
        $entry->submit($team, $request->only(['submission_title', 'submission_description', 'submission_links']));

        return redirect()->route('teams.show', ['reference' => $team->reference])->with('status', __('innovation.public.submission_saved'));
    }

    /**
     * @return array{0: Challenge, 1: ChallengeTranslation}
     */
    private function challenge(string $slug): array
    {
        /** @var ChallengeTranslation|null $translation */
        $translation = ChallengeTranslation::query()->where('locale', app()->getLocale())->where('slug', $slug)->first();
        $challenge = $translation?->challenge()->with('translations')->first();
        abort_if($translation === null || $challenge === null || ! $translation->isLive(), 404);

        return [$challenge, $translation];
    }

    private function proved(Request $request, string $reference): ?ChallengeTeam
    {
        $team = ChallengeTeam::query()->where('reference', $reference)->whereNull('erased_at')->with('challenge.translations')->first();

        return $team !== null && in_array($team->id, (array) $request->session()->get(self::SESSION_KEY, []), true) ? $team : null;
    }

    private function remember(Request $request, ChallengeTeam $team): void
    {
        $ids = (array) $request->session()->get(self::SESSION_KEY, []);
        $ids[] = $team->id;
        $request->session()->put(self::SESSION_KEY, array_slice(array_values(array_unique($ids)), -20));
    }
}
