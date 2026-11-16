@props(['id' => 'hex-field', 'fade' => 'radial'])
{{-- ADR-0007: the logo's hexagon field as a background texture: thin honeycomb lines in currentColor, faded out
     towards the edges. Purely decorative; never placed behind text without enough contrast. --}}
@php
    $mask = match ($fade) {
        'right' => 'linear-gradient(to left, #000 0%, transparent 75%)',
        'left' => 'linear-gradient(to right, #000 0%, transparent 75%)',
        'top' => 'linear-gradient(to bottom, #000 0%, transparent 80%)',
        'none' => 'none',
        default => 'radial-gradient(ellipse at 70% 40%, #000 0%, transparent 70%)',
    };
@endphp
<svg aria-hidden="true" focusable="false" {{ $attributes->merge(['class' => 'pointer-events-none']) }} style="-webkit-mask-image: {{ $mask }}; mask-image: {{ $mask }}">
    <defs>
        <pattern id="{{ $id }}" width="28" height="48" patternUnits="userSpaceOnUse">
            <path d="M14 0 28 8v16l-14 8-14-8V8zM14 32v16" fill="none" stroke="currentColor" stroke-width="1"/>
        </pattern>
    </defs>
    <rect width="100%" height="100%" fill="url(#{{ $id }})"/>
</svg>
