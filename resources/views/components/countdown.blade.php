@props(['until', 'label', 'tone' => 'day'])
@php
    // ADR-0022: the time left to a deadline as days, hours and minutes. The server writes the figures; on cached
    // pages resources/js/countdown.js corrects them each minute. It never announces itself (no aria-live): the
    // deadline itself is in the <time> beneath, in both calendars.
    $seconds = max(0, $until->getTimestamp() - now()->getTimestamp());
    $parts = ['days' => intdiv($seconds, 86400), 'hours' => intdiv($seconds % 86400, 3600), 'minutes' => intdiv($seconds % 3600, 60)];
    $night = $tone === 'night';
@endphp
<div {{ $attributes->class('countdown') }} data-countdown data-until="{{ $until->toIso8601String() }}">
    <p @class(['kicker', '[--color-eyebrow:var(--color-cyan-300)]' => $night])>{{ $label }}</p>
    <dl class="mt-3 grid grid-cols-3 gap-2">
        @foreach ($parts as $unit => $value)
            <div @class(['flex flex-col-reverse rounded-control px-2 py-3 text-center', 'bg-night-950/60 ring-1 ring-night-line' => $night, 'border border-line bg-surface' => ! $night])>
                <dt @class(['mt-1 font-mono text-[11px] tracking-wider uppercase', 'text-night-muted' => $night, 'text-ink-muted' => ! $night])>{{ __("innovation.countdown.{$unit}") }}</dt>
                <dd @class(['font-display text-3xl font-bold tracking-tight tabular-nums', 'text-night-ink' => $night, 'text-ink' => ! $night]) data-part="{{ $unit }}">{{ sprintf('%02d', $value) }}</dd>
            </div>
        @endforeach
    </dl>
    <p @class(['mt-3 text-sm', 'text-night-muted' => $night, 'text-ink-muted' => ! $night])><x-date :date="$until" :time="true" /></p>
</div>
