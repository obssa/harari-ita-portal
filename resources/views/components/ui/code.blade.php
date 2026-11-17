@props(['label' => null, 'wrap' => false])
{{-- ADR-0013: a code or API snippet on night, the label in mono with three dots in the logo's colours. Pass escaped text. --}}
<figure {{ $attributes->class('overflow-hidden rounded-card border border-night-line bg-night-950 text-night-ink') }}>
    @if ($label)
        <figcaption class="flex items-center gap-1.5 border-b border-night-line px-4 py-2 font-mono text-xs text-night-muted">
            <span aria-hidden="true" class="size-2 rounded-full bg-ruby-500"></span>
            <span aria-hidden="true" class="size-2 rounded-full bg-saffron-500"></span>
            <span aria-hidden="true" class="size-2 rounded-full bg-cyan-500"></span>
            <span class="ms-2">{{ $label }}</span>
        </figcaption>
    @endif
    {{-- Focusable so a long line can be scrolled with the keyboard (WCAG 2.1.1). --}}
    <pre tabindex="0" @if ($label) aria-label="{{ $label }}" @endif @class(['p-4 font-mono text-sm leading-relaxed focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400', 'overflow-x-auto' => ! $wrap, 'whitespace-pre-wrap break-words' => $wrap])><code>{{ $slot }}</code></pre>
</figure>
