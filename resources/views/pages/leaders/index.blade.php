@use('App\Domain\Organisation\Enums\LeaderBody')
@use('App\Domain\Organisation\Queries\PublicDirectory')
@use('App\Support\Initials')
@php
    $locale = app()->getLocale();
    // The head of the Agency (management's first person by rank) opens the page; the groups follow.
    $management = collect($groups)->firstWhere('body', LeaderBody::Management);
    $featured = $management['leaders'][0] ?? null;
    $featuredShown = $featured !== null ? PublicDirectory::shown($featured, $locale) : null;
@endphp
<x-layouts.public :title="__('leaders.title')" :description="__('leaders.description')">
    <x-page-hero :title="__('leaders.title')" :lead="__('leaders.description')" tone="night" :crumbs="[[__('nav.about'), route('structure')]]">
        @if (count($groups) > 1)
            <nav aria-label="{{ __('leaders.jump') }}">
                <ul class="flex flex-wrap gap-2">
                    @foreach ($groups as $group)
                        <li>
                            <a href="#body-{{ $group['body']->value }}" class="inline-flex items-center gap-2 rounded-control border border-night-line py-2 ps-4 pe-2 text-sm font-semibold text-night-ink transition hover:border-cyan-400 hover:bg-white/5">
                                {{ $group['body']->label() }}
                                <span class="rounded-md bg-cyan-300 px-2 py-0.5 font-mono text-xs text-ink-brand">{{ $group['leaders']->count() }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif
    </x-page-hero>

    <div class="mx-auto max-w-page px-4 pb-16 sm:px-6">
        @if ($featuredShown)
            <section aria-label="{{ __('leaders.featured') }}" class="mt-12">
                <a href="{{ route('leaders.show', ['locale' => $featuredShown->locale, 'slug' => $featuredShown->slug]) }}" lang="{{ $featuredShown->locale }}"
                   class="reveal group relative isolate flex flex-wrap items-center gap-8 overflow-hidden rounded-card border border-night-line bg-night-900 p-8 text-night-ink transition hover:border-cyan-500 sm:p-10 lg:gap-12">
                    <x-hex-field id="leader-featured-hex" fade="right" class="absolute inset-y-0 end-0 -z-10 h-full w-3/5 text-cyan-500/20" />
                    <span aria-hidden="true" class="absolute inset-y-0 start-0 w-1 bg-saffron-500"></span>
                    @if ($featured->portrait)
                        <x-picture :media="$featured->portrait" preset="square" sizes="13rem" loading="eager" class="size-44 shrink-0 rounded-full object-cover ring-4 ring-cyan-400 ring-offset-4 ring-offset-night-900 sm:size-52" />
                    @else
                        <span aria-hidden="true" class="flex size-44 shrink-0 items-center justify-center rounded-full bg-night-800 font-display text-5xl font-bold text-night-ink ring-4 ring-cyan-400 ring-offset-4 ring-offset-night-900 sm:size-52">{{ Initials::of($featuredShown->name) }}</span>
                    @endif
                    <span class="flex min-w-0 flex-1 basis-80 flex-col gap-3">
                        <span class="kicker [--color-eyebrow:var(--color-cyan-300)]">{{ __('leaders.featured') }}</span>
                        <span class="font-display text-3xl leading-tight font-bold tracking-tight sm:text-4xl">{{ $featuredShown->name }}</span>
                        @if ($featuredShown->position)
                            <span class="text-lg text-night-ink">{{ $featuredShown->position }}</span>
                        @endif
                        @if ($featuredShown->summary)
                            <span class="max-w-prose text-night-muted">{{ $featuredShown->summary }}</span>
                        @endif
                        <span class="mt-2 inline-flex items-center gap-2 font-semibold text-saffron-400">
                            {{ __('leaders.view_profile') }}
                            <svg aria-hidden="true" class="size-4 transition-transform group-hover:translate-x-1 rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                        </span>
                    </span>
                </a>
            </section>
        @endif

        @forelse ($groups as $group)
            <section class="mt-16 scroll-mt-28" id="body-{{ $group['body']->value }}" aria-labelledby="body-{{ $group['body']->value }}-title">
                <div class="flex flex-wrap items-end justify-between gap-3 border-b border-line pb-4">
                    <div>
                        <h2 id="body-{{ $group['body']->value }}-title" class="heading-2">{{ $group['body']->label() }}</h2>
                        <p class="mt-2 max-w-prose text-ink-muted">{{ $group['body']->description() }}</p>
                    </div>
                    <span class="rounded-md border border-line px-2.5 py-1 font-mono text-xs tracking-wide text-ink-muted uppercase">{{ trans_choice('leaders.count', $group['leaders']->count(), ['count' => $group['leaders']->count()]) }}</span>
                </div>
                <ul class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($group['leaders'] as $leader)
                        @php $shown = PublicDirectory::shown($leader, $locale); @endphp
                        @continue($shown === null)
                        <li><x-directory.person :leader="$leader" :translation="$shown" /></li>
                    @endforeach
                </ul>
            </section>
        @empty
            <p class="mt-10 rounded-card border border-line bg-surface px-5 py-8 text-center text-ink-muted">{{ __('directory.empty') }}</p>
        @endforelse

        <section aria-labelledby="leaders-structure" class="reveal relative isolate mt-20 overflow-hidden rounded-card border border-line bg-surface-muted p-8 sm:p-10">
            <x-hex-field id="leaders-structure-hex" fade="right" class="absolute inset-y-0 end-0 -z-10 h-full w-1/2 text-primary-700/10 dark:text-cyan-500/15" />
            <div class="relative flex flex-wrap items-center justify-between gap-6">
                <div class="max-w-2xl">
                    <h2 id="leaders-structure" class="heading-2">{{ __('leaders.organised_title') }}</h2>
                    <p class="mt-3 text-lg text-ink-muted">{{ __('leaders.organised_text') }}</p>
                </div>
                <p class="flex flex-wrap gap-3">
                    <x-ui.button :href="route('structure')">{{ __('structure.title') }}</x-ui.button>
                    <x-ui.button :href="route('offices.index')" variant="secondary">{{ __('offices.title') }}</x-ui.button>
                </p>
            </div>
        </section>
    </div>
</x-layouts.public>
