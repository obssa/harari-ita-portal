<?php

namespace App\Domain\Innovation\Actions;

use App\Domain\Events\Models\EventRegistration;
use App\Domain\Innovation\Mail\TeamRegisteredMail;
use App\Domain\Innovation\Models\Challenge;
use App\Domain\Innovation\Models\ChallengeTeam;
use App\Domain\Innovation\Notifications\TeamNotice;
use App\Domain\Notifications\Support\Recipients;
use App\Support\PhoneNumber;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Taking part in a challenge (ADR-0022): a team registers while registration is open, then sends or changes its one
 * submission (a title, a description and up to five links) until submissions close.
 */
final class ChallengeEntry
{
    public const MAX_LINKS = 5;

    /**
     * @param  array<string, mixed>  $data
     */
    public function register(Challenge $challenge, array $data, string $locale): ChallengeTeam
    {
        if ($challenge->phase() !== Challenge::PHASE_REGISTRATION) {
            throw ValidationException::withMessages(['form' => __('innovation.validation.registration_closed')]);
        }

        $name = trim((string) ($data['name'] ?? ''));
        $contact = trim((string) ($data['contact_name'] ?? ''));
        $email = mb_strtolower(trim((string) ($data['contact_email'] ?? '')));
        $phoneTyped = trim((string) ($data['contact_phone'] ?? ''));
        $phone = $phoneTyped !== '' ? PhoneNumber::normalise($phoneTyped) : null;
        $members = array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) ($data['members'] ?? '')) ?: []), fn (string $line): bool => $line !== ''));

        $errors = [];
        if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
            $errors['name'] = __('innovation.validation.team_name');
        } elseif (ChallengeTeam::query()->where('challenge_id', $challenge->id)->where('name', $name)->exists()) {
            $errors['name'] = __('innovation.validation.team_taken');
        }
        if (mb_strlen($contact) < 2 || mb_strlen($contact) > 120) {
            $errors['contact_name'] = __('support.validation.name');
        }
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['contact_email'] = __('support.validation.email');
        }
        if ($phoneTyped !== '' && $phone === null) {
            $errors['contact_phone'] = __('support.validation.phone');
        }
        if (count($members) + 1 > $challenge->max_team_size) {
            $errors['members'] = __('innovation.validation.team_size', ['max' => $challenge->max_team_size]);
        }
        if (! filter_var($data['consent'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $errors['consent'] = __('support.validation.consent');
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $team = new ChallengeTeam([
            'challenge_id' => $challenge->id,
            'reference' => ChallengeTeam::newReference(),
            'locale' => $locale,
            'name' => $name,
            'contact_name' => $contact,
            'contact_email' => $email,
            'contact_phone' => $phone,
            'email_hash' => EventRegistration::hash($email),
            'phone_hash' => $phone !== null ? EventRegistration::hash($phone) : null,
            'members' => $members === [] ? null : mb_substr(implode("\n", $members), 0, 3000),
            'show_in_results' => filter_var($data['show_in_results'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'consented_at' => now(),
        ]);
        $team->save();

        Mail::to($email)->locale($locale)->queue(new TeamRegisteredMail($team));
        Recipients::notify(Recipients::withPermission('challenges.manage'), TeamNotice::for($team, TeamNotice::REGISTERED));

        return $team;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function submit(ChallengeTeam $team, array $data): ChallengeTeam
    {
        if (! $team->challenge->takesSubmissions()) {
            throw ValidationException::withMessages(['form' => __('innovation.validation.submissions_closed')]);
        }

        $title = trim((string) ($data['submission_title'] ?? ''));
        $description = trim((string) ($data['submission_description'] ?? ''));
        $links = [];
        $errors = [];
        foreach (array_slice(array_values(array_filter(array_map('trim', (array) ($data['submission_links'] ?? [])))), 0, self::MAX_LINKS) as $i => $link) {
            if (! str_starts_with($link, 'https://') || filter_var($link, FILTER_VALIDATE_URL) === false) {
                $errors["submission_links.{$i}"] = __('innovation.validation.website');
            }
            $links[] = mb_substr($link, 0, 500);
        }
        if (mb_strlen($title) < 2 || mb_strlen($title) > 200) {
            $errors['submission_title'] = __('innovation.validation.submission_title');
        }
        if (mb_strlen($description) < 20 || mb_strlen($description) > 5000) {
            $errors['submission_description'] = __('innovation.validation.submission_description');
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $first = $team->submitted_at === null;
        $team->forceFill([
            'submission_title' => $title,
            'submission_description' => $description,
            'submission_links' => $links,
            'submitted_at' => CarbonImmutable::now(),
        ])->save();
        if ($first) {
            Recipients::notify(Recipients::withPermission('challenges.manage'), TeamNotice::for($team, TeamNotice::SUBMITTED));
        }

        return $team;
    }
}
