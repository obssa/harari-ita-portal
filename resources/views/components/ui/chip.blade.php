@props(['href', 'active' => false])
{{-- ADR-0012: filter chips; the chosen one is teal, the rest hairline. --}}
<a href="{{ $href }}" @if ($active) aria-current="page" @endif
   {{ $attributes->class([
       'inline-flex items-center rounded-control border px-3.5 py-2 text-sm font-medium transition-colors duration-150',
       'border-primary-700 bg-primary-700 text-white dark:border-cyan-300 dark:bg-cyan-300 dark:text-ink-brand' => $active,
       'border-line bg-surface text-ink hover:border-cyan-500' => ! $active,
   ]) }}>{{ $slot }}</a>
