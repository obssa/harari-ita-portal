@props(['label', 'value', 'source' => null])
{{-- ADR-0013: one figure: a mono label, the value in the display face with tabular numerals and, always for a public
     figure, its source. Figures come from live data only (CLAUDE.md). --}}
<div {{ $attributes->class('rounded-card border border-line bg-surface p-5') }}>
    <p class="kicker">{{ $label }}</p>
    <p class="mt-2 font-display text-4xl font-bold tracking-tight tabular-nums">{{ $value }}</p>
    @if ($source)
        <p class="mt-2 font-mono text-xs text-ink-muted">{{ $source }}</p>
    @endif
    {{ $slot }}
</div>
