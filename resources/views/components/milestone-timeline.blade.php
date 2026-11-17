@props(['milestones'])
@php $today = \Carbon\CarbonImmutable::today(); @endphp
{{-- ADR-0024: a project's milestones in order; done ones are filled, an overdue one is marked in words, not by colour alone. --}}
<ol {{ $attributes->class('relative space-y-6 border-s border-line ps-6') }}>
    @foreach ($milestones as $milestone)
        @php
            $done = $milestone->isDone();
            $overdue = $milestone->isOverdue($today);
        @endphp
        <li class="relative">
            <span aria-hidden="true" @class([
                'absolute -start-[1.95rem] top-1 grid size-4 place-items-center rounded-full ring-4 ring-canvas',
                'bg-cyan-600 dark:bg-cyan-400' => $done,
                'bg-ruby-600' => ! $done && $overdue,
                'border-2 border-ink-muted bg-surface' => ! $done && ! $overdue,
            ])>
                @if ($done)
                    <svg viewBox="0 0 16 16" class="size-3 text-white dark:text-night-950" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m4 8 3 3 5-6" /></svg>
                @endif
            </span>
            <p class="font-medium">{{ $milestone->title() }}</p>
            <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 font-mono text-xs text-ink-muted">
                @if ($done)
                    <span>{{ __('projects.milestone_done') }} <x-date :date="$milestone->done_on" /></span>
                @elseif ($milestone->due_on)
                    <span>{{ __('projects.milestone_due') }} <x-date :date="$milestone->due_on" /></span>
                @endif
                @if ($overdue)
                    <x-ui.badge tone="danger">{{ __('projects.overdue') }}</x-ui.badge>
                @elseif (! $done)
                    <span class="sr-only">{{ __('projects.milestone_open') }}</span>
                @endif
            </p>
        </li>
    @endforeach
</ol>
