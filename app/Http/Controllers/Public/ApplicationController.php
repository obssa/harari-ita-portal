<?php

namespace App\Http\Controllers\Public;

use App\Domain\Events\Models\EventRegistration;
use App\Domain\Innovation\Actions\ReviewApplication;
use App\Domain\Innovation\Actions\SubmitApplication;
use App\Domain\Innovation\Models\Application;
use App\Domain\Innovation\Models\Programme;
use App\Domain\Innovation\Models\ProgrammeTranslation;
use App\Http\Controllers\Controller;
use App\Support\PhoneNumber;
use App\Support\SpamGuard;
use App\Support\Turnstile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Applying to a programme's open cohort and following the application (ADR-0021): the reference plus the email or
 * phone given (ADR-0003); the applicant's own browser opens it straight after sending, and they may withdraw it
 * while it is still being reviewed.
 */
final class ApplicationController extends Controller
{
    private const SESSION_KEY = 'applications.verified';

    public function create(string $slug, Turnstile $turnstile): View
    {
        [$programme, $translation] = $this->programme($slug);

        return view('pages.programmes.apply', [
            'programme' => $programme,
            'translation' => $translation,
            'cohort' => $programme->openCohort(),
            'questions' => $programme->questionsIn(app()->getLocale()),
            'turnstileKey' => $turnstile->siteKey(),
        ]);
    }

    public function store(Request $request, string $slug, SubmitApplication $submit, Turnstile $turnstile): RedirectResponse
    {
        [$programme] = $this->programme($slug);
        SpamGuard::check($request);
        $turnstile->check($request);

        $application = $submit->handle($programme, $request->only(['applicant_name', 'applicant_email', 'applicant_phone', 'team_name', 'answers', 'consent']), app()->getLocale());
        $this->remember($request, $application);

        return redirect()->route('applications.show', ['reference' => $application->reference])->with('status', __('innovation.public.application_received'));
    }

    public function lookup(Request $request): View
    {
        return view('pages.programmes.lookup', ['reference' => Application::normaliseReference((string) $request->query('reference'))]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $reference = Application::normaliseReference((string) $request->input('reference'));
        $contact = trim((string) $request->input('contact'));

        $errors = [];
        if ($reference === '') {
            $errors['reference'] = __('innovation.track.application_reference_invalid');
        }
        if ($contact === '') {
            $errors['contact'] = __('support.track.contact_required');
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $application = Application::query()->where('reference', $reference)->whereNull('erased_at')->first();
        if ($application === null) {
            throw ValidationException::withMessages(['reference' => __('support.track.not_found')]);
        }
        $matches = str_contains($contact, '@')
            ? $application->email_hash !== null && hash_equals($application->email_hash, EventRegistration::hash(mb_strtolower($contact)))
            : ($phone = PhoneNumber::normalise($contact)) !== null && $application->phone_hash !== null && hash_equals($application->phone_hash, EventRegistration::hash($phone));
        if (! $matches) {
            throw ValidationException::withMessages(['contact' => __('support.track.contact_mismatch')]);
        }

        $this->remember($request, $application);

        return redirect()->route('applications.show', ['reference' => $application->reference]);
    }

    public function show(Request $request, string $reference): View
    {
        $application = $this->proved($request, $reference);
        if ($application === null) {
            return view('pages.programmes.lookup', ['reference' => $reference]);
        }

        return view('pages.programmes.application', ['application' => $application]);
    }

    public function withdraw(Request $request, string $reference, ReviewApplication $review): RedirectResponse
    {
        $application = $this->proved($request, $reference);
        abort_if($application === null, 403);
        $review->withdraw($application);

        return redirect()->route('applications.show', ['reference' => $application->reference])->with('status', __('innovation.public.withdrawn'));
    }

    /**
     * @return array{0: Programme, 1: ProgrammeTranslation}
     */
    private function programme(string $slug): array
    {
        /** @var ProgrammeTranslation|null $translation */
        $translation = ProgrammeTranslation::query()->where('locale', app()->getLocale())->where('slug', $slug)->first();
        $programme = $translation?->programme()->with(['translations', 'cohorts'])->first();
        abort_if($translation === null || $programme === null || ! $translation->isLive(), 404);

        return [$programme, $translation];
    }

    private function proved(Request $request, string $reference): ?Application
    {
        $application = Application::query()->where('reference', $reference)->whereNull('erased_at')->with(['programme.translations', 'cohort'])->first();

        return $application !== null && in_array($application->id, (array) $request->session()->get(self::SESSION_KEY, []), true) ? $application : null;
    }

    private function remember(Request $request, Application $application): void
    {
        $ids = (array) $request->session()->get(self::SESSION_KEY, []);
        $ids[] = $application->id;
        $request->session()->put(self::SESSION_KEY, array_slice(array_values(array_unique($ids)), -20));
    }
}
