@props(['tone' => 'neutral'])
<span {{ $attributes->class([
    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold ring-1 ring-inset',
    'bg-primary-50 text-primary-800 ring-primary-200 dark:bg-primary-950 dark:text-primary-200 dark:ring-primary-800' => $tone === 'primary',
    'bg-cyan-50 text-cyan-800 ring-cyan-200 dark:bg-cyan-950 dark:text-cyan-200 dark:ring-cyan-800' => $tone === 'cyan',
    'bg-saffron-50 text-saffron-800 ring-saffron-200' => $tone === 'saffron',
    'bg-ruby-50 text-ruby-700 ring-ruby-100' => $tone === 'ruby',
    'bg-ink-brand text-white ring-ink-brand' => $tone === 'ink',
    'bg-surface-muted text-ink-muted ring-line' => $tone === 'neutral',
    'bg-success-50 text-success-800 ring-success-600/30' => $tone === 'success',
    'bg-warning-50 text-warning-800 ring-warning-600/30' => $tone === 'warning',
    'bg-danger-50 text-danger-800 ring-danger-600/30' => $tone === 'danger',
]) }}>{{ $slot }}</span>
