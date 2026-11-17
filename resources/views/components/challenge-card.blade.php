@props(['challenge', 'translation', 'headingLevel' => 2])
@use('App\Domain\Innovation\Models\Challenge')
@php
    $phase = $challenge->phase();
    $state = match ($phase) {
        Challenge::PHASE_REGISTRATION => 'ok',
        Challenge::PHASE_BUILDING, Challenge::PHASE_UPCOMING => 'maintenance',
        default => 'degraded',
    };
    $deadline = match ($phase) {
        Challenge::PHASE_UPCOMING => [__('innovation.field.registration_opens_at'), $challenge->registration_opens_at],
        Challenge::PHASE_REGISTRATION => [__('innovation.field.registration_closes_at'), $challenge->registration_closes_at],
        Challenge::PHASE_BUILDING => [__('innovation.field.submission_closes_at'), $challenge->submission_closes_at],
        default => null,
    };
@endphp
{{-- ADR-0022: a challenge as a card: the phase as a status pill, the title, the pitch, the next date and, for live
     challenges, the first prize line. --}}
<article {{ $attributes->class('group relative flex h-full flex-col gap-4 rounded-card border border-line bg-surface p-7 transition duration-200 hover:-translate-y-0.5 hover:border-cyan-500 hover:shadow-lg hover:shadow-night-950/5') }} lang="{{ $translation->locale }}">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="kicker">{{ __('innovation.challenges.kicker') }}</p>
        @if ($phase === Challenge::PHASE_RESULTS)
            <x-ui.badge tone="success">{{ __('innovation.phase.results') }}</x-ui.badge>
        @else
            <x-ui.status-pill :state="$state">{{ __('innovation.phase.'.$phase) }}</x-ui.status-pill>
        @endif
    </div>
    <h{{ $headingLevel }} class="font-display text-2xl font-semibold tracking-tight">
        <a href="{{ route('challenges.show', ['locale' => $translation->locale, 'slug' => $translation->slug]) }}" class="after:absolute after:inset-0 group-hover:underline underline-offset-4">{{ $translation->getAttribute('title') }}</a>
    </h{{ $headingLevel }}>
    @if (filled($translation->getAttribute('summary')))
        <p class="line-clamp-3 text-ink-muted">{{ $translation->getAttribute('summary') }}</p>
    @endif
    <dl class="mt-auto grid gap-1 border-t border-line pt-4 font-mono text-xs text-ink-muted">
        @if ($deadline)
            <div class="flex flex-wrap gap-x-2"><dt>{{ $deadline[0] }}:</dt><dd class="text-ink"><x-date :date="$deadline[1]" :time="true" /></dd></div>
        @endif
        @php $prize = $translation->prizeList()[0] ?? null; @endphp
        @if ($prize)
            <div class="flex flex-wrap gap-x-2"><dt>{{ __('innovation.field.prizes') }}:</dt><dd class="text-ink">{{ $prize }}</dd></div>
        @endif
    </dl>
</article>
