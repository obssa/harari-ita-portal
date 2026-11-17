@props(['value', 'label' => null, 'caption' => null, 'tone' => 'day'])
@php
    $value = max(0, min(100, (int) $value));
    $caption ??= __('projects.progress');
    $night = $tone === 'night';
@endphp
{{-- The visible caption says what is measured; the label names it for screen readers when several bars share a page. --}}
<div {{ $attributes->class('text-sm') }}>
    <div @class(['flex justify-between text-xs', 'text-night-muted' => $night, 'text-ink-muted' => ! $night])>
        <span>{{ $caption }}</span>
        <span class="tabular-nums" dir="ltr">{{ $value }}%</span>
    </div>
    <div @class(['mt-1 h-2 overflow-hidden rounded-sm ring-1 ring-inset', 'bg-night-950 ring-night-line' => $night, 'bg-surface-muted ring-line' => ! $night])
         role="progressbar" aria-valuenow="{{ $value }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ $label ?? $caption }}">
        <div @class(['h-full rounded-sm', 'bg-cyan-400' => $night, 'bg-cyan-600 dark:bg-cyan-400' => ! $night]) style="width: {{ $value }}%"></div>
    </div>
</div>
