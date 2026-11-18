@props(['course', 'translation', 'headingLevel' => 2])
@use('App\Domain\Translation\Support\Locales')
@php
    /** @var \App\Domain\Academy\Models\Course $course */
    $next = $course->nextCohort();
@endphp
{{-- ADR-0031: a course as a card: the level in mono, the title and summary, the mode, length and languages as chips,
     and at the foot the next cohort in both calendars with whether it is open. --}}
<article {{ $attributes->class('glow-card group relative flex h-full flex-col gap-4 rounded-card border border-line bg-surface p-6 transition duration-200 hover:-translate-y-0.5 hover:border-cyan-500 hover:shadow-lg hover:shadow-night-950/5') }} lang="{{ $translation->locale }}">
    <div class="flex items-start justify-between gap-3">
        <p class="kicker">{{ $course->level->label() }}</p>
        @if ($next && in_array($next->enrolmentState(), [\App\Domain\Academy\Models\CourseCohort::STATE_OPEN, \App\Domain\Academy\Models\CourseCohort::STATE_WAITLIST], true))
            <x-ui.status-pill state="ok">{{ __('academy.state.'.$next->enrolmentState()) }}</x-ui.status-pill>
        @endif
    </div>
    <div>
        <h{{ $headingLevel }} class="font-display text-xl leading-snug font-semibold tracking-tight">
            <a href="{{ route('courses.show', ['locale' => $translation->locale, 'slug' => $translation->slug]) }}" class="after:absolute after:inset-0 group-hover:underline underline-offset-4">{{ $translation->title }}</a>
        </h{{ $headingLevel }}>
        @if (filled($translation->summary))
            <p class="mt-2 line-clamp-3 text-ink-muted">{{ $translation->summary }}</p>
        @endif
    </div>
    <ul class="flex flex-wrap gap-1.5" aria-label="{{ __('academy.course.facts') }}">
        <li><x-ui.badge tone="cyan">{{ $course->mode->label() }}</x-ui.badge></li>
        @if ($course->duration_hours)
            <li><x-ui.badge>{{ trans_choice('academy.course.hours', $course->duration_hours, ['count' => $course->duration_hours]) }}</x-ui.badge></li>
        @endif
        @foreach ($course->languages ?? [] as $language)
            <li><x-ui.badge>{{ Locales::nativeName($language) }}</x-ui.badge></li>
        @endforeach
    </ul>
    <dl class="mt-auto grid gap-1 border-t border-line pt-4 font-mono text-xs text-ink-muted">
        <div class="flex flex-wrap gap-x-2">
            <dt>{{ __('academy.course.next_cohort') }}:</dt>
            <dd class="text-ink">@if ($next)<x-date :date="$next->starts_on" />@else{{ __('academy.course.no_cohort') }}@endif</dd>
        </div>
        @if ($course->partner)
            <div class="flex flex-wrap gap-x-2"><dt>{{ __('academy.field.partner') }}:</dt><dd class="text-ink">{{ $course->partner }}</dd></div>
        @endif
    </dl>
</article>
