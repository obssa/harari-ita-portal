@use('App\Domain\Organisation\Queries\PublicDirectory')
@php $locale = app()->getLocale(); @endphp
<x-layouts.public
    :title="$translation->name"
    :description="$translation->summary"
    :alternates="$alternates"
    :image="$image"
    :json-ld="$jsonLd"
>
    @php
        $tone = $pillar->accentTone();
        $node = match ($tone) { 'cyan' => 'bg-cyan-500', 'saffron' => 'bg-saffron-500', 'ruby' => 'bg-ruby-600', 'night' => 'bg-night-ink', default => 'bg-primary-500' };
    @endphp
    <article>
        {{-- ADR-0012: the pillar opens on a night band with the hexagon field and its colour on the edge; the head of the
             pillar sits beside the name. --}}
        <section class="relative isolate overflow-hidden border-b border-night-line bg-night-900 text-night-ink">
            <span aria-hidden="true" class="absolute inset-x-0 top-0 h-1 {{ $node }}"></span>
            <x-hex-field id="pillar-hex" fade="right" class="absolute inset-y-0 end-0 -z-10 h-full w-3/5 text-cyan-500/20" />
            <div class="mx-auto max-w-page px-4 py-10 sm:px-6 sm:py-14">
                <nav aria-label="{{ __('news.breadcrumb') }}">
                    <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 font-mono text-xs tracking-wide text-night-muted uppercase [&_a:hover]:text-night-ink">
                        <li><a href="{{ route('home') }}">{{ __('nav.home') }}</a></li>
                        <li class="flex items-center gap-2"><span aria-hidden="true" class="text-cyan-400">/</span><a href="{{ route('pillars.index') }}">{{ __('pillars.title') }}</a></li>
                        <li class="flex min-w-0 items-center gap-2"><span aria-hidden="true" class="text-cyan-400">/</span><span aria-current="page" class="line-clamp-1 text-night-ink normal-case">{{ $translation->name }}</span></li>
                    </ol>
                </nav>
                <div class="mt-10 grid items-end gap-10 lg:grid-cols-[1fr_22rem]">
                    <header>
                        @if ($pillar->logo)
                            <x-picture :media="$pillar->logo" preset="square" sizes="5rem" class="mb-6 size-20 rounded-card bg-white object-contain p-1" />
                        @endif
                        <p class="flex items-center gap-3">
                            <span aria-hidden="true" class="size-3 rotate-45 {{ $node }}"></span>
                            <span class="kicker [--color-eyebrow:var(--color-cyan-300)]">{{ __('pillars.title') }}</span>
                        </p>
                        <h1 class="heading-display mt-4 text-night-ink">{{ $translation->name }}</h1>
                        @if ($translation->summary)
                            <p class="mt-5 max-w-prose text-lg text-night-muted sm:text-xl">{{ $translation->summary }}</p>
                        @endif
                        @php
                            // ADR-0046: each pillar leads to its own work (by its English address); others to the services.
                            [$ctaRoute, $ctaLabel] = match ($pillar->translation('en', fallback: false)?->getAttribute('slug')) {
                                'innovation-startups' => ['programmes.index', __('innovation.programmes.title')],
                                'digital-skills' => ['academy.index', __('academy.title')],
                                'data-infrastructure' => ['datasets.index', __('opendata.title')],
                                'trust-cyber-awareness' => ['advisories.index', __('advisories.title')],
                                default => ['services.index', __('homepage.hero.services')],
                            };
                        @endphp
                        <p class="mt-8 flex flex-wrap gap-3">
                            <x-ui.button :href="route($ctaRoute)" variant="action" size="lg">{{ $ctaLabel }}</x-ui.button>
                        </p>
                    </header>
                    @if ($head)
                        <a href="{{ route('leaders.show', ['locale' => $head->locale, 'slug' => $head->slug]) }}" lang="{{ $head->locale }}"
                           class="group flex items-center gap-4 rounded-card border border-night-line bg-night-800/80 p-5 transition hover:border-cyan-400">
                            @if ($pillar->head?->portrait)
                                <x-picture :media="$pillar->head->portrait" preset="square" sizes="5rem" class="size-18 shrink-0 rounded-full object-cover ring-2 ring-cyan-400 ring-offset-2 ring-offset-night-800" />
                            @else
                                <span aria-hidden="true" class="flex size-18 shrink-0 items-center justify-center rounded-full bg-night-950 font-display text-2xl font-bold text-night-ink ring-2 ring-cyan-400 ring-offset-2 ring-offset-night-800">{{ mb_substr($head->name, 0, 1) }}</span>
                            @endif
                            <span class="min-w-0">
                                <span class="block font-mono text-[11px] tracking-wider text-cyan-300 uppercase">{{ __('pillars.head') }}</span>
                                <span class="mt-1 block font-display text-lg leading-snug font-semibold group-hover:underline">{{ $head->name }}</span>
                                @if ($head->position)
                                    <span class="mt-0.5 block text-sm text-night-muted">{{ $head->position }}</span>
                                @endif
                            </span>
                        </a>
                    @endif
                </div>
            </div>
        </section>

        <div class="mx-auto max-w-page px-4 pt-12 pb-16 sm:px-6">
            <div class="grid gap-12 lg:grid-cols-[1fr_22rem]">
                <div class="min-w-0">
                    @if ($pillar->coverImage)
                        <figure class="mb-10">
                            <x-picture :media="$pillar->coverImage" preset="landscape" sizes="(min-width: 64rem) 52rem, 100vw" class="w-full rounded-card" />
                        </figure>
                    @endif
                    <h2 class="heading-2">{{ __('pillars.about') }}</h2>
                    @if (trim($body) !== '')
                        <div class="prose-content mt-6 max-w-prose">{!! $body !!}</div>
                    @elseif ($translation->summary)
                        <p class="mt-6 max-w-prose text-lg leading-relaxed">{{ $translation->summary }}</p>
                    @endif
                </div>

                <aside class="space-y-8" aria-label="{{ __('offices.details') }}">
                    @if ($people->isNotEmpty())
                        <section aria-labelledby="pillar-people">
                            <h2 id="pillar-people" class="kicker">{{ __('pillars.people') }}</h2>
                            <ul class="mt-4 space-y-3">
                                @foreach ($people as $person)
                                    @php $shown = PublicDirectory::shown($person, $locale); @endphp
                                    @continue($shown === null)
                                    <li><x-directory.person-row :leader="$person" :translation="$shown" /></li>
                                @endforeach
                            </ul>
                        </section>
                    @endif
                    <x-directory.contacts :phone="$pillar->phone" :email="$pillar->email" :socials="$pillar->socialLinks()" />
                    <section aria-labelledby="pillar-take-part" class="relative isolate overflow-hidden rounded-card border border-line bg-surface-muted p-6">
                        <x-hex-field id="pillar-take-part-hex" fade="right" class="absolute inset-0 -z-10 size-full text-primary-700/10 dark:text-cyan-500/15" />
                        <h2 id="pillar-take-part" class="relative font-display text-lg font-semibold tracking-tight">{{ __('pillars.take_part_title') }}</h2>
                        <p class="relative mt-2 text-sm text-ink-muted">{{ __('pillars.take_part_text') }}</p>
                        <p class="relative mt-4 flex flex-wrap gap-2">
                            <x-ui.button :href="route('events.index')" variant="secondary" size="sm">{{ __('events.title') }}</x-ui.button>
                        </p>
                    </section>
                </aside>
            </div>

            <x-directory.posts :posts="$posts" :heading="__('pillars.posts')" />
            <x-directory.social :posts="$social" />
        </div>
    </article>
</x-layouts.public>
