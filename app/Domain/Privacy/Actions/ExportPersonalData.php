<?php

namespace App\Domain\Privacy\Actions;

use App\Domain\Academy\Models\Certificate;
use App\Domain\Academy\Models\Enrolment;
use App\Domain\Engagement\Models\Subscriber;
use App\Domain\Events\Models\EventRegistration;
use App\Domain\Forms\Models\FormSubmission;
use App\Domain\Forms\Models\SubmissionNote;
use App\Domain\Innovation\Models\Application;
use App\Domain\Innovation\Models\ChallengeTeam;
use App\Domain\Innovation\Models\Startup;
use App\Domain\People\Models\PersonNote;
use App\Domain\Press\Models\MediaContact;
use App\Domain\Privacy\Models\PrivacyRequest;
use App\Domain\Privacy\Support\PersonalDataSearch;
use App\Domain\Support\Models\Ticket;
use App\Domain\Support\Models\TicketMessage;
use App\Domain\Volunteers\Models\Volunteer;
use App\Domain\Volunteers\Models\VolunteerAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Activitylog\Models\Activity;

/**
 * A copy of what the portal holds about a person, as JSON for them
 * (PLAN.md §11 data export, ADR-0041). Uploaded attachments are listed by
 * name; the request is logged without the person's details.
 */
