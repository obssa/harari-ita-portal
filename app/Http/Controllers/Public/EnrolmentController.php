<?php

namespace App\Http\Controllers\Public;

use App\Domain\Academy\Actions\CancelEnrolment;
use App\Domain\Academy\Actions\EnrolInCohort;
use App\Domain\Academy\Enums\EnrolmentStatus;
use App\Domain\Academy\Models\CourseTranslation;
use App\Domain\Academy\Models\Enrolment;
use App\Domain\Academy\Support\AttendanceRule;
use App\Domain\Organisation\Queries\PublicDirectory;
use App\Domain\Translation\Support\Locales;
use App\Http\Controllers\Controller;
use App\Support\SpamGuard;
use App\Support\Turnstile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enrolling in a cohort, the learner's private page and their printable certificate (ADR-0029, ADR-0030). These pages
 * keep a session for the form protection and are never cached: the learner's page is personal.
 */
final class EnrolmentController extends Controller
{
    public function create(string $slug, int $cohort, Turnstile $turnstile): View
    {
        [$course, $translation, $record] = AcademyController::cohort($slug, $cohort);

        return view('pages.academy.enrol', [
            'course' => $course,
            'translation' => $translation,
            'cohort' => $record,
            'state' => $record->enrolmentState(),
            'placesLeft' => $record->placesLeft(),
            'turnstileKey' => $turnstile->siteKey(),
            // The same cohort's form in each language the course is published in.
            'alternates' => collect(Locales::all())->mapWithKeys(function (string $locale) use ($course, $record): array {
                $shown = $course->translation($locale, fallback: false);

                return [$locale => $shown instanceof CourseTranslation && $shown->isLive()
                    ? route('enrolments.create', ['locale' => $locale, 'slug' => $shown->slug, 'cohort' => $record->id])
                    : route('academy.index', ['locale' => $locale])];
            })->all(),
        ]);
    }

    public function store(Request $request, string $slug, int $cohort, EnrolInCohort $enrol, Turnstile $turnstile): RedirectResponse
    {
        [, , $record] = AcademyController::cohort($slug, $cohort);

        SpamGuard::check($request);
        $turnstile->check($request);

        $result = $enrol->handle($record, $request->only(['name', 'phone', 'email', 'consent']), app()->getLocale());

        if ($result->existing) {
            // Say no more than that the link was sent again to the details on file.
            return redirect()->route('enrolments.create', ['slug' => $slug, 'cohort' => $cohort])->with('status', __('academy.enrol.again'));
        }

        return redirect()->route('enrolments.show', ['token' => (string) $result->enrolment->token])
            ->with('status', __("academy.enrol.done.{$result->enrolment->status->value}"));
    }

    public function show(string $token): Response
    {
        $enrolment = $this->enrolment($token);
        $cohort = $enrolment->cohort;
        $sessions = $cohort->sessions;
        $attended = $enrolment->attendances()->pluck('course_session_id')->all();
        $translation = PublicDirectory::shown($cohort->course, app()->getLocale());

        return response()->view('pages.academy.enrolment', [
            'enrolment' => $enrolment,
            'cohort' => $cohort,
            'course' => $cohort->course,
            'translation' => $translation instanceof CourseTranslation ? $translation : null,
            'sessions' => $sessions,
            'attended' => $attended,
            'percent' => AttendanceRule::percent($sessions->count(), count($attended)),
            'certificate' => $enrolment->certificate,
            'token' => $token,
            'position' => $enrolment->waitlistPosition(),
            'canCancel' => $enrolment->status !== EnrolmentStatus::Cancelled && $enrolment->certificate === null && ! $cohort->hasEnded() && ! $cohort->isCancelled(),
        ])->header('Cache-Control', 'private, no-store')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function cancel(string $token, CancelEnrolment $cancel): RedirectResponse
    {
        $enrolment = $this->enrolment($token);
        abort_if($enrolment->cohort->hasEnded(), 404);

        $cancel->handle($enrolment);

        return redirect()->route('enrolments.show', ['token' => $token])->with('status', __('academy.enrol.cancelled'));
    }

    public function certificate(string $token): Response
    {
        $enrolment = $this->enrolment($token);
        $certificate = $enrolment->certificate;
        abort_if($certificate === null, 404);
        $certificate->load(['course.translations', 'cohort', 'enrolment']);

        return response()->view('pages.academy.certificate', [
            'certificate' => $certificate,
            'token' => $token,
        ])->header('Cache-Control', 'private, no-store')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    private function enrolment(string $token): Enrolment
    {
        $enrolment = Enrolment::findByToken($token);
        abort_if($enrolment === null, 404);
        $enrolment->load(['cohort.course.translations', 'cohort.sessions', 'cohort.office.translations', 'certificate']);

        return $enrolment;
    }
}
