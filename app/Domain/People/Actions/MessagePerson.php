<?php

namespace App\Domain\People\Actions;

use App\Domain\Access\Support\OfficeScope;
use App\Domain\Innovation\Models\Application;
use App\Domain\Innovation\Models\ChallengeTeam;
use App\Domain\People\Mail\PersonMail;
use App\Domain\People\Models\PersonNote;
use App\Domain\Volunteers\Models\Volunteer;
use App\Models\User;
use App\Services\Sms\SendSms;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Staff write to an applicant, a challenge team or a volunteer (ADR-0053). The message goes by email, or by text
 * message when there is only a phone number, in the person's language around the staff member's own words. The
 * words are kept, encrypted, in the person's history (people_notes), never in the activity log, so a privacy
 * export shows them and an erasure removes them.
 */
final class MessagePerson
{
    public const MAX = 2000;

    /** A text message stays within two SMS parts in Ethiopic. */
    public const MAX_SMS = 320;

    public function application(Application $application, string $text, User $actor): void
    {
        abort_unless($actor->can('review', $application), 403);
        $locale = $application->locale;

        $this->send(
            $application,
            $application->applicant_name,
            $application->applicant_email,
            $application->applicant_phone,
            $locale,
            $application->programme->title($locale),
            $text,
            $actor,
            $application->reference,
            route('applications.lookup', ['locale' => $locale, 'reference' => $application->reference]),
        );
    }

    public function team(ChallengeTeam $team, string $text, User $actor): void
    {
        abort_unless($actor->can('message', $team), 403);
        $locale = $team->locale;

        $this->send(
            $team,
            $team->contact_name,
            $team->contact_email,
            $team->contact_phone,
            $locale,
            $team->challenge->title($locale),
            $text,
            $actor,
            $team->reference,
            route('teams.lookup', ['locale' => $locale, 'reference' => $team->reference]),
        );
    }

    public function volunteer(Volunteer $volunteer, string $text, User $actor): void
    {
        abort_unless(OfficeScope::covers($actor, $volunteer->office_id, 'volunteers.manage'), 403);
        $locale = $volunteer->locale;

        $this->send(
            $volunteer,
            $volunteer->name,
            $volunteer->email,
            $volunteer->phone,
            $locale,
            __('people.message.about_volunteering', [], $locale),
            $text,
            $actor,
        );
    }

    public static function reachable(?string $email, ?string $phone): bool
    {
        return filled($email) || filled($phone);
    }

    private function send(
        Application|ChallengeTeam|Volunteer $record,
        ?string $name,
        ?string $email,
        ?string $phone,
        string $locale,
        string $about,
        string $text,
        User $actor,
        ?string $reference = null,
        ?string $url = null,
    ): void {
        $text = trim($text);
        if ($text === '' || mb_strlen($text) > self::MAX) {
            throw ValidationException::withMessages(['body' => __('people.validation.message')]);
        }
        if (! self::reachable($email, $phone)) {
            throw ValidationException::withMessages(['body' => __('people.validation.no_contact')]);
        }

        if (filled($email)) {
            Mail::to($email)->locale($locale)->queue(new PersonMail(
                __('people.mail.message.subject', ['about' => $about], $locale),
                __('people.mail.message.heading', [], $locale),
                $name,
                $text,
                $reference,
                $url,
                __('people.mail.message.button', [], $locale),
            ));
        } else {
            if (mb_strlen($text) > self::MAX_SMS) {
                throw ValidationException::withMessages(['body' => __('people.validation.message_sms', ['max' => self::MAX_SMS])]);
            }
            SendSms::dispatch((string) $phone, __('people.sms.message', ['about' => $about, 'text' => $text], $locale));
        }

        $record->notes()->create(['user_id' => $actor->id, 'kind' => PersonNote::MESSAGE, 'body' => $text]);
    }
}
