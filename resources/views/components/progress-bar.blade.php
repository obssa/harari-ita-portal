@props(['value'])
@php $value = max(0, min(100, (int) $value)); @endphp
<div {{ $attributes->class('text-sm') }}>
    <div class="flex justify-between text-xs text-ink-muted">
        <span>{{ __('projects.progress') }}</span>
        <span class="tabular-nums" dir="ltr">{{ $value }}%</span>
    </div>
    <div class="mt-1 h-2 overflow-hidden rounded-sm bg-surface-muted ring-1 ring-line ring-inset" role="progressbar" aria-valuenow="{{ $value }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ __('projects.progress') }}">
        <div class="h-full rounded-sm bg-cyan-600 dark:bg-cyan-400" style="width: {{ $value }}%"></div>
    </div>
</div>
