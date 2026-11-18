@use('App\Domain\Organisation\Queries\PublicDirectory')
@php $calendar = app(\App\Services\Calendar\EthiopianCalendar::class); @endphp
{{-- ADR-0031: the next Academy cohorts, as date tiles in both calendars, with the catalogue and the certificate check. --}}
<section aria-labelledby="home-academy" class="mx-auto max-w-page px-4 py-12 sm:px-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="kicker">{{ __('academy.kicker') }}</p>
            {{-- ADR-0046: while only the catalogue shows, the heading speaks of courses rather than cohorts. --}}
            <h2 id="home-academy" class="heading-2 mt-2">{{ $data['cohorts']->isEmpty() && blank($section->setting('titles.'.app()->getLocale())) ? __('homepage.academy.courses_title') : $section->heading() }}</h2>
        </div>
        <p class="flex flex-wrap gap-4 text-sm font-semibold">
            <a href="{{ route('academy.index') }}" class="text-link underline-offset-4 hover:underline">{{ __('academy.all') }}</a>
            <a href="{{ route('certificates.verify') }}" class="text-link underline-offset-4 hover:underline">{{ __('academy.verify.title') }}</a>
        </p>
    </div>
    @if ($data['cohorts']->isEmpty())
        {{-- ADR-0045: while no cohort is scheduled, the courses on offer. --}}
        <ul class="mt-6 grid gap-4 md:grid-cols-3">
            @foreach ($data['courses'] as $course)
                @php $shownCourse = PublicDirectory::shown($course, app()->getLocale()); @endphp
                @continue($shownCourse === null)
                <li><x-course-card :course="$course" :translation="$shownCourse" :heading-level="3" /></li>
            @endforeach
        </ul>
    @endif
    @if ($data['cohorts']->isNotEmpty())
    <ul class="mt-6 grid gap-4 md:grid-cols-3">
        @foreach ($data['cohorts'] as $row)
            @php $state = $row['cohort']->enrolmentState(); @endphp
            <li lang="{{ $row['translation']->locale }}" class="group relative flex gap-4 rounded-card border border-line bg-surface p-5 transition duration-200 hover:-translate-y-0.5 hover:border-cyan-500 hover:shadow-lg hover:shadow-night-950/5">
                <x-ui.date-tile :date="$row['cohort']->starts_on" class="shrink-0" />
                <div class="min-w-0">
                    <p class="font-mono text-xs tracking-wide text-ink-muted uppercase">{{ $row['cohort']->mode->label() }}</p>
                    <h3 class="mt-1 font-display text-lg leading-snug font-semibold tracking-tight">
                        <a href="{{ route('courses.show', ['locale' => $row['translation']->locale, 'slug' => $row['translation']->slug]) }}#cohort-{{ $row['cohort']->id }}" class="after:absolute after:inset-0 group-hover:underline underline-offset-4">{{ $row['translation']->title }}</a>
                    </h3>
                    <p class="mt-2"><x-ui.badge :tone="match ($state) { 'open' => 'success', 'waitlist', 'soon' => 'warning', default => 'neutral' }">{{ __('academy.state.'.$state) }}</x-ui.badge></p>
                </div>
            </li>
        @endforeach
    </ul>
    @endif
</section>
