<?php

namespace App\Console\Commands;

use App\Domain\Academy\Actions\IssueCertificate;
use App\Domain\Academy\Actions\MarkAttendance;
use App\Domain\Academy\Actions\SaveCohort;
use App\Domain\Academy\Actions\SaveCourse;
use App\Domain\Academy\Actions\SaveInstructor;
use App\Domain\Academy\Enums\EnrolmentStatus;
use App\Domain\Academy\Models\Certificate;
use App\Domain\Academy\Models\Course;
use App\Domain\Academy\Models\CourseCohort;
use App\Domain\Academy\Models\Enrolment;
use App\Domain\Academy\Models\Instructor;
use App\Domain\Access\Enums\SystemRole;
use App\Domain\Organisation\Models\Pillar;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Clearly labelled sample content for the phase 5 gate on staging (docs/phases/05-academy.md): one course in English,
 * Amharic and Afaan Oromo with a sample instructor; a cohort taking enrolments; and a cohort that ran last month with
 * one sample learner (no phone or email, so nothing is ever sent), their attendance and a certificate to verify.
 * `--remove` deletes exactly what it added. Refuses to run in production.
 */
final class SampleAcademyCommand extends Command
{
    protected $signature = 'portal:sample-academy {--remove : Delete the sample content again}';

    protected $description = 'Add (or remove) a labelled sample course, cohorts, learner and certificate';

    private const MARK = '[Sample]';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->components->error('Not in production.');

