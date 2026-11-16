@props(['type' => null, 'lead' => false])
@php
    // ADR-0012: a story without a photo gets a night tile with the hexagon field, the emblem and its type's tint.
    $tint = match ($type?->tone()) {
        'cyan' => 'from-cyan-700/70',
        'ruby' => 'from-ruby-700/70',
        'saffron' => 'from-saffron-600/60',
        'ink' => 'from-night-950',
        default => 'from-primary-700/70',
    };
@endphp
<div aria-hidden="true" {{ $attributes->class(['relative isolate flex size-full items-center justify-center overflow-hidden bg-night-900 bg-linear-to-br to-night-900', $tint]) }}>
    <x-hex-field :id="'card-hex-'.($lead ? 'lead' : 'item')" fade="radial" class="absolute inset-0 -z-10 size-full text-cyan-400/25 transition-transform duration-700 group-hover:scale-110" />
    <img src="{{ $lead ? '/brand/emblem-256.webp' : '/brand/emblem-128.webp' }}" alt="" width="112" height="112" @class(['drop-shadow-xl', 'size-16' => ! $lead, 'size-28' => $lead])>
</div>
