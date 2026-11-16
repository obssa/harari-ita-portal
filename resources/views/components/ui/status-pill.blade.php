@props(['state' => 'ok'])
@php
    // ADR-0013: a service's state as a mono pill with a coloured dot; anything other than "ok" pulses (not under reduced motion).
    $dot = match ($state) {
        'degraded' => 'bg-warning-600',
        'down' => 'bg-danger-600',
        'maintenance' => 'bg-cyan-600',
        default => 'bg-success-600',
    };
@endphp
<span {{ $attributes->class('inline-flex items-center gap-2 rounded-control border border-line bg-surface px-2.5 py-1 font-mono text-xs tracking-wide uppercase') }}>
    <span aria-hidden="true" class="relative flex size-2">
        @if ($state !== 'ok')
            <span class="absolute inline-flex size-full animate-ping rounded-full opacity-60 motion-reduce:animate-none {{ $dot }}"></span>
        @endif
        <span class="relative inline-flex size-2 rounded-full {{ $dot }}"></span>
    </span>
    {{ $slot }}
</span>