            return self::FAILURE;
        }

        return $this->option('remove') ? $this->remove() : $this->add();
    }

    private function add(): int
    {
        if (Course::query()->whereHas('translations', fn ($q) => $q->where('title', 'like', self::MARK.'%'))->exists()) {
            $this->components->warn('Sample content is already there.');

            return self::SUCCESS;
        }

        $actor = User::role(SystemRole::SuperAdmin->value)->firstOrFail();
        $today = CarbonImmutable::today();

        $instructor = app(SaveInstructor::class)->handle(new Instructor, [
            'name' => self::MARK.' Instructor',
            'titles' => ['en' => 'Sample trainer (not a real person)', 'am' => 'ናሙና አሰልጣኝ (እውነተኛ ሰው አይደለም)', 'om' => 'Leenjisaa fakkeenyaa (nama dhugaa miti)'],
            'consented' => true,
            'public' => true,
        ], $actor);

        $course = app(SaveCourse::class)->handle(new Course, [
            'pillar_id' => Pillar::query()->orderBy('sort')->value('id'),
            'level' => 'beginner',
            'mode' => 'blended',
            'duration_hours' => 18,
            'languages' => ['am', 'om', 'en'],
            'partner' => self::MARK.' Partner institution',
            'featured' => true,
            'translations' => [
                'en' => [
                    'title' => self::MARK.' Digital skills for office staff',
                    'summary' => 'Sample course for testing the Academy. Not a real course.',
                    'audience' => 'Sample: staff of public offices who use a computer at work.',
                    'prerequisites' => 'Sample: none.',
                    'outcomes' => "Sample: manage files and folders safely\nSample: write and share documents\nSample: spot a phishing message",
                    'syllabus' => "Sample module: the computer and its files\nSample module: documents and spreadsheets\nSample module: email and staying safe online",
                    'published' => true,
                ],
                'am' => [
                    'title' => self::MARK.' ለቢሮ ሠራተኞች የዲጂታል ክህሎት',
                    'summary' => 'አካዳሚውን ለመሞከር የቀረበ ናሙና ኮርስ። እውነተኛ ኮርስ አይደለም።',
                    'outcomes' => "ናሙና፦ ፋይሎችንና ማኅደሮችን በጥንቃቄ ማስተዳደር\nናሙና፦ ሰነዶችን መጻፍና ማጋራት\nናሙና፦ የማጭበርበሪያ መልእክትን መለየት",
                    'syllabus' => "ናሙና ሞጁል፦ ኮምፒውተርና ፋይሎቹ\nናሙና ሞጁል፦ ሰነዶችና የተመን ሉሆች\nናሙና ሞጁል፦ ኢሜይልና በኦንላይን ደኅንነት",
                    'published' => true,
                ],
                'om' => [
                    'title' => self::MARK.' Dandeettii dijitaalaa hojjettoota waajjiraaf',
                    'summary' => 'Koorsii fakkeenyaa Akkaadamii qoruuf. Koorsii dhugaa miti.',
                    'outcomes' => "Fakkeenya: faayilootaa fi kuusaalee nageenyaan bulchuu\nFakkeenya: sanadoota barreessuu fi qooduu\nFakkeenya: ergaa gowwoomsaa adda baasuu",
                    'syllabus' => "Moojulii fakkeenyaa: kompiitara fi faayiloota isaa\nMoojulii fakkeenyaa: sanadootaa fi gabateewwan herregaa\nMoojulii fakkeenyaa: imeelii fi nageenya toora interneetii",
                    'published' => true,
                ],
            ],
        ], $actor);

        // A cohort taking enrolments now.
        $starts = $today->addWeeks(3);
        app(SaveCohort::class)->handle(new CourseCohort, [
            'course_id' => $course->id, 'name' => self::MARK.' Cohort 2', 'mode' => 'blended',
            'venue_notes' => ['en' => 'Sample venue (invented)', 'am' => 'ናሙና ቦታ (የተፈጠረ)', 'om' => 'Bakka fakkeenyaa (uumamaa)'],
            'starts_on' => $starts->toDateString(), 'ends_on' => $starts->addDays(10)->toDateString(),
            'enrol_opens_at' => $today->subDay()->toDateTimeString(), 'capacity' => 25, 'waitlist' => true,
            'instructors' => [$instructor->id],
            'sessions' => array_map(fn (int $day): array => [
                'starts_at' => $starts->addDays($day)->setTime(9, 0)->toDateTimeString(),
                'ends_at' => $starts->addDays($day)->setTime(13, 30)->toDateTimeString(),
            ], [0, 3, 7, 10]),
        ], $actor);

        // A cohort that ran last month, with one sample learner and their certificate.
        $ran = $today->subMonth();
        $past = app(SaveCohort::class)->handle(new CourseCohort, [
            'course_id' => $course->id, 'name' => self::MARK.' Cohort 1', 'mode' => 'in_person',
            'starts_on' => $ran->toDateString(), 'ends_on' => $ran->addDays(4)->toDateString(),
            'enrol_opens_at' => $ran->subMonth()->toDateTimeString(),
            'instructors' => [$instructor->id],
            'sessions' => array_map(fn (int $day): array => [
                'starts_at' => $ran->addDays($day)->setTime(9, 0)->toDateTimeString(),
                'ends_at' => $ran->addDays($day)->setTime(13, 30)->toDateTimeString(),
            ], [0, 2, 4]),
        ], $actor);

        $token = Str::random(40);
        $learner = new Enrolment([
            'locale' => 'en', 'name' => self::MARK.' Learner Example', 'status' => EnrolmentStatus::Enrolled,
            'token' => $token, 'token_hash' => Enrolment::hash($token), 'consented_at' => $ran->subWeeks(2),
        ]);
        $past->enrolments()->save($learner);
        foreach ($past->sessions()->get() as $session) {
            app(MarkAttendance::class)->handle($session, [$learner->id], $actor);
        }
        $certificate = app(IssueCertificate::class)->handle($learner, $actor, notify: false);

        $this->components->info('Sample course, two cohorts, a learner and a certificate added.');
        $this->line('Certificate: '.$certificate->code);
        $this->line('Learner page: '.route('enrolments.show', ['locale' => 'en', 'token' => $token]));

        return self::SUCCESS;
    }

    private function remove(): int
    {
        $courses = Course::query()->withTrashed()->whereHas('translations', fn ($q) => $q->where('title', 'like', self::MARK.'%'))->get();
        $cohortIds = CourseCohort::query()->whereIn('course_id', $courses->pluck('id'))->pluck('id');

        $certificates = Certificate::query()->whereIn('course_cohort_id', $cohortIds)->delete();
        $enrolments = Enrolment::query()->whereIn('course_cohort_id', $cohortIds)->delete();
        $courses->each(fn (Course $course) => $course->forceDelete());
        $instructors = Instructor::query()->where('name', 'like', self::MARK.'%')->delete();

        $this->components->info(sprintf('Removed %d course(s), %d enrolment(s), %d certificate(s), %d instructor(s).', $courses->count(), $enrolments, $certificates, $instructors));

        return self::SUCCESS;
    }
}
