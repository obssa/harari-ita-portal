@props(['steps' => [], 'current' => 0])
{{-- ADR-0013: the steps of a process, numbered in mono; done steps are teal with a tick, the current one has a cyan
     frame and aria-current="step". --}}
<ol {{ $attributes->class('flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4') }}>
    @foreach ($steps as $i => $label)
        <li @if ($i === $current) aria-current="step" @endif class="flex min-w-0 items-center gap-3 sm:flex-1">
            <span @class([
                'grid size-8 shrink-0 place-items-center rounded-control font-mono text-sm',
                'bg-primary-700 text-white dark:bg-cyan-300 dark:text-ink-brand' => $i < $current,
                'border-2 border-cyan-600 font-semibold text-cyan-800 dark:border-cyan-400 dark:text-cyan-200' => $i === $current,
                'border border-line text-ink-muted' => $i > $current,
            ])>
                @if ($i < $current)
                    <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    <span class="sr-only">{{ __('ui.step_done') }}</span>
                @else
                    {{ sprintf('%02d', $i + 1) }}
                @endif
            </span>
            <span @class(['text-sm font-medium', 'text-ink' => $i <= $current, 'text-ink-muted' => $i > $current])>{{ $label }}</span>
            @unless ($loop->last)
                <span aria-hidden="true" @class(['hidden h-px flex-1 sm:block', 'bg-primary-700 dark:bg-cyan-300' => $i < $current, 'bg-line' => $i >= $current])></span>
            @endunless
        </li>
    @endforeach
</ol>
