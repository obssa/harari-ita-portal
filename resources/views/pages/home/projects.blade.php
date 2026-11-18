@use('App\Domain\Organisation\Queries\PublicDirectory')
@use('App\Domain\Projects\Enums\ProjectStatus')
@php
    // ADR-0051: the roadmap. Each project shows where it stands on the way from planned to completed, its current
    // stage lit (and pulsing while it is under way). A paused project shows its status instead of the track.
    $stages = [ProjectStatus::Planned, ProjectStatus::Ongoing, ProjectStatus::Completed];
@endphp
<section aria-labelledby="home-projects" class="mx-auto max-w-page px-4 py-16 sm:px-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="kicker">{{ __('homepage.section_kicker.projects') }}</p>
            <h2 id="home-projects" class="heading-2 mt-2">{{ $section->heading() }}</h2>
        </div>
        <a href="{{ route('projects.index') }}" class="inline-flex items-center gap-1.5 font-semibold text-link underline-offset-4 hover:underline">
            {{ __('projects.all_projects') }}
            <svg aria-hidden="true" class="size-4 rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </a>
    </div>
    <ol class="mt-8 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @foreach ($data as $project)
            @php
                $shown = PublicDirectory::shown($project, app()->getLocale());
                $reached = array_search($project->status, $stages, true);
            @endphp
            @continue($shown === null)
            <li class="flex" lang="{{ $shown->locale }}">
                <article class="reveal glow-card group relative flex w-full flex-col gap-4 overflow-hidden rounded-card border border-line bg-surface p-6 transition duration-200 hover:-translate-y-0.5 hover:border-cyan-500 hover:shadow-lg hover:shadow-night-950/5">
                    <p class="font-mono text-xs text-ink-muted">{{ sprintf('%02d', $loop->iteration) }}</p>
                    <div>
                        <h3 class="font-display text-xl font-semibold tracking-tight">
                            <a href="{{ route('projects.show', ['locale' => $shown->locale, 'slug' => $shown->slug]) }}" class="underline-offset-4 group-hover:underline after:absolute after:inset-0">{{ $shown->getAttribute('title') }}</a>
                        </h3>
                        @if (filled($shown->getAttribute('summary')))
                            <p class="mt-2 line-clamp-3 text-sm text-ink-muted">{{ $shown->getAttribute('summary') }}</p>
                        @endif
                    </div>
                    <div class="mt-auto border-t border-line pt-4">
                        @if ($reached === false)
                            <x-ui.badge :tone="$project->status->tone()">{{ $project->status->label() }}</x-ui.badge>
                        @else
                            <p class="sr-only">{{ __('homepage.projects.stage', ['stage' => $project->status->label()]) }}</p>
                            <ol aria-hidden="true" class="flex items-center">
                                @foreach ($stages as $index => $stage)
                                    <li @class(['flex items-center', 'flex-1' => ! $loop->last])>
                                        <span class="flex flex-col items-start gap-1.5">
                                            <span @class([
                                                'relative size-2.5 rounded-full',
                                                'bg-cyan-500' => $index <= $reached,
                                                'live-dot' => $index === $reached && $stage !== ProjectStatus::Completed,
                                                'border border-line bg-surface' => $index > $reached,
                                            ])></span>
                                            <span @class(['font-mono text-[0.65rem] tracking-wide uppercase', 'text-ink' => $index === $reached, 'text-ink-muted' => $index !== $reached])>{{ $stage->label() }}</span>
                                        </span>
                                        @unless ($loop->last)
                                            <span @class(['mx-2 mb-5 h-px flex-1', 'bg-cyan-500' => $index < $reached, 'bg-line' => $index >= $reached])></span>
                                        @endunless
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </div>
                </article>
            </li>
        @endforeach
    </ol>
</section>
