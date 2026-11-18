<?php

namespace App\Http\Controllers\Public;

use App\Domain\Events\Models\EventRegistration;
use App\Domain\Organisation\Queries\PublicDirectory;
use App\Domain\Services\Models\ServiceItem;
use App\Domain\Services\Models\ServiceItemTranslation;
use App\Domain\Support\Actions\OpenTicket;
use App\Domain\Support\Actions\RequesterReply;
use App\Domain\Support\Actions\StoreAttachments;
use App\Domain\Support\Models\Ticket;
use App\Http\Controllers\Controller;
use App\Support\PhoneNumber;
use App\Support\SpamGuard;
use App\Support\Turnstile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Request IT support and follow it (PLAN.md §4; ADR-0017). A request is followed with its reference plus the
 * email address or phone number given with it (ADR-0003, the status-check rule): the page says which of the two is
 * wrong and never shows anything from one alone. Once proved, the session remembers the ticket.
 */
final class SupportController extends Controller
{
    private const SESSION_KEY = 'support.verified';

    public function create(Request $request, Turnstile $turnstile): View
    {
        $locale = app()->getLocale();

        // ADR-0053: arriving from a service page, the form is about that service.
        $requested = null;
        $slug = $request->query('service');
        if (is_string($slug) && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) === 1) {
            /** @var ServiceItemTranslation|null $translation */
            $translation = ServiceItemTranslation::query()->where('locale', $locale)->where('slug', $slug)->first();
            $service = $translation?->serviceItem;
            if ($translation !== null && $service !== null && $translation->isLive() && $service->accepts_requests) {
                $requested = ['id' => $service->id, 'title' => $translation->title, 'url' => route('services.show', ['slug' => $translation->slug])];
            }
        }

        return view('pages.support.create', [
            'requested' => $requested,
            'turnstileKey' => $turnstile->siteKey(),
            'services' => PublicDirectory::visible(ServiceItem::query(), $locale)->orderBy('rank')->get()
                ->mapWithKeys(fn (ServiceItem $service): array => [$service->id => (string) PublicDirectory::shown($service, $locale)?->getAttribute('title')])
                ->filter()
                ->all(),
        ]);
    }

    public function store(Request $request, OpenTicket $open, Turnstile $turnstile): RedirectResponse
    {
        SpamGuard::check($request);
        $turnstile->check($request);

        $ticket = $open->handle([
            'requester_type' => $request->input('requester_type'),
            'requester_office' => $request->input('requester_office'),
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'category' => $request->input('category'),
            'service_item_id' => $request->input('service_item_id'),
            'subject' => $request->input('subject'),
            'description' => $request->input('description'),
            'consent' => $request->boolean('consent'),
        ], self::uploads($request), app()->getLocale());

        $this->remember($request, $ticket);

        return redirect()->route('support.track', ['reference' => $ticket->reference])->with('status', __('support.public.received'));
    }

    public function lookup(Request $request): View
    {
        return view('pages.support.lookup', ['reference' => Ticket::normaliseReference((string) $request->query('reference'))]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $reference = Ticket::normaliseReference((string) $request->input('reference'));
        $contact = trim((string) $request->input('contact'));

        $errors = [];
        if ($reference === '') {
            $errors['reference'] = __('support.track.reference_invalid');
        }
        if ($contact === '') {
            $errors['contact'] = __('support.track.contact_required');
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $ticket = Ticket::query()->where('reference', $reference)->whereNull('erased_at')->first();
        if ($ticket === null) {
            throw ValidationException::withMessages(['reference' => __('support.track.not_found')]);
        }
        if (! self::matches($ticket, $contact)) {
            throw ValidationException::withMessages(['contact' => __('support.track.contact_mismatch')]);
        }

        $this->remember($request, $ticket);

        return redirect()->route('support.track', ['reference' => $ticket->reference]);
    }

    public function show(Request $request, string $reference): View
    {
        $ticket = $this->proved($request, $reference);
        if ($ticket === null) {
            return view('pages.support.lookup', ['reference' => $reference]);
        }

        return view('pages.support.show', [
            'ticket' => $ticket,
            'messages' => $ticket->messages()->where('internal', false)->get(),
            'service' => $ticket->serviceItem !== null ? PublicDirectory::shown($ticket->serviceItem, app()->getLocale()) : null,
        ]);
    }

    public function reply(Request $request, string $reference, RequesterReply $reply): RedirectResponse
    {
        $ticket = $this->proved($request, $reference);
        abort_if($ticket === null, 403);
        SpamGuard::check($request);

        $reply->message($ticket, (string) $request->input('body'), self::uploads($request));

        return redirect()->route('support.track', ['reference' => $ticket->reference])->with('status', __('support.public.replied'));
    }

    public function rate(Request $request, string $reference, RequesterReply $reply): RedirectResponse
    {
        $ticket = $this->proved($request, $reference);
        abort_if($ticket === null, 403);

        $reply->rate($ticket, (int) $request->input('satisfaction'), $request->input('note'));

        return redirect()->route('support.track', ['reference' => $ticket->reference])->with('status', __('support.public.rated'));
    }

    /**
     * An attachment of the request or of a reply the requester can see: "t0" … or "m{id}-{n}".
     */
    public function file(Request $request, string $reference, string $key): StreamedResponse
    {
        $ticket = $this->proved($request, $reference);
        abort_if($ticket === null, 403);

        $file = null;
        if (preg_match('/^t(\d)$/', $key, $m) === 1) {
            $file = ($ticket->files ?? [])[(int) $m[1]] ?? null;
        } elseif (preg_match('/^m(\d+)-(\d)$/', $key, $m) === 1) {
            $message = $ticket->messages()->where('internal', false)->whereKey((int) $m[1])->first();
            $file = ($message->files ?? [])[(int) $m[2]] ?? null;
        }
        abort_if($file === null || ! Storage::disk(StoreAttachments::DISK)->exists($file['path']), 404);

        return Storage::disk(StoreAttachments::DISK)->download($file['path'], $file['name'], [
            'Content-Type' => 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function proved(Request $request, string $reference): ?Ticket
    {
        $ids = (array) $request->session()->get(self::SESSION_KEY, []);
        $ticket = Ticket::query()->where('reference', $reference)->whereNull('erased_at')->first();

        return $ticket !== null && in_array($ticket->id, $ids, true) ? $ticket : null;
    }

    private function remember(Request $request, Ticket $ticket): void
    {
        $ids = (array) $request->session()->get(self::SESSION_KEY, []);
        $ids[] = $ticket->id;
        $request->session()->put(self::SESSION_KEY, array_slice(array_values(array_unique($ids)), -20));
    }

    /** The email address or phone number given with the ticket, compared by its keyed hash. */
    private static function matches(Ticket $ticket, string $contact): bool
    {
        if (str_contains($contact, '@')) {
            return $ticket->email_hash !== null && hash_equals($ticket->email_hash, EventRegistration::hash(mb_strtolower($contact)));
        }

        $phone = PhoneNumber::normalise($contact);

        return $phone !== null && $ticket->phone_hash !== null && hash_equals($ticket->phone_hash, EventRegistration::hash($phone));
    }

    /**
     * @return list<UploadedFile>
     */
    private static function uploads(Request $request): array
    {
        return array_values(array_filter((array) $request->file('files', []), fn (mixed $file): bool => $file instanceof UploadedFile));
    }
}
