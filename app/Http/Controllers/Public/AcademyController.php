<?php

namespace App\Http\Controllers\Public;

use App\Domain\Academy\Enums\CourseLevel;
use App\Domain\Academy\Enums\CourseMode;
use App\Domain\Academy\Models\Certificate;
use App\Domain\Academy\Models\Course;
use App\Domain\Academy\Models\CourseCohort;
use App\Domain\Academy\Models\CourseTranslation;
use App\Domain\Academy\Support\CohortCalendar;
use App\Domain\Content\Support\BodyRenderer;
use App\Domain\Organisation\Models\Pillar;
use App\Domain\Organisation\Queries\PublicDirectory;
use App\Domain\Translation\Support\Locales;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * The Academy on the public site (ADR-0031): the course catalogue with its filters, the upcoming cohorts as a
 * calendar in both calendars, a course with its cohorts, and each cohort's sessions as a calendar file.
 */
final class AcademyController extends Controller
{
    public const CALENDAR_LIMIT = 12;

    public function index(Request $request): View
    {
        $locale = app()->getLocale();
        $level = CourseLevel::tryFrom((string) $request->query('level'));
        $mode = CourseMode::tryFrom((string) $request->query('mode'));
        $language = Locales::isSupported((string) $request->query('language')) ? (string) $request->query('language') : null;
        $pillars = PublicDirectory::visible(Pillar::query(), $locale)->with('translations')->orderBy('sort')->get()
            ->mapWithKeys(fn (Pillar $p): array => [$p->id => (string) PublicDirectory::shown($p, $locale)?->getAttribute('name')])->all();
        $pillar = array_key_exists((string) $request->query('pillar'), $pillars) ? (string) $request->query('pillar') : null;

        $courses = PublicDirectory::visible(Course::query(), $locale)
            ->with(['cohorts' => fn ($q) => $q->with('office.translations'), 'coverImage', 'pillar.translations'])
            ->orderByDesc('featured')->orderBy('sort')->get();
        $all = $courses;
        $filtered = $courses
            ->when($level !== null, fn (Collection $c) => $c->filter(fn (Course $course): bool => $course->level === $level))
            ->when($mode !== null, fn (Collection $c) => $c->filter(fn (Course $course): bool => $course->mode === $mode || $course->upcomingCohorts()->contains(fn (CourseCohort $cohort): bool => $cohort->mode === $mode)))
            ->when($language !== null, fn (Collection $c) => $c->filter(fn (Course $course): bool => in_array($language, $course->languages ?? [], true)))
            ->when($pillar !== null, fn (Collection $c) => $c->filter(fn (Course $course): bool => $course->pillar_id === $pillar))
            ->values();

        return view('pages.academy.index', [
            'courses' => $filtered,
            'calendar' => self::calendar($filtered, self::CALENDAR_LIMIT),
            'pillars' => $pillars,
            'filters' => ['level' => $level?->value, 'mode' => $mode?->value, 'language' => $language, 'pillar' => $pillar],
            'languages' => collect($all)->flatMap(fn (Course $c): array => $c->languages ?? [])->unique()->sort()->values()->all(),
            'learners' => self::learners(),
        ]);
    }

    public function show(string $slug): View
    {
        [$course, $translation] = $this->find($slug);
        $locale = app()->getLocale();
        $course->load(['cohorts' => fn ($q) => $q->with(['office.translations', 'sessions', 'instructors' => fn ($i) => $i->shown()]), 'pillar.translations', 'coverImage']);
        $upcoming = $course->upcomingCohorts();
        $url = route('courses.show', ['slug' => $translation->slug]);

        return view('pages.academy.show', [
            'course' => $course,
            'translation' => $translation,
            'body' => BodyRenderer::toHtml($translation->body),
            'upcoming' => $upcoming,
            'past' => $course->cohorts->filter(fn (CourseCohort $c): bool => ! $c->isCancelled() && $c->hasEnded())->count(),
            'instructors' => $upcoming->flatMap(fn (CourseCohort $c) => $c->instructors)->unique('id')->values(),
            'pillar' => $course->pillar !== null ? PublicDirectory::shown($course->pillar, $locale) : null,
            'alternates' => PublicDirectory::alternates($course, 'courses.show', 'academy.index'),
            'jsonLd' => [self::courseJsonLd($course, $translation, $url, $upcoming)],
        ]);
    }