final class ExportPersonalData
{
    /**
     * @return array<string, mixed>
     */
    public function handle(PersonalDataSearch $search, User $actor): array
    {
        $registrations = $search->registrations();
        $submissions = $search->submissions();
        $tickets = $search->tickets();
        $applications = $search->applications();
        $enrolments = $search->enrolments();
        $startups = $search->startups();
        $teams = $search->teams();
        $failed = $search->failedSignIns();
        $volunteers = $search->volunteers();
        $subscribers = $search->subscribers();
        $mediaContacts = $search->mediaContacts();

        $export = [
            'exported_at' => now()->toIso8601String(),
            'searched_for' => array_filter(['email' => $search->email, 'phone' => $search->phone]),
            'event_registrations' => $registrations->map(fn (EventRegistration $r): array => [
                'event' => $r->event->title(),
                'event_starts_at' => $r->event->starts_at->toIso8601String(),
                'name' => $r->name,
                'email' => $r->email,
                'phone' => $r->phone,
                'language' => $r->locale,
                'status' => $r->status->value,
                'registered_at' => $r->created_at->toIso8601String(),
                'checked_in_at' => $r->checked_in_at?->toIso8601String(),
                'cancelled_at' => $r->cancelled_at?->toIso8601String(),
            ])->values()->all(),
            'messages_and_tickets' => $submissions->map(fn (FormSubmission $s): array => [
                'reference' => $s->reference,
                'form' => $s->form->title(),
                'sent_at' => $s->created_at->toIso8601String(),
                'status' => $s->status->value,
                'name' => $s->name,
                'email' => $s->email,
                'phone' => $s->phone,
                'answers' => $s->answers,
                'attachments' => array_map(fn (array $file): string => $file['name'], $s->files ?? []),
                'history' => $s->notes
                    ->filter(fn (SubmissionNote $n): bool => in_array($n->kind, [SubmissionNote::REPLY, SubmissionNote::NOTE], true))
                    ->map(fn (SubmissionNote $n): array => ['kind' => $n->kind, 'at' => $n->created_at->toIso8601String(), 'text' => $n->body])
                    ->values()->all(),
            ])->values()->all(),
            'support_tickets' => $tickets->map(fn (Ticket $t): array => [
                'reference' => $t->reference,
                'sent_at' => $t->created_at->toIso8601String(),
                'status' => $t->status->value,
                'requester_type' => $t->requester_type->value,
                'requester_office' => $t->requester_office,
                'name' => $t->name,
                'email' => $t->email,
                'phone' => $t->phone,
                'subject' => $t->subject,
                'description' => $t->description,
                'attachments' => array_map(fn (array $file): string => $file['name'], $t->files ?? []),
                'messages' => $t->messages
                    ->reject(fn (TicketMessage $m): bool => $m->internal)
                    ->map(fn (TicketMessage $m): array => ['from' => $m->from_requester ? 'you' : 'service_desk', 'at' => $m->created_at->toIso8601String(), 'text' => $m->body])
                    ->values()->all(),
                'rating' => $t->satisfaction,
                'rating_note' => $t->satisfaction_note,
            ])->values()->all(),
            'programme_applications' => $applications->map(fn (Application $a): array => [
                'reference' => $a->reference,
                'programme' => $a->programme->title(),
                'sent_at' => $a->created_at->toIso8601String(),
                'stage' => $a->stage->value,
                'name' => $a->applicant_name,
                'email' => $a->applicant_email,
                'phone' => $a->applicant_phone,
                'team' => $a->team_name,
                'answers' => $a->answers,
                'decision_note' => $a->decision_note,
                'history' => self::history($a->notes),
            ])->values()->all(),
            'academy_enrolments' => $enrolments->map(fn (Enrolment $e): array => [
                'course' => $e->cohort->course->title(),
                'cohort' => $e->cohort->name,
                'enrolled_at' => $e->created_at->toIso8601String(),
                'status' => $e->status->value,
                'name' => $e->name,
                'email' => $e->email,
                'phone' => $e->phone,
                'sessions_attended' => $e->attendances()->count(),
                'certificates' => $e->certificates->map(fn (Certificate $c): array => [
                    'code' => $c->code, 'issued_at' => $c->issued_at->toIso8601String(), 'revoked' => $c->isRevoked(),
                ])->values()->all(),
            ])->values()->all(),
            'challenge_teams' => $teams->map(fn (ChallengeTeam $t): array => [
                'reference' => $t->reference,
                'challenge' => $t->challenge->title(),
                'registered_at' => $t->created_at->toIso8601String(),
                'team' => $t->name,
                'contact_name' => $t->contact_name,
                'contact_email' => $t->contact_email,
                'contact_phone' => $t->contact_phone,
                'members' => $t->members,
                'submission_title' => $t->submission_title,
                'submission_description' => $t->submission_description,
                'submission_links' => $t->submission_links,
                'submitted_at' => $t->submitted_at?->toIso8601String(),
                'rank' => $t->rank,
                'history' => self::history($t->notes),
            ])->values()->all(),
            'startup_listings' => $startups->map(fn (Startup $s): array => [
                'reference' => $s->reference,
                'startup' => $s->name(),
                'sent_at' => $s->created_at->toIso8601String(),
                'listing' => $s->listing_status->value,
                'founder_name' => $s->founder_name,
                'founder_email' => $s->founder_email,
                'founder_phone' => $s->founder_phone,
            ])->values()->all(),
            'volunteering' => $volunteers->map(fn (Volunteer $v): array => [
                'signed_up_at' => $v->created_at->toIso8601String(),
                'status' => $v->status->value,
                'name' => $v->name,
                'phone' => $v->phone,
                'email' => $v->email,
                'office' => $v->office?->name(),
                'skills' => $v->skills,
                'availability' => $v->availability,
                'languages' => $v->languages,
                'message' => $v->message,
                'assignments' => $v->assignments->map(fn (VolunteerAssignment $a): array => [
                    'task' => $a->task, 'event' => $a->event?->title(), 'on' => $a->on_date?->toDateString(), 'status' => $a->status->value, 'hours' => $a->hours,
                ])->values()->all(),
                'history' => self::history($v->notes),
            ])->values()->all(),
            'newsletter' => $subscribers->map(fn (Subscriber $s): array => [
                'email' => $s->email,
                'phone' => $s->phone,
                'channels' => $s->channels,
                'topics' => $s->topics,
                'language' => $s->locale,
                'signed_up_at' => $s->created_at->toIso8601String(),
                'email_confirmed_at' => $s->email_confirmed_at?->toIso8601String(),
                'phone_confirmed_at' => $s->phone_confirmed_at?->toIso8601String(),
                'unsubscribed_at' => $s->unsubscribed_at?->toIso8601String(),
            ])->values()->all(),
            'media_contact' => $mediaContacts->map(fn (MediaContact $c): array => [
                'name' => $c->name, 'outlet' => $c->outlet, 'position' => $c->position, 'email' => $c->email, 'phone' => $c->phone,
                'languages' => $c->languages, 'notes' => $c->notes, 'opted_out_at' => $c->opted_out_at?->toIso8601String(),
                'press_releases_received' => $c->distributions()->where('status', 'sent')->count(),
            ])->values()->all(),
            'failed_sign_ins' => $failed->map(fn (Activity $a): array => ['at' => $a->created_at?->toIso8601String()])->values()->all(),
            'staff_account' => $search->staffAccount() !== null,
        ];

        PrivacyRequest::query()->create([
            'kind' => PrivacyRequest::EXPORT,
            'subject_hash' => $search->subjectHash(),
            'found' => $search->counts(),
            'handled_by' => $actor->id,
        ]);
        activity('privacy')->causedBy($actor)->event('export')->withProperties(['found' => $search->counts()])->log('Personal data exported');

        return $export;
    }

    /**
     * @param  Collection<int, PersonNote>  $notes
     * @return list<array{kind: string, at: string, text: ?string}>
     */
    private static function history(Collection $notes): array
    {
        return $notes->sortBy('id')->map(fn (PersonNote $n): array => ['kind' => $n->kind, 'at' => $n->created_at->toIso8601String(), 'text' => $n->body])->values()->all();
    }
}
