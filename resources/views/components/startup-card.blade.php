@props(['startup', 'translation', 'headingLevel' => 2])
@use('App\Domain\Innovation\Enums\LabelStatus')
@php
    $locale = app()->getLocale();
    $sector = $startup->sector?->name($locale) ?: null;
@endphp
{{-- ADR-0020: a startup as a card: logo (or its initials on night), sector in mono, name, pitch, stage and the label
     claim (verified by the Agency, or self-declared). --}}
<article {{ $attributes->class('group relative flex h-full flex-col gap-4 rounded-card border border-line bg-surface p-6 transition duration-200 hover:-translate-y-0.5 hover:border-cyan-500 hover:shadow-lg hover:shadow-night-950/5') }} lang="{{ $translation->locale }}">
    <div class="flex items-center gap-4">
        @if ($startup->logo)
            <x-picture :media="$startup->logo" preset="square" sizes="4rem" class="size-14 shrink-0 rounded-control bg-white object-contain p-1 ring-1 ring-line" />
        @else
            <span aria-hidden="true" class="grid size-14 shrink-0 place-items-center rounded-control bg-night-900 font-display text-xl font-bold text-cyan-300">{{ \App\Support\Initials::of($translation->name) }}</span>
        @endif
        <div class="min-w-0">
            <p class="kicker truncate">{{ $sector ?? __('innovation.startups.kicker') }}</p>
            <h{{ $headingLevel }} class="mt-1 font-display text-xl leading-snug font-semibold tracking-tight">
                <a href="{{ route('startups.show', ['locale' => $translation->locale, 'slug' => $translation->slug]) }}" class="after:absolute after:inset-0 group-hover:underline underline-offset-4">{{ $translation->name }}</a>
            </h{{ $headingLevel }}>
        </div>
    </div>
    @if (filled($translation->summary))
        <p class="line-clamp-3 text-ink-muted">{{ $translation->summary }}</p>
    @endif
    <p class="mt-auto flex flex-wrap gap-1.5">
        <x-ui.badge>{{ $startup->stage->label() }}</x-ui.badge>
        @if ($startup->label_status === LabelStatus::Verified)
            <x-ui.badge tone="success">{{ __('innovation.label.verified') }}</x-ui.badge>
        @elseif ($startup->label_status === LabelStatus::SelfDeclared)
            <x-ui.badge tone="saffron">{{ __('innovation.label.self_declared') }}</x-ui.badge>
        @endif
    </p>
</article>
