@use('App\Domain\Academy\Models\CourseCohort')
@use('App\Domain\Organisation\Queries\PublicDirectory')
@use('App\Domain\Translation\Support\Locales')
@php
    $locale = app()->getLocale();
    $calendar = app(\App\Services\Calendar\EthiopianCalendar::class);
    $first = $upcoming->first(fn (CourseCohort $c): bool => $c->acceptsEnrolment());
@endphp
<x-layouts.public :title="$translation->title" :description="$translation->summary" :alternates="$alternates" :json-ld="$jsonLd">
    {{-- ADR-0031: a course: who it is for, what learners will be able to do, the syllabus, and its cohorts with the way
         to enrol in each. --}}
    <x-page-hero :title="$translation->title" :lead="$translation->summary" :kicker="$course->level->label()" tone="night"
                 :crumbs="[[__('academy.title'), route('academy.index')]]" :heading-lang="$translation->locale">
        <ul class="flex flex-wrap gap-2" aria-label="{{ __('academy.course.facts') }}">
            <li><x-ui.badge tone="cyan">{{ $course->mode->label() }}</x-ui.badge></li>
            @if ($course->duration_hours)
                <li><x-ui.badge tone="ink">{{ trans_choice('academy.course.hours', $course->duration_hours, ['count' => $course->duration_hours]) }}</x-ui.badge></li>
            @endif
            @foreach ($course->languages ?? [] as $language)
                <li><x-ui.badge tone="ink">{{ Locales::nativeName($language) }}</x-ui.badge></li>
            @endforeach
            @if ($pillar)
                <li><x-ui.badge tone="ink">{{ $pillar->getAttribute('name') }}</x-ui.badge></li>
            @endif
        </ul>
        @if ($first)
            <x-slot:actions>
                <x-ui.button :href="route('enrolments.create', ['slug' => $translation->slug, 'cohort' => $first->id])" variant="action" size="lg">
                    {{ $first->enrolmentState() === CourseCohort::STATE_WAITLIST ? __('academy.enrol.join_waitlist') : __('academy.enrol.cta') }}
                </x-ui.button>
            </x-slot:actions>
        @endif
    </x-page-hero>

    <div class="mx-auto max-w-page px-4 pt-10 pb-16 sm:px-6">
        <div class="grid gap-12 lg:grid-cols-[1fr_24rem]">
            <div class="min-w-0 space-y-12" lang="{{ $translation->locale }}">
                @if (filled($translation->audience) || filled($translation->prerequisites))
                    <section aria-labelledby="course-audience" class="grid gap-6 sm:grid-cols-2">
                        <h2 id="course-audience" class="sr-only">{{ __('academy.field.audience') }}</h2>
                        @if (filled($translation->audience))
                            <div class="rounded-card border border-line bg-surface p-5">
                                <h3 class="kicker">{{ __('academy.field.audience') }}</h3>
                                <p class="mt-2 leading-relaxed whitespace-pre-line">{{ $translation->audience }}</p>
                            </div>
                        @endif
                        @if (filled($translation->prerequisites))
                            <div class="rounded-card border border-line bg-surface p-5">
                                <h3 class="kicker">{{ __('academy.field.prerequisites') }}</h3>
                                <p class="mt-2 leading-relaxed whitespace-pre-line">{{ $translation->prerequisites }}</p>
                            </div>
                        @endif
                    </section>
                @endif

                @if ($translation->outcomeList() !== [])
                    <section aria-labelledby="course-outcomes">
                        <h2 id="course-outcomes" class="heading-2">{{ __('academy.field.outcomes') }}</h2>
                        <ul class="mt-5 grid gap-2 sm:grid-cols-2">
                            @foreach ($translation->outcomeList() as $outcome)
                                <li class="flex items-start gap-3 rounded-control border border-line bg-surface px-4 py-3">
                                    <svg aria-hidden="true" class="mt-0.5 size-5 shrink-0 text-cyan-600 dark:text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                    <span>{{ $outcome }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if ($translation->syllabusList() !== [])
                    <section aria-labelledby="course-syllabus">
                        <h2 id="course-syllabus" class="heading-2">{{ __('academy.field.syllabus') }}</h2>
                        <ol class="relative mt-6 space-y-6 before:absolute before:inset-y-2 before:start-4 before:w-px before:bg-line">
                            @foreach ($translation->syllabusList() as $module)
                                <li class="relative flex gap-4">
                                    <span aria-hidden="true" class="relative grid size-8 shrink-0 place-items-center rounded-control bg-night-900 font-mono text-sm text-cyan-300 ring-4 ring-canvas">{{ sprintf('%02d', $loop->iteration) }}</span>
                                    <p class="pt-1 text-lg leading-relaxed">{{ $module }}</p>
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif

                @if (trim($body) !== '')
                    <section aria-labelledby="course-details">
                        <h2 id="course-details" class="heading-2">{{ __('academy.field.body') }}</h2>
                        <div class="prose-content mt-5 max-w-prose">{!! $body !!}</div>
                    </section>
                @endif

                @if ($instructors->isNotEmpty())
                    <section aria-labelledby="course-instructors">
                        <h2 id="course-instructors" class="heading-2">{{ __('academy.admin.instructors') }}</h2>
                        <ul class="mt-5 grid gap-3 sm:grid-cols-2">
                            @foreach ($instructors as $instructor)
                                <li class="flex items-center gap-3 rounded-card border border-line bg-surface p-4">
                                    <span aria-hidden="true" class="grid size-11 shrink-0 place-items-center rounded-full bg-night-900 font-display font-semibold text-cyan-300">{{ \App\Support\Initials::of($instructor->name) }}</span>
                                    <span>
                                        <span class="block font-semibold">{{ $instructor->name }}</span>
                                        @if ($instructor->title() !== '')
                                            <span class="block text-sm text-ink-muted">{{ $instructor->title() }}</span>
                                        @endif
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>

            <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start" aria-labelledby="course-cohorts">
                <section class="relative isolate overflow-hidden rounded-card border border-night-line bg-night-900 p-6 text-night-ink">
                    <x-hex-field id="course-hex" fade="top" class="absolute inset-x-0 top-0 -z-10 h-32 w-full text-cyan-500/20" />
                    <h2 id="course-cohorts" class="kicker [--color-eyebrow:var(--color-cyan-300)]">{{ __('academy.cohort.title') }}</h2>
                    @forelse ($upcoming as $cohort)
                        @php $state = $cohort->enrolmentState(); $left = $cohort->placesLeft(); @endphp
                        <article id="cohort-{{ $cohort->id }}" @class(['pt-5' => true, 'mt-5 border-t border-night-line' => ! $loop->first])>
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <h3 class="font-display text-lg font-semibold tracking-tight">{{ $cohort->name }}</h3>
                                <x-ui.status-pill :state="match ($state) { 'open' => 'ok', 'waitlist', 'soon' => 'maintenance', default => 'down' }" tone="night">{{ __('academy.state.'.$state) }}</x-ui.status-pill>
                            </div>
                            <dl class="mt-3 space-y-2 text-sm">
                                <div>
                                    <dt class="font-mono text-[11px] tracking-wider text-night-muted uppercase">{{ __('academy.cohort.dates') }}</dt>
                                    <dd class="mt-0.5">{{ __('academy.cohort.runs', ['start' => $calendar->format($cohort->starts_on), 'end' => $calendar->format($cohort->ends_on)]) }}</dd>
                                </div>
                                <div>
                                    <dt class="font-mono text-[11px] tracking-wider text-night-muted uppercase">{{ __('academy.field.mode') }}</dt>
                                    <dd class="mt-0.5">{{ $cohort->mode->label() }}@if ($cohort->office) · {{ PublicDirectory::shown($cohort->office, $locale)?->getAttribute('name') ?? $cohort->office->name() }}@endif</dd>
                                </div>
                                @if ($cohort->venueNote() !== '')
                                    <div>
                                        <dt class="font-mono text-[11px] tracking-wider text-night-muted uppercase">{{ __('academy.field.venue_note') }}</dt>
                                        <dd class="mt-0.5">{{ $cohort->venueNote() }}</dd>
                                    </div>
                                @endif
                                @if ($state === CourseCohort::STATE_SOON && $cohort->enrol_opens_at)
                                    <div>
                                        <dt class="font-mono text-[11px] tracking-wider text-night-muted uppercase">{{ __('academy.field.enrol_opens_at') }}</dt>
                                        <dd class="mt-0.5"><x-date :date="$cohort->enrol_opens_at" :time="true" /></dd>
                                    </div>
                                @elseif ($cohort->acceptsEnrolment())
                                    <div>
                                        <dt class="font-mono text-[11px] tracking-wider text-night-muted uppercase">{{ __('academy.field.enrol_closes_at') }}</dt>
                                        <dd class="mt-0.5"><x-date :date="$cohort->enrolClosesAt()" :time="true" /></dd>
                                    </div>
                                @endif
                                @if ($left !== null && $state === CourseCohort::STATE_OPEN)
                                    <div>
                                        <dt class="font-mono text-[11px] tracking-wider text-night-muted uppercase">{{ __('academy.field.capacity') }}</dt>
                                        <dd class="mt-0.5">{{ trans_choice('academy.cohort.places_left', $left, ['count' => $left]) }}</dd>
                                    </div>
                                @endif
                            </dl>
                            @if ($cohort->sessions->isNotEmpty())
                                <details class="mt-4 text-sm">
                                    <summary class="cursor-pointer font-medium text-cyan-300">{{ trans_choice('academy.cohort.sessions', $cohort->sessions->count(), ['count' => $cohort->sessions->count()]) }}</summary>
                                    <ol class="mt-3 space-y-2">
                                        @foreach ($cohort->sessions as $session)
                                            <li class="rounded-control border border-night-line px-3 py-2">
                                                <x-date :date="$session->starts_at" :time="true" />
                                                @if ($session->title() !== '')
                                                    <span class="block text-night-muted">{{ $session->title() }}</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ol>
                                    <p class="mt-3"><a href="{{ route('courses.ics', ['slug' => $translation->slug, 'cohort' => $cohort->id]) }}" class="text-cyan-300 underline underline-offset-4">{{ __('events.add_to_calendar') }}</a></p>
                                </details>
                            @endif
                            @if ($cohort->acceptsEnrolment())
                                <p class="mt-5">
                                    <x-ui.button :href="route('enrolments.create', ['slug' => $translation->slug, 'cohort' => $cohort->id])" variant="action" class="w-full">
                                        {{ $state === CourseCohort::STATE_WAITLIST ? __('academy.enrol.join_waitlist') : __('academy.enrol.cta') }}
                                    </x-ui.button>
                                </p>
                            @endif
                        </article>
                    @empty
                        <p class="mt-2 text-night-muted">{{ __('academy.cohort.none') }}</p>
                    @endforelse
                </section>
                @if ($course->partner)
                    <p class="font-mono text-xs text-ink-muted">{{ __('academy.field.partner') }}: {{ $course->partner }}</p>
                @endif
                @if ($past > 0)
                    <p class="font-mono text-xs text-ink-muted">{{ trans_choice('academy.cohort.past', $past, ['count' => $past]) }}</p>
                @endif
                <p class="text-sm"><a href="{{ route('certificates.verify') }}" class="text-link underline underline-offset-4">{{ __('academy.verify.title') }}</a></p>
            </aside>
        </div>
    </div>
</x-layouts.public>