    public function ics(string $slug, int $cohort): Response
    {
        [$course] = $this->find($slug);
        $record = $course->cohorts()->whereKey($cohort)->whereNull('cancelled_at')->with(['sessions', 'office.translations'])->first();
        abort_if($record === null, 404);
        $record->setRelation('course', $course);
        $ics = CohortCalendar::render($record, app()->getLocale());
        abort_if($ics === null, 404);

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="course.ics"',
        ]);
    }

    /**
     * The upcoming cohorts of the courses given, soonest first, that a learner may still join or wait for.
     *
     * @param  iterable<Course>  $courses
     * @return Collection<int, array{cohort: CourseCohort, course: Course, translation: CourseTranslation}>
     */
    public static function calendar(iterable $courses, int $limit): Collection
    {
        $locale = app()->getLocale();
        $today = CarbonImmutable::today();
        $rows = collect();
        foreach ($courses as $course) {
            $translation = PublicDirectory::shown($course, $locale);
            if (! $translation instanceof CourseTranslation) {
                continue;
            }
            foreach ($course->upcomingCohorts($today) as $cohort) {
                if ($cohort->starts_on >= $today) {
                    $rows->push(['cohort' => $cohort, 'course' => $course, 'translation' => $translation]);
                }
            }
        }

        return $rows->sortBy(fn (array $row): string => $row['cohort']->starts_on->toDateString().'-'.$row['cohort']->id)->take($limit)->values();
    }

    /** Learners who received a certificate, for the hero, from live records. */
    public static function learners(): int
    {
        return Certificate::query()->whereNull('revoked_at')->distinct()->count('enrolment_id');
    }

    /**
     * schema.org `Course` with its upcoming runs as `CourseInstance` (ADR-0031).
     *
     * @param  Collection<int, CourseCohort>  $upcoming
     * @return array<string, mixed>
     */
    private static function courseJsonLd(Course $course, CourseTranslation $translation, string $url, Collection $upcoming): array
    {
        $modes = [CourseMode::InPerson->value => 'onsite', CourseMode::Online->value => 'online', CourseMode::Blended->value => 'blended'];

        return array_filter([
            '@type' => 'Course',
            '@id' => $url.'#course',
            'name' => $translation->title,
            'description' => $translation->summary,
            'url' => $url,
            'inLanguage' => $course->languages,
            'educationalLevel' => $course->level->label(),
            'provider' => ['@id' => url('/').'#organization'],
            'teaches' => $translation->outcomeList() ?: null,
            'timeRequired' => $course->duration_hours !== null ? 'PT'.$course->duration_hours.'H' : null,
            'hasCourseInstance' => $upcoming->map(fn (CourseCohort $cohort): array => array_filter([
                '@type' => 'CourseInstance',
                'name' => $cohort->name,
                'courseMode' => $modes[$cohort->mode->value],
                'startDate' => $cohort->starts_on->toDateString(),
                'endDate' => $cohort->ends_on->toDateString(),
                'location' => $cohort->office !== null ? ['@type' => 'Place', 'name' => $cohort->office->name()] : null,
            ], fn (mixed $v): bool => $v !== null))->values()->all() ?: null,
        ], fn (mixed $value): bool => $value !== null && $value !== '');
    }

    /**
     * @return array{0: Course, 1: CourseTranslation}
     */
    private function find(string $slug): array
    {
        /** @var CourseTranslation|null $translation */
        $translation = CourseTranslation::query()->where('locale', app()->getLocale())->where('slug', $slug)->first();
        $course = $translation?->course()->with('translations')->first();
        abort_if($translation === null || $course === null || ! $translation->isLive(), 404);

        return [$course, $translation];
    }

    /**
     * Used by the enrolment pages: a live course's cohort.
     *
     * @return array{0: Course, 1: CourseTranslation, 2: CourseCohort}
     */
    public static function cohort(string $slug, int $cohort): array
    {
        /** @var CourseTranslation|null $translation */
        $translation = CourseTranslation::query()->where('locale', app()->getLocale())->where('slug', $slug)->first();
        $course = $translation?->course()->with('translations')->first();
        $record = $course?->cohorts()->whereKey($cohort)->with(['office.translations', 'sessions'])->first();
        abort_if($translation === null || $course === null || $record === null || ! $translation->isLive(), 404);
        $record->setRelation('course', $course);

        return [$course, $translation, $record];
    }
}
