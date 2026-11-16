<?php

namespace App\Domain\Innovation\Actions;

use App\Domain\Events\Models\EventRegistration;
use App\Domain\Innovation\Enums\ApplicationStage;
use App\Domain\Innovation\Mail\ApplicationMail;
use App\Domain\Innovation\Models\Application;
use App\Domain\Innovation\Models\Cohort;
use App\Domain\Innovation\Models\Programme;
use App\Domain\Innovation\Notifications\NewApplicationNotice;
use App\Domain\Notifications\Support\Recipients;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * An application to the cohort of a programme that is open now (ADR-0021): the applicant, the team, the answers
 * to the programme's questions and consent. One application per email address and cohort.
 */
final class SubmitApplication
{
    public const MAX_ANSWER = 3000;

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Programme $programme, array $data, string $locale): Application
    {
        $cohort = $programme->openCohort();
        if (! $cohort instanceof Cohort) {
            throw ValidationException::withMessages(['form' => __('innovation.validation.closed')]);
        }

        $name = trim((string) ($data['applicant_name'] ?? ''));
        $email = mb_strtolower(trim((string) ($data['applicant_email'] ?? '')));
        $phoneTyped = trim((string) ($data['applicant_phone'] ?? ''));
        $phone = $phoneTyped !== '' ? PhoneNumber::normalise($phoneTyped) : null;
        $team = trim((string) ($data['team_name'] ?? ''));

        $errors = [];
        if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
            $errors['applicant_name'] = __('support.validation.name');
        }
        if ($email === '' || mb_strlen($email) > 190 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['applicant_email'] = __('support.validation.email');
        }
        if ($phoneTyped !== '' && $phone === null) {
            $errors['applicant_phone'] = __('support.validation.phone');
        }

        $answers = [];
        $given = (array) ($data['answers'] ?? []);
        foreach ($programme->questionsIn($locale) as $question) {
            $answer = trim((string) ($given[$question['key']] ?? ''));
            if ($question['required'] && $answer === '') {
                $errors["answers.{$question['key']}"] = __('innovation.validation.answer');
            }
            if ($answer !== '') {
                $answers[$question['key']] = mb_substr($answer, 0, self::MAX_ANSWER);
            }
        }
        if (! filter_var($data['consent'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $errors['consent'] = __('support.validation.consent');
        }
        if ($errors === [] && Application::query()->where('cohort_id', $cohort->id)->where('email_hash', EventRegistration::hash($email))->exists()) {
            $errors['applicant_email'] = __('innovation.validation.already_applied');
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $application = new Application([
            'reference' => Application::newReference(),
            'programme_id' => $programme->id,
            'cohort_id' => $cohort->id,
            'locale' => $locale,
            'applicant_name' => $name,
            'applicant_email' => $email,
            'applicant_phone' => $phone,
            'email_hash' => EventRegistration::hash($email),
            'phone_hash' => $phone !== null ? EventRegistration::hash($phone) : null,
            'team_name' => $team !== '' ? mb_substr($team, 0, 200) : null,
            'answers' => $answers,
            'stage' => ApplicationStage::Received,
            'consented_at' => now(),
        ]);
        $application->save();

        Mail::to($email)->locale($locale)->queue(new ApplicationMail($application, 'received'));
        Recipients::notify(Recipients::withPermission('applications.review'), NewApplicationNotice::for($application));

        return $application;
    }
}
