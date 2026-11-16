@props(['href' => null, 'variant' => 'primary', 'size' => 'md', 'type' => 'button'])
@php
    // ADR-0012: squared controls (radius-control), semibold; teal primary, the orange action, hairline secondary.
    $classes = \Illuminate\Support\Arr::toCssClasses([
        'inline-flex items-center justify-center gap-2 rounded-control font-semibold transition-colors duration-150 focus-visible:outline-3 disabled:opacity-50',
        'px-5 py-2.5 text-sm' => $size === 'md',
        'px-3.5 py-1.5 text-sm' => $size === 'sm',
        'px-6 py-3 text-base' => $size === 'lg',
        'bg-primary-700 text-white hover:bg-primary-800' => $variant === 'primary',
        // The one action per view: the logo's orange, always with ink text (ADR-0007).
        'bg-saffron-500 text-ink-brand hover:bg-saffron-400' => $variant === 'action',
        'bg-cyan-700 text-white hover:bg-cyan-800' => $variant === 'accent',
        'border border-line bg-surface text-ink hover:border-cyan-500 hover:bg-surface-muted' => $variant === 'secondary',
        'border border-night-line text-night-ink hover:border-cyan-400 hover:bg-white/5' => $variant === 'light',
        'text-link hover:bg-surface-muted' => $variant === 'ghost',
    ]);
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
