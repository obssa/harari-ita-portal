<?php

namespace App\Http\Controllers\Public;

use App\Domain\Content\Models\Category;
use App\Domain\Events\Models\EventRegistration;
use App\Domain\Innovation\Actions\SubmitStartup;
use App\Domain\Innovation\Models\Startup;
use App\Http\Controllers\Controller;
use App\Support\PhoneNumber;
use App\Support\SpamGuard;
use App\Support\Turnstile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Founders ask to be listed and follow the review (ADR-0020), with the reference plus the email or phone they gave
 * (ADR-0003); the sender's own browser opens it straight after sending.
 */
final class StartupListingController extends Controller
{
    private const SESSION_KEY = 'startups.verified';

    public function create(Turnstile $turnstile): View
    {
        $locale = app()->getLocale();

        return view('pages.startups.apply', [
            'turnstileKey' => $turnstile->siteKey(),
            'sectors' => Category::query()->where('type', Category::TYPE_SECTOR)->with('translations')->orderBy('sort')->get()
                ->mapWithKeys(fn (Category $c): array => [$c->id => $c->name($locale)])->filter()->all(),
        ]);
    }

    public function store(Request $request, SubmitStartup $submit, Turnstile $turnstile): RedirectResponse
    {
        SpamGuard::check($request);
        $turnstile->check($request);

        // The form calls the website "web_address": "website" is the spam guard's honeypot (App\Support\SpamGuard).
        $startup = $submit->handle([
            ...$request->only([
                'name', 'summary', 'products', 'stage', 'sector_id', 'founded_year', 'team_size', 'area',
                'label_claimed', 'founder_name', 'founder_email', 'founder_phone', 'consent',
            ]),
            'website' => $request->input('web_address'),
        ], app()->getLocale());

        $this->remember($request, $startup);

        return redirect()->route('startups.listing.show', ['reference' => $startup->reference])->with('status', __('innovation.public.received'));
    }

    public function lookup(Request $request): View
    {
        return view('pages.startups.lookup', ['reference' => Startup::normaliseReference((string) $request->query('reference'))]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $reference = Startup::normaliseReference((string) $request->input('reference'));
        $contact = trim((string) $request->input('contact'));

        $errors = [];
        if ($reference === '') {
            $errors['reference'] = __('innovation.track.reference_invalid');
        }
        if ($contact === '') {
            $errors['contact'] = __('support.track.contact_required');
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $startup = Startup::query()->where('reference', $reference)->first();
        if ($startup === null) {
            throw ValidationException::withMessages(['reference' => __('support.track.not_found')]);
        }
        $matches = str_contains($contact, '@')
            ? $startup->email_hash !== null && hash_equals($startup->email_hash, EventRegistration::hash(mb_strtolower($contact)))
            : ($phone = PhoneNumber::normalise($contact)) !== null && $startup->phone_hash !== null && hash_equals($startup->phone_hash, EventRegistration::hash($phone));
        if (! $matches) {
            throw ValidationException::withMessages(['contact' => __('support.track.contact_mismatch')]);
        }

        $this->remember($request, $startup);

        return redirect()->route('startups.listing.show', ['reference' => $startup->reference]);
    }

    public function show(Request $request, string $reference): View
    {
        $startup = Startup::query()->where('reference', $reference)->first();
        if ($startup === null || ! in_array($startup->id, (array) $request->session()->get(self::SESSION_KEY, []), true)) {
            return view('pages.startups.lookup', ['reference' => $reference]);
        }

        return view('pages.startups.listing', ['startup' => $startup]);
    }

    private function remember(Request $request, Startup $startup): void
    {
        $ids = (array) $request->session()->get(self::SESSION_KEY, []);
        $ids[] = $startup->id;
        $request->session()->put(self::SESSION_KEY, array_slice(array_values(array_unique($ids)), -20));
    }
}
