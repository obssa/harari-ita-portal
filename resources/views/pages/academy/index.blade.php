@use('App\Domain\Academy\Enums\CourseLevel')
@use('App\Domain\Academy\Enums\CourseMode')
@use('App\Domain\Organisation\Queries\PublicDirectory')
@use('App\Domain\Translation\Support\Locales')
@php
    $locale = app()->getLocale();
    $select = 'mt-1 block w-full rounded-control border border-line bg-surface px-3 py-2 focus:border-cyan-600 focus:outline-none focus:ring-2 focus:ring-cyan-500/30';
    $filtered = array_filter($filters) !== [];
@endphp
<x-layouts.public :title="__('academy.title')" :description="__('academy.description')">
    {{-- ADR-0031: the Academy opens on night with the certificate check; then the cohorts calendar and the catalogue. --}}
    <x-page-hero :title="__('academy.title')" :lead="__('academy.description')" :kicker="__('academy.kicker')" tone="night">
        <form method="get" action="{{ route('certificates.verify') }}" class="flex max-w-xl flex-wrap gap-2">
            <label for="certificate-code" class="sr-only">{{ __('academy.verify.code_label') }}</label>
            <input id="certificate-code" type="text" name="code" maxlength="20" placeholder="{{ __('academy.verify.placeholder') }}" autocomplete="off" spellcheck="false" dir="ltr"
                   class="min-w-0 flex-1 rounded-control border border-night-line bg-night-950 px-5 py-3 font-mono text-base text-night-ink uppercase placeholder:text-night-muted placeholder:normal-case focus:border-cyan-400 focus:ring-2 focus:ring-cyan-400/40 focus:outline-none">
            <x-ui.button type="submit" variant="action" size="lg">{{ __('academy.verify.submit') }}</x-ui.button>
        </form>
        @if ($learners > 0)
            <p class="font-mono text-xs tracking-wide text-night-muted uppercase">{{ trans_choice('academy.learners_certified', $learners, ['count' => \Illuminate\Support\Number::format($learners, locale: 'en')]) }}</p>
        @endif
    </x-page-hero>

    <div class="mx-auto max-w-page px-4 pt-2 pb-16 sm:px-6">
        <section aria-labelledby="academy-calendar" class="mt-10">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="kicker">{{ __('academy.calendar.kicker') }}</p>
                    <h2 id="academy-calendar" class="heading-2 mt-1">{{ __('academy.calendar.title') }}</h2>
                </div>
            </div>
            @if ($calendar->isEmpty())
                <x-ui.empty-state class="mt-6">{{ __('academy.calendar.empty') }}</x-ui.empty-state>
            @else
                <ol class="mt-6 divide-y divide-line overflow-hidden rounded-card border border-line bg-surface">
                    @foreach ($calendar as $row)
                        @php $state = $row['cohort']->enrolmentState(); @endphp
                        <li class="relative flex flex-wrap items-center gap-4 p-4 hover:bg-surface-muted sm:flex-nowrap sm:p-5" lang="{{ $row['translation']->locale }}">
                            <x-ui.date-tile :date="$row['cohort']->starts_on" class="shrink-0" />
                            <div class="min-w-0 flex-1">
                                <p class="font-mono text-xs tracking-wide text-ink-muted uppercase">{{ $row['cohort']->mode->label() }} · {{ $row['cohort']->name }}</p>
                                <h3 class="mt-1 font-display text-lg font-semibold tracking-tight">
                                    <a href="{{ route('courses.show', ['locale' => $row['translation']->locale, 'slug' => $row['translation']->slug]) }}#cohort-{{ $row['cohort']->id }}" class="after:absolute after:inset-0 hover:underline underline-offset-4">{{ $row['translation']->title }}</a>
                                </h3>
                                <p class="mt-1 text-sm text-ink-muted">
                                    {{ __('academy.cohort.runs', ['start' => app(\App\Services\Calendar\EthiopianCalendar::class)->format($row['cohort']->starts_on), 'end' => app(\App\Services\Calendar\EthiopianCalendar::class)->format($row['cohort']->ends_on)]) }}
                                    @if ($row['cohort']->office)
                                        · {{ PublicDirectory::shown($row['cohort']->office, $locale)?->getAttribute('name') ?? $row['cohort']->office->name() }}
                                    @endif
                                </p>
                            </div>
                            <x-ui.badge :tone="match ($state) { 'open' => 'success', 'waitlist', 'soon' => 'warning', default => 'neutral' }">{{ __('academy.state.'.$state) }}</x-ui.badge>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>

        <section aria-labelledby="academy-courses" class="mt-14">
            <p class="kicker">{{ __('academy.catalogue.kicker') }}</p>
            <h2 id="academy-courses" class="heading-2 mt-1">{{ __('academy.catalogue.title') }}</h2>

            <form method="get" action="{{ route('academy.index') }}" class="mt-6 grid gap-4 rounded-card border border-line bg-surface p-4 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_1fr_auto] lg:items-end">
                <div>
                    <label for="filter-level" class="kicker">{{ __('academy.field.level') }}</label>
                    <select id="filter-level" name="level" class="{{ $select }}">
                        <option value="">{{ __('services.filter.any') }}</option>
                        @foreach (CourseLevel::options() as $value => $label)
                            <option value="{{ $value }}" @selected($filters['level'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="filter-mode" class="kicker">{{ __('academy.field.mode') }}</label>
                    <select id="filter-mode" name="mode" class="{{ $select }}">
                        <option value="">{{ __('services.filter.any') }}</option>
                        @foreach (CourseMode::options() as $value => $label)
                            <option value="{{ $value }}" @selected($filters['mode'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="filter-language" class="kicker">{{ __('academy.field.language') }}</label>
                    <select id="filter-language" name="language" class="{{ $select }}">
                        <option value="">{{ __('services.filter.any') }}</option>
                        @foreach ($languages as $language)
                            <option value="{{ $language }}" @selected($filters['language'] === $language)>{{ Locales::nativeName($language) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="filter-pillar" class="kicker">{{ __('projects.pillar') }}</label>
                    <select id="filter-pillar" name="pillar" class="{{ $select }}">
                        <option value="">{{ __('projects.all_pillars') }}</option>
                        @foreach ($pillars as $id => $name)
                            <option value="{{ $id }}" @selected($filters['pillar'] === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <x-ui.button type="submit">{{ __('services.filter.apply') }}</x-ui.button>
                    @if ($filtered)
                        <a href="{{ route('academy.index') }}" class="text-sm text-link underline-offset-4 hover:underline">{{ __('services.filter.clear') }}</a>
                    @endif
                </div>
            </form>

            @if ($courses->isEmpty())
                <x-ui.empty-state class="mt-8">{{ $filtered ? __('academy.catalogue.none_match') : __('academy.catalogue.empty') }}</x-ui.empty-state>
            @else
                <p class="mt-8 font-mono text-xs tracking-wide text-ink-muted uppercase" role="status">{{ trans_choice('academy.catalogue.count', $courses->count(), ['count' => $courses->count()]) }}</p>
                <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($courses as $course)
                        @php $shown = PublicDirectory::shown($course, $locale); @endphp
                        @continue($shown === null)
                        <li><x-course-card :course="$course" :translation="$shown" :heading-level="3" /></li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</x-layouts.public>
