@use('App\Domain\Content\Queries\PublicPosts')
@php
    $locale = app()->getLocale();
    $lead = $data['calls'][0] ?? null;
    $rest = array_slice($data['calls'], 1);
    $stories = $data['stories']->map(fn ($post): array => ['post' => $post, 'shown' => PublicPosts::shown($post, $locale)])
        ->filter(fn (array $item): bool => $item['shown'] !== null)->values();
    $tag = (string) config('portal.innovation.stories_tag');
@endphp
{{-- ADR-0023: what innovators can take part in now (the nearest deadline leads on night, with its countdown), the
     other open calls, two live figures and the latest innovation stories. --}}
<section aria-labelledby="home-innovation" class="mx-auto max-w-page px-4 py-20 sm:px-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="kicker">{{ __('nav.innovation') }}</p>
            <h2 id="home-innovation" class="heading-2 mt-2">{{ $section->heading() }}</h2>
        </div>
        <a href="{{ route('startups.index') }}" class="inline-flex items-center gap-1.5 font-semibold text-link">
            {{ __('homepage.innovation.all') }}
            <svg aria-hidden="true" class="size-4 rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </a>
    </div>

    <div class="mt-10 grid gap-4 lg:grid-cols-[1fr_20rem]">
        <div class="space-y-4">
            @if ($lead)
                <article class="group relative isolate overflow-hidden rounded-card border border-night-line bg-night-900 p-7 pb-16 text-night-ink" lang="{{ $lead['locale'] }}">
                    <x-circuit class="absolute inset-x-0 bottom-0 -z-10 h-12 w-full text-cyan-500/20" />
                    <div class="grid gap-6 md:grid-cols-[1fr_16rem] md:items-end">
                        <div>
                            <p class="kicker [--color-eyebrow:var(--color-cyan-300)]">{{ $lead['open'] ? __('homepage.innovation.open_call') : __('homepage.innovation.next_call') }} · {{ __('homepage.innovation.kind.'.$lead['kind']) }}</p>
                            <h3 class="mt-3 font-display text-2xl font-semibold tracking-tight sm:text-3xl">
                                <a href="{{ $lead['url'] }}" class="after:absolute after:inset-0 group-hover:underline underline-offset-4">{{ $lead['title'] }}</a>
                            </h3>
                            @if (filled($lead['summary']))
                                <p class="mt-3 max-w-prose text-night-muted">{{ $lead['summary'] }}</p>
                            @endif
                        </div>
                        <x-countdown :until="$lead['date']" :label="$lead['open'] ? __('homepage.innovation.closes_in') : __('homepage.innovation.opens_in')" tone="night" />
                    </div>
                </article>
                @vite('resources/js/countdown.js')
            @endif
            @if (! $lead && ($data['programmes'] ?? collect())->isNotEmpty())
                {{-- ADR-0045: while no call is open, the programmes, each with where its next call will appear. --}}
                <ul class="grid gap-4 sm:grid-cols-2">
                    @foreach ($data['programmes'] as $programme)
                        @php $shownProgramme = \App\Domain\Organisation\Queries\PublicDirectory::shown($programme, $locale); @endphp
                        @continue($shownProgramme === null)
                        <li lang="{{ $shownProgramme->locale }}" @class(['group relative isolate overflow-hidden rounded-card border p-6 transition', 'border-night-line bg-night-900 text-night-ink sm:col-span-2' => $loop->first, 'border-line bg-surface hover:border-cyan-500' => ! $loop->first])>
                            @if ($loop->first)
                                <x-circuit class="absolute inset-x-0 bottom-0 -z-10 h-12 w-full text-cyan-500/20" />
                            @endif
                            <p @class(['kicker', '[--color-eyebrow:var(--color-cyan-300)]' => $loop->first])>{{ $programme->kind->label() }}</p>
                            <h3 @class(['mt-2 font-display font-semibold tracking-tight', 'text-2xl sm:text-3xl' => $loop->first, 'text-lg' => ! $loop->first])>
                                <a href="{{ route('programmes.show', ['locale' => $shownProgramme->locale, 'slug' => $shownProgramme->slug]) }}" class="after:absolute after:inset-0 group-hover:underline underline-offset-4">{{ $shownProgramme->getAttribute('title') }}</a>
                            </h3>
                            @if (filled($shownProgramme->getAttribute('summary')))
                                <p @class(['mt-2 max-w-prose', 'text-night-muted' => $loop->first, 'text-ink-muted' => ! $loop->first])>{{ $shownProgramme->getAttribute('summary') }}</p>
                            @endif
                            <p @class(['mt-4 font-mono text-xs', 'text-cyan-300 pb-6' => $loop->first, 'text-ink-muted' => ! $loop->first])>{{ __('homepage.innovation.next_call_soon') }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
            @if ($rest !== [])
                <ul class="grid gap-4 sm:grid-cols-2">
                    @foreach ($rest as $call)
                        <li lang="{{ $call['locale'] }}" class="group relative rounded-card border border-line bg-surface p-5 transition hover:border-cyan-500">
                            <p class="kicker">{{ __('homepage.innovation.kind.'.$call['kind']) }}</p>
                            <h3 class="mt-2 font-display text-lg font-semibold tracking-tight">
                                <a href="{{ $call['url'] }}" class="after:absolute after:inset-0 group-hover:underline underline-offset-4">{{ $call['title'] }}</a>
                            </h3>
                            <p class="mt-2 font-mono text-xs text-ink-muted">{{ __('homepage.innovation.closes') }} <x-date :date="$call['date']" /></p>
                        </li>
                    @endforeach
                </ul>
            @endif
            @if ($stories->isNotEmpty())
                <div class="pt-4">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <h3 class="font-display text-xl font-semibold tracking-tight">{{ __('homepage.innovation.stories') }}</h3>
                        <a href="{{ route('news.index', ['tag' => $tag]) }}" class="text-sm font-semibold text-link">{{ __('homepage.innovation.all_stories') }}</a>
                    </div>
                    <ul class="mt-4 grid gap-4 sm:grid-cols-3">
                        @foreach ($stories as $item)
                            <li><x-news-card :post="$item['post']" :shown="$item['shown']" :heading-level="4" /></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <aside class="flex flex-col gap-4" aria-label="{{ __('homepage.innovation.figures') }}">
            @if ($data['startups'] > 0 || $data['challenges'] > 0)
            <dl class="grid grid-cols-2 gap-4 lg:grid-cols-1">
                <div class="flex flex-col-reverse gap-1 rounded-card border border-line bg-surface p-5">
                    <dt class="font-mono text-xs tracking-wide text-ink-muted uppercase">{{ __('homepage.live.startups_listed') }}</dt>
                    <dd class="font-display text-4xl font-bold tracking-tight tabular-nums" dir="ltr">{{ $data['startups'] }}</dd>
                </div>
                <div class="flex flex-col-reverse gap-1 rounded-card border border-line bg-surface p-5">
                    <dt class="font-mono text-xs tracking-wide text-ink-muted uppercase">{{ __('homepage.live.challenges_open') }}</dt>
                    <dd class="font-display text-4xl font-bold tracking-tight tabular-nums" dir="ltr">{{ $data['challenges'] }}</dd>
                </div>
            </dl>
            @endif
            <nav aria-label="{{ __('nav.innovation') }}" class="flex flex-col gap-2 rounded-card border border-line bg-surface p-5">
                @foreach ([
                    [route('startups.apply'), __('innovation.startups.apply')],
                    [route('programmes.index'), __('innovation.programmes.title')],
                    [route('challenges.index'), __('innovation.challenges.title')],
                ] as [$url, $label])
                    <a href="{{ $url }}" class="flex items-center justify-between gap-2 rounded-control px-2 py-2 font-medium hover:bg-surface-muted">
                        {{ $label }}
                        <svg aria-hidden="true" class="size-4 text-ink-muted rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </a>
                @endforeach
            </nav>
            <p class="font-mono text-xs text-ink-muted">{{ __('homepage.innovation.source') }}</p>
        </aside>
    </div>
</section>
