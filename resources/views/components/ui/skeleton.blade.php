@props(['lines' => 3, 'media' => true])
{{-- ADR-0013: a placeholder while something loads; decorative (the region that loads carries aria-busy). --}}
<div aria-hidden="true" {{ $attributes->class('animate-pulse space-y-3 motion-reduce:animate-none') }}>
    @if ($media)
        <div class="aspect-[16/10] rounded-card bg-surface-muted ring-1 ring-line ring-inset"></div>
    @endif
    @for ($i = 0; $i < $lines; $i++)
        <div @class(['h-3 rounded-sm bg-surface-muted', 'w-2/3' => $i % 3 === 2, 'w-5/6' => $i % 3 === 1, 'w-full' => $i % 3 === 0])></div>
    @endfor
</div>
