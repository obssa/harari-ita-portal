@props(['opportunity', 'translation', 'headingLevel' => 3])
@php
    /** @var \App\Domain\Opportunities\Models\Opportunity $opportunity */
    $state = $opportunity->state();
    $past = $opportunity->isPast();
    $left = max(0, (int) floor(now()->diffInHours($opportunity->closes_at, false)));
    $office = $opportunity->office ? \App\Domain\Organisation\Queries\PublicDirectory::shown($opportunity->office, app()->getLocale()) : null;
@endphp
{{-- DESIGN.md §4 opportunity card (ADR-0032): the type and reference in mono, the title, the issuing directorate, and at
     the foot the deadline in both calendars with the time left, said in words (never by colour alone). --}}
<article {{ $attributes->class(['glow-card group relative flex h-full flex-col gap-4 rounded-card border border-line bg-surface p-6 transition duration-200 hover:-translate-y-0.5 hover:border-cyan-500 hover:shadow-lg hover:shadow-night-950/5', 'opacity-90' => $past]) }} lang="{{ $translation->locale }}">
    <div class="flex flex-wrap items-start justify-between gap-2">
        <p class="flex flex-wrap items-center gap-2">
            <x-ui.badge :tone="$opportunity->type->tone()">{{ $opportunity->type->label() }}</x-ui.badge>
            @if (filled($opportunity->reference))
                <span class="font-mono text-xs text-ink-muted" dir="ltr">{{ $opportunity->reference }}</span>
            @endif
        </p>
        <x-ui.badge :tone="\App\Domain\Opportunities\Models\Opportunity::stateTone($state)">{{ __('opportunities.state.'.$state) }}</x-ui.badge>
    </div>
    <div>
        <h{{ $headingLevel }} class="font-display text-xl leading-snug font-semibold tracking-tight">
            <a href="{{ route('opportunities.show', ['locale' => $translation->locale, 'slug' => $translation->slug]) }}" class="after:absolute after:inset-0 group-hover:underline underline-offset-4">{{ $translation->title }}</a>
        </h{{ $headingLevel }}>
        @if (filled($translation->summary))
            <p class="mt-2 line-clamp-3 text-ink-muted">{{ $translation->summary }}</p>
        @endif
    </div>
    <dl class="mt-auto grid gap-1 border-t border-line pt-4 font-mono text-xs text-ink-muted">
        <div class="flex flex-wrap gap-x-2">
            <dt>{{ $state === \App\Domain\Opportunities\Models\Opportunity::STATE_UPCOMING ? __('opportunities.field.opens_at') : __('opportunities.field.closes_at') }}:</dt>
            <dd class="text-ink"><x-date :date="$state === \App\Domain\Opportunities\Models\Opportunity::STATE_UPCOMING ? $opportunity->opens_at : $opportunity->closes_at" :time="true" /></dd>
        </div>
        @if ($opportunity->isOpen())
            <div class="flex flex-wrap gap-x-2">
                <dt class="sr-only">{{ __('opportunities.time_left') }}</dt>
                <dd @class(['font-semibold', 'text-warning-800 dark:text-saffron-300' => $state === \App\Domain\Opportunities\Models\Opportunity::STATE_CLOSING, 'text-ink' => $state !== \App\Domain\Opportunities\Models\Opportunity::STATE_CLOSING])>
                    {{ match (true) { $left >= 48 => trans_choice('opportunities.closes_in_days', intdiv($left, 24), ['count' => intdiv($left, 24)]), $left >= 1 => trans_choice('opportunities.closes_in_hours', $left, ['count' => $left]), default => __('opportunities.closes_within_hour') } }}
                </dd>
            </div>
        @endif
        @if ($office)
            <div class="flex flex-wrap gap-x-2"><dt>{{ __('opportunities.field.office') }}:</dt><dd class="text-ink">{{ $office->getAttribute('name') }}</dd></div>
        @endif
    </dl>
</article>
