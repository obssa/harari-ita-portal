<?php

use App\Domain\Academy\Actions\SaveCourse;
use App\Domain\Academy\Actions\SaveInstructor;
use App\Domain\Academy\Models\Certificate;
use App\Domain\Academy\Models\Course;
use App\Domain\Academy\Models\Instructor;
use App\Domain\Access\Enums\SystemRole;
use App\Domain\Homepage\Enums\SectionKey;
use App\Domain\Homepage\Support\HomepageLayout;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

it('lists courses with filters and the next cohorts in both calendars', function () {
    $course = sampleCourse();
    sampleCohort($course);
    sampleCourse('Sample advanced data course', ['level' => 'advanced', 'mode' => 'online', 'languages' => ['om']]);
    app(SaveCourse::class)->handle(new Course, ['translations' => ['en' => ['title' => 'Sample draft course', 'published' => false]]], staff(SystemRole::AcademyCoordinator));

    $this->get('/en/academy')
        ->assertOk()
        ->assertSee('Sample digital basics')
        ->assertSee('Sample advanced data course')
        ->assertDontSee('Sample draft course')
        ->assertSee(__('academy.calendar.title', [], 'en'))
        ->assertSee(__('academy.state.open', [], 'en'))
        ->assertSee(route('certificates.verify', ['locale' => 'en']), escape: false);

    $this->get('/en/academy?level=advanced')->assertOk()->assertSee('Sample advanced data course')->assertDontSee('Sample digital basics');
    $this->get('/en/academy?language=am')->assertOk()->assertSee('Sample digital basics')->assertDontSee('Sample advanced data course');
    $this->get('/en/academy?mode=online&level=beginner')->assertOk()->assertSee(__('academy.catalogue.none_match', [], 'en'));
});

it('shows a course with its outcomes, syllabus, cohorts, consenting instructors only, Course JSON-LD and a calendar file', function () {
    $course = sampleCourse();
    $cohort = sampleCohort($course, ['capacity' => 20]);
    $coordinator = staff(SystemRole::AcademyCoordinator);
    $shown = app(SaveInstructor::class)->handle(new Instructor, ['name' => 'Sample Shown Trainer', 'consented' => true, 'public' => true], $coordinator);
    $hidden = app(SaveInstructor::class)->handle(new Instructor, ['name' => 'Sample Hidden Trainer'], $coordinator);
    $cohort->instructors()->sync([$shown->id, $hidden->id]);

    $this->get('/en/academy/courses/sample-digital-basics')
        ->assertOk()
        ->assertSee('Sample outcome two')
        ->assertSee('Sample module one')
        ->assertSee('Sample cohort')
        ->assertSee(trans_choice('academy.cohort.places_left', 20, ['count' => 20], 'en'))
        ->assertSee('Sample Shown Trainer')
        ->assertDontSee('Sample Hidden Trainer')
        ->assertSee('"@type":"Course"', escape: false)
        ->assertSee('"@type":"CourseInstance"', escape: false)
        ->assertSee('"courseMode":"onsite"', escape: false);

    $ics = $this->get("/en/academy/courses/sample-digital-basics/cohorts/{$cohort->id}.ics")->assertOk();
    expect($ics->headers->get('Content-Type'))->toContain('text/calendar')
        ->and(substr_count($ics->getContent(), 'BEGIN:VEVENT'))->toBe(3);

    // A public instructor without consent cannot be saved.
    expect(fn () => app(SaveInstructor::class)->handle(new Instructor, ['name' => 'Sample Trainer', 'public' => true], $coordinator))
        ->toThrow(ValidationException::class);

    $this->get('/en/academy/courses/no-such-course')->assertNotFound();
});

it('puts the Academy on the homepage, in search, the sitemap, the menu and the numbers page', function () {
    $course = sampleCourse();
    sampleCohort($course, ['starts_on' => CarbonImmutable::today()->addDays(10)->toDateString(), 'ends_on' => CarbonImmutable::today()->addDays(12)->toDateString(), 'sessions' => []]);

    $sections = collect(app(HomepageLayout::class)->forLocale('en'))->keyBy(fn (array $s) => $s['section']->key->value);
    expect($sections->has(SectionKey::Academy->value))->toBeTrue();

    $this->get('/en')->assertOk()->assertSee('Sample digital basics')->assertSee(route('academy.index', ['locale' => 'en']), escape: false);
    $this->get('/en/search?q=digital&kind=courses')->assertOk()->assertSee('Sample digital basics');
    $this->get('/sitemaps/en.xml')->assertOk()->assertSee(route('courses.show', ['locale' => 'en', 'slug' => 'sample-digital-basics']), escape: false);
    $this->get('/en/numbers')->assertOk()->assertSee(__('numbers.live.courses', [], 'en'));
});

it('adds and removes the labelled samples', function () {
    staff(SystemRole::SuperAdmin);

    $this->artisan('portal:sample-academy')->assertSuccessful();
    expect(Course::query()->count())->toBe(1)
        ->and(Certificate::query()->sole()->isValid())->toBeTrue();
    $this->get('/en/academy')->assertOk()->assertSee('[Sample] Digital skills for office staff');
    $this->get('/am/academy')->assertOk()->assertSee('ለቢሮ ሠራተኞች የዲጂታል ክህሎት');

    $this->artisan('portal:sample-academy --remove')->assertSuccessful();
    expect(Course::query()->withTrashed()->count())->toBe(0)
        ->and(Instructor::query()->count())->toBe(0);
});
