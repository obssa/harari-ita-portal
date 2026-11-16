@props(['tone' => 'neutral'])
{{-- ADR-0012: small mono labels with a tinted ground; each tone has its dark twin. --}}
<span {{ $attributes->class([
    'inline-flex items-center rounded-md px-2 py-0.5 font-mono text-[0.7rem] font-medium tracking-wide uppercase ring-1 ring-inset',
    'bg-primary-50 text-primary-800 ring-primary-200 dark:bg-primary-950 dark:text-primary-200 dark:ring-primary-800' => $tone === 'primary',
    'bg-cyan-50 text-cyan-800 ring-cyan-200 dark:bg-cyan-950 dark:text-cyan-200 dark:ring-cyan-800' => $tone === 'cyan',
    'bg-saffron-50 text-saffron-800 ring-saffron-200 dark:bg-saffron-950 dark:text-saffron-200 dark:ring-saffron-800' => $tone === 'saffron',
    'bg-ruby-50 text-ruby-700 ring-ruby-100 dark:bg-ruby-950 dark:text-ruby-200 dark:ring-ruby-800' => $tone === 'ruby',
    'bg-night-900 text-night-ink ring-night-700' => $tone === 'ink',
    'bg-surface-muted text-ink-muted ring-line' => $tone === 'neutral',
    'bg-success-50 text-success-800 ring-success-600/30' => $tone === 'success',
    'bg-warning-50 text-warning-800 ring-warning-600/30' => $tone === 'warning',
    'bg-danger-50 text-danger-800 ring-danger-600/30' => $tone === 'danger',
]) }}>{{ $slot }}</span>
