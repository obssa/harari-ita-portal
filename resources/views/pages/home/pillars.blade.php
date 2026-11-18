@use('App\Domain\Content\Queries\PublicPosts')
@use('App\Domain\Organisation\Queries\PublicDirectory')
@php
    $locale = app()->getLocale();
    // DESIGN.md: each pillar keeps its place colour (pillar-1 … pillar-5) on the node, the arch and the edge.
    $nodes = ['bg-pillar-1', 'bg-pillar-2', 'bg-pillar-3', 'bg-pillar-4', 'bg-pillar-5'];
    $edges = ['hover:border-pillar-1', 'hover:border-pillar-2', 'hover:border-pillar-3', 'hover:border-pillar-4', 'hover:border-pillar-5'];
    $texts = ['text-pillar-1', 'text-pillar-2', 'text-pillar-3', 'text-pillar-4', 'text-pillar-5'];
@endphp
{{-- ADR-0011: the pillar "gates": a bento grid, the first gate twice as wide, each with its arch, number and colour. --}}
<section aria-labelledby="home-pillars" class="mx-auto max-w-page px-4 py-20 sm:px-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="kicker">{{ __('homepage.section_kicker.pillars') }}</p>
            <h2 id="home-pillars" class="heading-2 mt-2">{{ $section->heading() }}</h2>
        </div>
        <a href="{{ route('pillars.index') }}" class="inline-flex items-center gap-1.5 font-semibold text-link">
            {{ __('pillars.title') }}
            <svg aria-hidden="true" class="size-4 rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </a>
    </div>
    <ul class="mt-10 grid auto-rows-fr gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($data as $item)
            @php
                $shown = PublicDirectory::shown($item['pillar'], $locale);
                $post = $item['post'] !== null ? PublicPosts::shown($item['post'], $locale) : null;
                $i = $loop->index % 5;
            @endphp
            @continue($shown === null)
            <li lang="{{ $shown->locale }}" @class(['lg:col-span-2' => $loop->first])>
                <article class="reveal glow-card group relative flex h-full min-h-56 flex-col gap-5 overflow-hidden rounded-card border border-line bg-surface p-7 transition duration-200 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-night-950/5 {{ $edges[$i] }}">
                    <svg aria-hidden="true" class="arch-draw pointer-events-none absolute -end-6 -bottom-10 -z-10 h-44 w-auto opacity-[0.08] transition-opacity duration-300 group-hover:opacity-30 {{ $texts[$i] }}" viewBox="0 0 80 100" fill="none" stroke="currentColor" stroke-width="6"><path d="M10 100V40Q10 12 40 4q30 8 30 36v60" pathLength="520"/></svg>
                    <div class="flex items-center gap-3">
                        <span aria-hidden="true" class="size-3 rotate-45 {{ $nodes[$i] }}"></span>
                        <span class="font-mono text-xs text-ink-muted">{{ sprintf('%02d', $loop->iteration) }}</span>
                    </div>
                    <div>
                        <h3 class="font-display text-2xl font-semibold tracking-tight">
                            <a href="{{ route('pillars.show', ['locale' => $shown->locale, 'slug' => $shown->slug]) }}" class="after:absolute after:inset-0">{{ $shown->getAttribute('name') }}</a>
                        </h3>
                        @if (filled($shown->getAttribute('summary')))
                            <p class="mt-2 text-ink-muted">{{ $shown->getAttribute('summary') }}</p>
                        @endif
                    </div>
                    @if ($post)
                        <p class="mt-auto border-t border-line pt-4 text-sm" lang="{{ $post->locale }}"><span class="font-mono text-xs text-ink-muted uppercase">{{ __('homepage.latest') }}</span> {{ $post->title }}</p>
                    @endif
                </article>
            </li>
        @endforeach
    </ul>
    @php
        $national = \App\Domain\Content\Models\PageTranslation::query()->with('page')->where('locale', $locale)->where('slug', 'digital-ethiopia-2030')->first();
        $nationalPath = $national !== null && $national->isLive() ? $national->page?->path($locale) : null;
    @endphp
    @if ($nationalPath)
        {{-- ADR-0046: the pillars are the region's part in the national strategy. --}}
        <a href="{{ route('page.show', ['path' => $nationalPath]) }}" class="group mt-6 flex flex-wrap items-center justify-between gap-4 rounded-card border border-line bg-surface-muted px-6 py-5 transition hover:border-cyan-500">
            <span class="flex items-center gap-4">
                <span aria-hidden="true" class="grid size-11 shrink-0 place-items-center rounded-control bg-night-900 font-mono text-xs font-semibold text-saffron-300">2030</span>
                <span>
                    <span class="block font-display text-lg font-semibold tracking-tight">{{ __('homepage.pillars.national_title') }}</span>
                    <span class="block text-sm text-ink-muted">{{ __('homepage.pillars.national_text') }}</span>
                </span>
            </span>
            <span class="inline-flex items-center gap-1.5 font-semibold text-link group-hover:underline underline-offset-4">
                {{ __('homepage.pillars.national_link') }}
                <svg aria-hidden="true" class="size-4 rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </span>
        </a>
    @endif
</section>
