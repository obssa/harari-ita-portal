@props(['tone' => 'info', 'title' => null])
@php
    // ADR-0013: a coloured edge and icon on a tinted ground; on dark, the ground turns night and the edge keeps the tone.
    $ground = match ($tone) {
        'success' => 'border-success-600 bg-success-50 text-success-800',
        'warning' => 'border-warning-600 bg-warning-50 text-warning-800',
        'danger' => 'border-danger-600 bg-danger-50 text-danger-800',
        default => 'border-cyan-600 bg-cyan-50 text-cyan-900',
    };
    $icon = match ($tone) {
        'success' => 'M20 6 9 17l-5-5',
        'warning', 'danger' => 'M12 8v5m0 3.5v.5M10.3 3.9 2.4 18a2 2 0 0 0 1.7 3h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z',
        default => 'M12 16v-5m0-3.5V7M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Z',
    };
@endphp
<div role="{{ $tone === 'danger' ? 'alert' : 'status' }}" {{ $attributes->class(['flex gap-3 rounded-control border-s-4 px-4 py-3 dark:bg-night-900 dark:text-night-ink', $ground]) }}>
    <svg aria-hidden="true" class="mt-0.5 size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icon }}"/></svg>
    <div class="min-w-0">
        @if ($title)
            <p class="font-semibold">{{ $title }}</p>
        @endif
        <div @class(['text-sm' => $title])>{{ $slot }}</div>
    </div>
</div>
