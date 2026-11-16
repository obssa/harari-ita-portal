@props(['title' => null])
{{-- ADR-0013: an empty list says so calmly: a dashed card, the hexagon mark with an open centre, the message and an
     optional next step (the `action` slot). --}}
<div {{ $attributes->class('flex flex-col items-center gap-4 rounded-card border border-dashed border-line bg-surface px-6 py-12 text-center') }}>
    <svg aria-hidden="true" class="size-14 text-cyan-600 dark:text-cyan-400" viewBox="0 0 56 56" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round">
        <path d="M28 4 49 16v24L28 52 7 40V16z"/>
        <path d="M28 19l7.8 4.5v9L28 37l-7.8-4.5v-9z" class="text-saffron-500" stroke-dasharray="3 3"/>
    </svg>
    @if ($title)
        <p class="font-display text-lg font-semibold tracking-tight">{{ $title }}</p>
    @endif
    <p class="max-w-prose text-ink-muted">{{ $slot }}</p>
    @isset($action)
        <div class="flex flex-wrap justify-center gap-2">{{ $action }}</div>
    @endisset
</div>
