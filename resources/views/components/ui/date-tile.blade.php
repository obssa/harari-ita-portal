@props(['date'])
@php
    $calendar = app(\App\Services\Calendar\EthiopianCalendar::class);
    $value = \Carbon\CarbonImmutable::instance($date)->setTimezone(\App\Services\Calendar\EthiopianCalendar::timezone());
    $ethiopian = $calendar->toEthiopian($value);
@endphp
{{-- ADR-0012: the Ethiopian month in mono on a night band, the day large in the display face, the Gregorian date below. --}}
<time datetime="{{ $value->toDateString() }}" {{ $attributes->class('inline-flex w-21 flex-col overflow-hidden rounded-card border border-line bg-surface text-center') }}>
    <span class="bg-night-900 px-1 py-1 font-mono text-[0.7rem] tracking-wide text-cyan-300 uppercase">{{ $ethiopian->monthName() }}</span>
    <span class="pt-1.5 font-display text-3xl font-bold leading-none">{{ $ethiopian->day }}</span>
    <span class="px-1 pt-1 pb-2 font-mono text-[0.65rem] text-ink-muted">{{ $calendar->formatGregorian($value) }}</span>
</time>
