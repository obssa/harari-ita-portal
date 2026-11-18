<?php

namespace App\Domain\Support\Actions;

use App\Domain\Access\Support\OfficeScope;
use App\Domain\Events\Models\EventRegistration;
use App\Domain\Notifications\Support\Recipients;
use App\Domain\Organisation\Models\Office;
use App\Domain\Organisation\Queries\PublicDirectory;
use App\Domain\Services\Models\ServiceItem;
use App\Domain\Support\Enums\RequesterType;
use App\Domain\Support\Enums\TicketCategory;
use App\Domain\Support\Enums\TicketChannel;
use App\Domain\Support\Enums\TicketPriority;
use App\Domain\Support\Enums\TicketStatus;
use App\Domain\Support\Mail\TicketReceivedMail;
use App\Domain\Support\Models\SlaPolicy;
use App\Domain\Support\Models\Ticket;
use App\Domain\Support\Notifications\NewSupportTicketNotice;
use App\Domain\Support\Sla\WorkingHours;
use App\Models\User;
use App\Services\Sms\SendSms;
use App\Support\PhoneNumber;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Opens a support ticket (ADR-0017): from the public form, or recorded by staff from a call, an email or a visit.
 * The SLA targets start counting in working hours; the requester gets the reference by email and SMS, and the staff
 * who handle the directorate are told.
 */
final class OpenTicket
{
    public function __construct(private readonly StoreAttachments $attachments) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<UploadedFile>  $uploads
     */
    public function handle(array $data, array $uploads, string $locale, ?User $staff = null): Ticket
    {
        $requesterType = RequesterType::tryFrom((string) ($data['requester_type'] ?? ''));
        $category = TicketCategory::tryFrom((string) ($data['category'] ?? ''));
        $name = trim((string) ($data['name'] ?? ''));
        $email = mb_strtolower(trim((string) ($data['email'] ?? '')));
        $phoneTyped = trim((string) ($data['phone'] ?? ''));
        $phone = $phoneTyped !== '' ? PhoneNumber::normalise($phoneTyped) : null;
        $requesterOffice = trim((string) ($data['requester_office'] ?? ''));
        $subject = trim((string) ($data['subject'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));

        $errors = [];
        if ($requesterType === null) {
            $errors['requester_type'] = __('support.validation.requester_type');
        }
        if ($requesterType === RequesterType::PublicOffice && mb_strlen($requesterOffice) < 2) {
            $errors['requester_office'] = __('support.validation.requester_office');
        }
        if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
            $errors['name'] = __('support.validation.name');
        }
        if ($email !== '' && (mb_strlen($email) > 190 || filter_var($email, FILTER_VALIDATE_EMAIL) === false)) {
            $errors['email'] = __('support.validation.email');
        }
        if ($phoneTyped !== '' && $phone === null) {
            $errors['phone'] = __('support.validation.phone');
        }
        if ($email === '' && $phoneTyped === '') {
            $errors['email'] = __('support.validation.contact');
        }
        if ($category === null) {
            $errors['category'] = __('support.validation.category');
        }
        if (mb_strlen($subject) < 3 || mb_strlen($subject) > 200) {
            $errors['subject'] = __('support.validation.subject');
        }
        if (mb_strlen($description) < 10 || mb_strlen($description) > 5000) {
            $errors['description'] = __('support.validation.description');
        }
        if ($staff === null && ! filter_var($data['consent'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $errors['consent'] = __('support.validation.consent');
        }
        if (count($uploads) > StoreAttachments::MAX_FILES) {
            $errors['files'] = __('support.validation.file_count', ['max' => StoreAttachments::MAX_FILES]);
        }
        foreach ($uploads as $upload) {
            if (($problem = $this->attachments->problem($upload)) !== null) {
                $errors['files'] = $problem;
            }
        }

        $service = null;
        if (filled($data['service_item_id'] ?? null)) {
            // ADR-0053: the public form only names a service its reader can see; staff may log against any.
            $services = $staff !== null ? ServiceItem::query() : PublicDirectory::visible(ServiceItem::query(), $locale);
            $service = $services->whereKey((string) $data['service_item_id'])->first();
        }
        $officeId = $service?->office_id;
        $priority = TicketPriority::Normal;
        $channel = TicketChannel::Web;
        if ($staff !== null) {
            $priority = TicketPriority::tryFrom((string) ($data['priority'] ?? '')) ?? TicketPriority::Normal;
            $channel = TicketChannel::tryFrom((string) ($data['channel'] ?? '')) ?? TicketChannel::Phone;
            if (filled($data['office_id'] ?? null) && Office::query()->whereKey((string) $data['office_id'])->exists()) {
                $officeId = (string) $data['office_id'];
            }
            if (! OfficeScope::covers($staff, $officeId, 'tickets.reply')) {
                $errors['office_id'] = __('support.validation.office_scope');
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($staff !== null ? self::prefixed($errors) : $errors);
        }

        $targets = SlaPolicy::targets($category, $priority);
        $now = CarbonImmutable::now();

        $ticket = DB::transaction(function () use ($locale, $requesterType, $requesterOffice, $name, $email, $phone, $officeId, $service, $category, $priority, $channel, $subject, $description, $targets, $now, $uploads): Ticket {
            $ticket = new Ticket([
                'reference' => Ticket::newReference(),
                'locale' => $locale,
                'requester_type' => $requesterType,
                'requester_office' => $requesterType === RequesterType::PublicOffice ? mb_substr($requesterOffice, 0, 200) : null,
                'name' => $name,
                'email' => $email !== '' ? $email : null,
                'phone' => $phone,
                // ADR-0041 of the source project: keyed hashes so a privacy request can find a person's tickets.
                'email_hash' => $email !== '' ? EventRegistration::hash($email) : null,
                'phone_hash' => $phone !== null ? EventRegistration::hash($phone) : null,
                'office_id' => $officeId,
                'service_item_id' => $service?->id,
                'category' => $category,
                'priority' => $priority,
                'status' => TicketStatus::New,
                'channel' => $channel,
                'subject' => $subject,
                'description' => $description,
                'response_due_at' => WorkingHours::add($now, $targets['response']),
                'resolve_due_at' => WorkingHours::add($now, $targets['resolve']),
            ]);
            $ticket->save();

            $files = $this->attachments->store($ticket->id, $uploads);
            if ($files !== []) {
                $ticket->files = $files;
                $ticket->save();
            }

            return $ticket;
        });

        if ($ticket->email !== null) {
            Mail::to($ticket->email)->locale($locale)->queue(new TicketReceivedMail($ticket));
        }
        if ($ticket->phone !== null) {
            SendSms::dispatch($ticket->phone, __('support.sms.received', ['reference' => $ticket->reference], $locale));
        }
        Recipients::notify(
            Recipients::withPermission('tickets.view')->filter(fn (User $user): bool => OfficeScope::covers($user, $ticket->office_id, 'tickets.view')),
            NewSupportTicketNotice::for($ticket),
            except: $staff,
        );

        return $ticket;
    }

    /**
     * Staff forms name their fields under `data.`.
     *
     * @param  array<string, string>  $errors
     * @return array<string, string>
     */
    private static function prefixed(array $errors): array
    {
        $prefixed = [];
        foreach ($errors as $field => $message) {
            $prefixed["data.{$field}"] = $message;
        }

        return $prefixed;
    }
}
