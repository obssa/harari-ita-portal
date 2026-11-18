@props(['type' => null, 'lead' => false, 'seed' => ''])
@php
    // ADR-0012: a story without a photo gets a night tile with its type's tint. ADR-0051: instead of the emblem alone,
    // each story draws its own arrangement of the Jugol circuit (turned and placed by the story's id), so a page of
    // stories without photos reads as a set of distinct tiles, with the emblem small in the corner.
    $tint = match ($type?->tone()) {
        'cyan' => 'from-cyan-700/70',
        'ruby' => 'from-ruby-700/70',
        'saffron' => 'from-saffron-600/60',
        'ink' => 'from-night-950',
        default => 'from-primary-700/70',
    };
    $hash = crc32((string) $seed);
    $turn = ['', '-scale-x-100', '-scale-y-100', 'rotate-180'][$hash % 4];
    $place = ['-start-1/4 -top-1/4', '-end-1/4 -top-1/3', '-start-1/3 -bottom-1/4', '-end-1/4 -bottom-1/3'][intdiv($hash, 4) % 4];
@endphp
<div aria-hidden="true" {{ $attributes->class(['relative isolate overflow-hidden bg-night-900 bg-linear-to-br to-night-900', $tint]) }}>
    <x-hex-field :id="'card-hex-'.($lead ? 'lead' : 'item')" fade="radial" class="absolute inset-0 -z-10 size-full text-cyan-400/20 transition-transform duration-700 group-hover:scale-110" />
    <x-circuit class="absolute -z-10 h-[150%] w-[150%] {{ $place }} {{ $turn }} text-cyan-300/30 transition-transform duration-700 group-hover:scale-105" />
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,rgb(255_255_255/0.08),transparent_55%)]"></div>
    <img src="/brand/emblem-128.webp" alt="" width="128" height="128" @class(['absolute end-4 bottom-4 drop-shadow-xl', 'size-10' => ! $lead, 'size-14' => $lead])>
</div>
