@use('App\Domain\Content\Models\PageTranslation')
@use('App\Domain\Organisation\Models\Pillar')
@use('App\Domain\Organisation\Queries\PublicDirectory')
@php
    // The Harar page of the content pack (ADR-0063), linked when it is published in this language.
    $locale = app()->getLocale();
    $hararPage = PageTranslation::query()->with('page')->where('locale', $locale)->where('slug', 'harar')->first();
    $hararPath = $hararPage !== null && $hararPage->isLive() ? $hararPage->page?->path($locale) : null;
    $pillars = PublicDirectory::visible(Pillar::query(), $locale)->orderBy('sort')->limit(5)->get()
        ->map(fn (Pillar $pillar): ?array => ($shown = PublicDirectory::shown($pillar, $locale)) !== null ? ['shown' => $shown] : null)
        ->filter()->values();
    $strokes = ['text-pillar-1', 'text-pillar-2', 'text-pillar-3', 'text-pillar-4', 'text-pillar-5'];
    $nodes = ['bg-pillar-1', 'bg-pillar-2', 'bg-pillar-3', 'bg-pillar-4', 'bg-pillar-5'];
@endphp
{{-- ADR-0046: the closing band. Jugol's gates become the five pillars: an arch for each, joined by a lane that turns
     into a circuit, over the facts that make Harar a World Heritage city. Night, so it reads as the page's finale. --}}
<section aria-labelledby="home-heritage" data-motion class="relative isolate overflow-hidden bg-night-950 text-night-ink">
    <x-hex-field id="heritage-hex" fade="radial" class="absolute inset-0 -z-20 size-full text-cyan-500/15" />
    <div aria-hidden="true" class="absolute inset-0 -z-10 bg-[radial-gradient(50rem_30rem_at_15%_0%,rgb(16_93_111/0.45),transparent_70%),radial-gradient(40rem_28rem_at_90%_100%,rgb(232_145_45/0.18),transparent_70%)]"></div>

    <div class="mx-auto max-w-page px-4 py-20 sm:px-6 lg:py-28">
        <div class="grid gap-12 lg:grid-cols-[1.1fr_1fr] lg:items-end">
            <div>
                <p class="kicker [--color-eyebrow:var(--color-saffron-300)]">{{ __('homepage.heritage.kicker') }}</p>
                <h2 id="home-heritage" class="heading-display mt-4 text-night-ink">{{ __('homepage.heritage.title') }}</h2>
                <p class="mt-6 max-w-2xl text-lg leading-relaxed text-night-muted sm:text-xl">{{ __('homepage.heritage.text') }}</p>
                <p class="mt-8 flex flex-wrap gap-3">
                    @if ($hararPath)
                        <x-ui.button :href="route('page.show', ['path' => $hararPath])" variant="action" size="lg">{{ __('homepage.heritage.discover') }}</x-ui.button>
                    @endif
                    <x-ui.button :href="route('pillars.index')" :variant="$hararPath ? 'light' : 'action'" size="lg">{{ __('homepage.heritage.pillars') }}</x-ui.button>
                </p>
            </div>

            <figure class="reveal grid grid-cols-5 gap-3">
                <div class="relative col-span-3 aspect-[4/5] overflow-hidden rounded-card ring-1 ring-night-line">
                    <x-harar-photo name="alley" sizes="(min-width: 64rem) 22rem, 60vw" class="size-full object-cover" />
                    <div aria-hidden="true" class="absolute inset-0 bg-linear-to-t from-night-950/70 to-transparent"></div>
                </div>
                <div class="col-span-2 flex flex-col gap-3">
                    <div class="relative flex-1 overflow-hidden rounded-card ring-1 ring-night-line">
                        <x-harar-photo name="wall" sizes="(min-width: 64rem) 14rem, 40vw" class="size-full object-cover" />
                    </div>
                    <div class="relative grid aspect-square place-items-center overflow-hidden rounded-card bg-night-900 ring-1 ring-night-line">
                        <x-circuit class="absolute inset-0 size-full text-cyan-500/25" />
                        <img src="/brand/emblem-256.webp" alt="" width="256" height="256" class="relative size-20 sm:size-24" loading="lazy">
                    </div>
                </div>
                <figcaption class="col-span-5 flex flex-wrap gap-x-3 gap-y-1 text-[11px] text-night-muted">
                    <x-harar-credit name="alley" /><span aria-hidden="true">·</span><x-harar-credit name="wall" />
                </figcaption>
            </figure>
        </div>

        @if ($pillars->isNotEmpty())
            {{-- Five gates, five pillars: each arch opens its pillar; the lane beneath runs from gate to gate. --}}
            <nav aria-label="{{ __('homepage.heritage.gates_label') }}" class="relative mt-20">
                <svg aria-hidden="true" class="pointer-events-none absolute inset-x-0 bottom-9 hidden h-8 w-full text-cyan-500/40 sm:block" viewBox="0 0 1000 32" preserveAspectRatio="none" fill="none">
                    <path d="M0 16H120l14-12h120l14 12h180l14 12h120l14-12h180l14 12H1000" stroke="currentColor" stroke-width="1.5" vector-effect="non-scaling-stroke"/>
                    {{-- ADR-0051: light travels the lane from gate to gate. --}}
                    <g class="circuit-flow" stroke="#67e8f9" stroke-width="2.5" stroke-linecap="round">
                        <path d="M0 16H120l14-12h120l14 12h180l14 12h120l14-12h180l14 12H1000" pathLength="100" vector-effect="non-scaling-stroke" style="--flow-duration: 9s"/>
                        <path d="M0 16H120l14-12h120l14 12h180l14 12h120l14-12h180l14 12H1000" pathLength="100" vector-effect="non-scaling-stroke" style="--flow-duration: 9s; --flow-delay: 4.5s"/>
                    </g>
                </svg>
                <ol class="relative grid grid-cols-2 gap-6 sm:grid-cols-5 sm:gap-4">
                    @foreach ($pillars as $item)
                        @php $i = $loop->index % 5; @endphp
                        <li lang="{{ $item['shown']->locale }}" @class(['col-span-2 sm:col-span-1' => $loop->last && $loop->count % 2 === 1])>
                            <a href="{{ route('pillars.show', ['locale' => $item['shown']->locale, 'slug' => $item['shown']->slug]) }}"
                               class="group flex flex-col items-center gap-4 text-center focus-visible:outline-3 focus-visible:outline-offset-4">
                                <span class="relative grid h-36 w-28 place-items-end justify-center sm:h-44 sm:w-32">
                                    <svg aria-hidden="true" class="arch-draw absolute inset-0 size-full {{ $strokes[$i] }} transition duration-300 group-hover:-translate-y-1" viewBox="0 0 80 100" fill="none">
                                        <path d="M8 100V42Q8 10 40 2q32 8 32 40v58" stroke="currentColor" stroke-width="3" pathLength="520"/>
                                        <path d="M20 100V48Q20 26 40 18q20 8 20 30v52" stroke="currentColor" stroke-width="1.5" opacity=".55" pathLength="520"/>
                                    </svg>
                                    <span class="relative mb-3 font-mono text-xs text-night-muted">{{ sprintf('%02d', $loop->iteration) }}</span>
                                </span>
                                <span aria-hidden="true" class="size-3 rotate-45 {{ $nodes[$i] }} ring-4 ring-night-950"></span>
                                <span class="font-display text-base leading-snug font-semibold tracking-tight text-night-ink group-hover:underline underline-offset-4">{{ $item['shown']->getAttribute('name') }}</span>
                            </a>
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif

        <dl class="mt-20 grid grid-cols-2 gap-px overflow-hidden rounded-card border border-night-line bg-night-line sm:grid-cols-4">
            @foreach ([['2006', 'homepage.heritage.fact_unesco'], ['82', 'homepage.heritage.fact_mosques'], ['102', 'homepage.heritage.fact_shrines'], [__('homepage.heritage.walls_figure'), 'homepage.heritage.fact_walls']] as [$figure, $label])
                <div class="flex flex-col gap-2 bg-night-950/80 p-6">
                    <dt class="font-mono text-[0.7rem] leading-snug tracking-wide text-night-muted uppercase">{{ __($label) }}</dt>
                    <dd class="order-first font-display text-3xl font-semibold tracking-tight text-saffron-300 tabular-nums sm:text-4xl">{{ $figure }}</dd>
                </div>
            @endforeach
        </dl>
        <p class="mt-4 font-mono text-[11px] text-night-muted">{{ __('homepage.heritage.source') }}</p>
    </div>
</section>
