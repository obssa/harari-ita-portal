<?php

namespace App\Http\Controllers\Public;

use App\Domain\Events\Models\EventRegistration;
use App\Domain\Forms\Actions\SubmitForm;
use App\Domain\Forms\Models\Form;
use App\Domain\Forms\Models\FormSubmission;
use App\Domain\Forms\Models\FormTranslation;
use App\Domain\Forms\Support\SubmissionAbout;
use App\Domain\Organisation\Enums\OfficeType;
use App\Domain\Organisation\Models\Office;
use App\Domain\Organisation\Queries\PublicDirectory;
use App\Http\Controllers\Controller;
use App\Settings\SiteSettings;
use App\Support\PhoneNumber;
use App\Support\SpamGuard;
use App\Support\Turnstile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public forms, the contact page and ticket status (PLAN.md §4 Contact and
 * Get involved, ADR-0028). These pages keep a session for the form
 * protection and are never cached.
 */
final class FormController extends Controller
{
    private const SESSION_KEY = 'forms.verified';

    public function show(Request $request, string $slug, Turnstile $turnstile): View
    {
        [$form, $translation] = $this->find($slug);
        $token = $request->query('about');

        return view('pages.forms.show', [
            'about' => $about = SubmissionAbout::accept(is_string($token) ? $token : null, $form, app()->getLocale()),
            'closed' => $about === null && SubmissionAbout::requiresOpportunity($form),
            'form' => $form,
            'translation' => $translation,
            'turnstileKey' => $turnstile->siteKey(),
            'alternates' => PublicDirectory::alternates($form, 'forms.show', 'contact'),
        ]);
    }

    public function submit(Request $request, string $slug, SubmitForm $submit, Turnstile $turnstile): RedirectResponse
    {
        [$form, $translation] = $this->find($slug);

        SpamGuard::check($request);
        $turnstile->check($request);

        $uploads = array_filter((array) $request->file('answers', []), fn (mixed $file): bool => $file instanceof UploadedFile);
        $submission = $submit->handle($form, [
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'answers' => (array) $request->input('answers', []),
            'about' => $request->input('about'),
        ], $uploads, app()->getLocale());

        self::remember($request, $submission);

        return redirect()->route('tickets.show', ['reference' => $submission->reference])
            ->with('status', filled($translation->success_message) ? $translation->success_message : __('forms.sent'));
    }

    public function contact(SiteSettings $settings, Turnstile $turnstile): View
    {
        $locale = app()->getLocale();
        $form = Form::query()->where('on_contact_page', true)->where('active', true)->with('translations')->first();
        $translation = $form !== null ? PublicDirectory::shown($form, $locale) : null;

        $offices = PublicDirectory::visible(Office::query(), $locale)
            ->where(fn ($q) => $q->whereNotNull('phone')->orWhereNotNull('email'))
            ->whereIn('type', OfficeType::reachableValues())
            ->with('translations')
            ->orderByRaw('type = ? desc', [OfficeType::Directorate->value])->orderBy('sort')
            ->limit(30)->get();

        return view('pages.forms.contact', [
            'settings' => $settings,
            'offices' => $offices,
            'form' => $translation instanceof FormTranslation ? $form : null,
            'translation' => $translation instanceof FormTranslation ? $translation : null,
            'turnstileKey' => $turnstile->siteKey(),
        ]);
    }

    /**
     * ADR-0003 (the status-check rule): a request is followed with its reference plus the email or phone given with
     * it; the form says which one is wrong and shows nothing from either alone. The sender's own browser remembers
     * what it sent, so the page opens straight after sending (also for forms that ask for no contact).
     */
    public function lookup(Request $request): View
    {
        return view('pages.forms.lookup', ['typed' => FormSubmission::normaliseReference((string) $request->query('reference', ''))]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $reference = FormSubmission::normaliseReference((string) $request->input('reference'));
        $contact = trim((string) $request->input('contact'));

        $errors = [];
        if ($reference === '') {
            $errors['reference'] = __('forms.lookup_not_found');
        }
        if ($contact === '') {
            $errors['contact'] = __('forms.lookup_contact_required');
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $submission = FormSubmission::query()->where('reference', $reference)->whereNull('erased_at')->first();
        if ($submission === null) {
            throw ValidationException::withMessages(['reference' => __('forms.lookup_not_found')]);
        }
        if ($submission->email_hash === null && $submission->phone_hash === null) {
            throw ValidationException::withMessages(['contact' => __('forms.lookup_no_contact')]);
        }
        $matches = str_contains($contact, '@')
            ? $submission->email_hash !== null && hash_equals($submission->email_hash, EventRegistration::hash(mb_strtolower($contact)))
            : ($phone = PhoneNumber::normalise($contact)) !== null && $submission->phone_hash !== null && hash_equals($submission->phone_hash, EventRegistration::hash($phone));
        if (! $matches) {
            throw ValidationException::withMessages(['contact' => __('forms.lookup_contact_mismatch')]);
        }

        self::remember($request, $submission);

        return redirect()->route('tickets.show', ['reference' => $submission->reference]);
    }

    public function ticket(Request $request, string $reference): Response
    {
        $submission = FormSubmission::query()->where('reference', $reference)->whereNull('erased_at')->with('form.translations')->first();
        if ($submission === null || ! in_array($submission->id, (array) $request->session()->get(self::SESSION_KEY, []), true)) {
            return response()->view('pages.forms.lookup', ['typed' => $reference])
                ->header('Cache-Control', 'private, no-store')->header('X-Robots-Tag', 'noindex, nofollow');
        }
        $translation = PublicDirectory::shown($submission->form, app()->getLocale());

        return response()->view('pages.forms.ticket', [
            'submission' => $submission,
            'title' => $translation instanceof FormTranslation ? $translation->title : $submission->form->title(),
        ])->header('Cache-Control', 'private, no-store')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    private static function remember(Request $request, FormSubmission $submission): void
    {
        $ids = (array) $request->session()->get(self::SESSION_KEY, []);
        $ids[] = $submission->id;
        $request->session()->put(self::SESSION_KEY, array_slice(array_values(array_unique($ids)), -20));
    }

    /**
     * @return array{0: Form, 1: FormTranslation}
     */
    private function find(string $slug): array
    {
        /** @var FormTranslation|null $translation */
        $translation = FormTranslation::query()->where('locale', app()->getLocale())->where('slug', $slug)->first();
        $form = $translation?->form()->with('translations', 'office')->first();
        abort_if($translation === null || $form === null || ! $form->active || ! $translation->isLive(), 404);

        return [$form, $translation];
    }
}
