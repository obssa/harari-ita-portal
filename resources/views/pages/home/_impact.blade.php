@php
    // ADR-0051: the figures editors publish with their source and date (Statistics, ADR-0027), shown on the
    // homepage in the order they set. Nothing is typed into this view; with no figures the band is not drawn.
    $statistics = \App\Domain\Projects\Models\Statistic::query()->where('on_achievements', true)->with('translations')
        ->orderBy('sort')->orderBy('id')->limit(3)->get()->filter(fn ($s) => filled($s->translation()?->getAttribute('label')));
@endphp
@if ($statistics->isNotEmpty())
    <section aria-labelledby="home-impact" data-motion class="relative isolate overflow-hidden bg-night-950 text-night-ink">
        <x-hex-field id="impact-hex" fade="left" class="absolute inset-y-0 start-0 -z-20 h-full w-1/2 text-cyan-500/15" />
        <x-circuit :flow="true" class="absolute start-0 top-0 -z-10 h-80 w-[40rem] text-cyan-500/10" />
        <x-circuit :flow="true" class="absolute end-0 bottom-0 -z-10 hidden h-80 w-[40rem] rotate-180 text-cyan-500/10 lg:block" />
        <div aria-hidden="true" class="absolute inset-0 -z-10 bg-[radial-gradient(40rem_24rem_at_85%_100%,rgb(232_145_45/0.16),transparent_70%)]"></div>
        <div class="mx-auto grid max-w-page gap-12 px-4 py-20 sm:px-6 lg:grid-cols-[1fr_2fr] lg:items-end">
            <div>
                <p class="kicker [--color-eyebrow:var(--color-cyan-300)]">{{ __('homepage.impact.kicker') }}</p>
                <h2 id="home-impact" class="heading-2 mt-2 text-night-ink">{{ __('homepage.impact.title') }}</h2>
                <p class="mt-4 max-w-md text-night-muted">{{ __('homepage.impact.text') }}</p>
                <a href="{{ route('projects.numbers') }}" class="mt-6 inline-flex items-center gap-1.5 font-semibold text-cyan-300 underline-offset-4 hover:underline">
                    {{ __('homepage.impact.all') }}
                    <svg aria-hidden="true" class="size-4 rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </a>
            </div>
            <dl class="grid gap-4 sm:grid-cols-3">
                @foreach ($statistics as $statistic)
                    @php
                        $translation = $statistic->translation();
                        $unit = $translation?->getAttribute('unit');
                        $source = $translation?->getAttribute('source');
                    @endphp
                    <div class="reveal glow-card flex flex-col gap-3 rounded-card border border-night-line bg-night-900/80 p-6" lang="{{ $translation?->locale }}">
                        <dt class="text-sm leading-snug text-night-muted">{{ $translation?->getAttribute('label') }}</dt>
                        <dd class="order-first font-display text-4xl font-semibold tracking-tight text-saffron-300 tabular-nums sm:text-5xl" dir="ltr">
                            <span data-count-to="{{ (float) $statistic->value == (int) $statistic->value ? (int) $statistic->value : '' }}">{{ $statistic->formatted() }}</span>@if (filled($unit))<span class="text-saffron-400">{{ $unit }}</span>@endif
                        </dd>
                        @if (filled($source) || $statistic->as_of)
                            <dd class="mt-auto border-t border-night-line pt-3 font-mono text-[0.7rem] leading-relaxed text-night-muted">
                                @if (filled($source))
                                    {{ __('leaders.source') }}:
                                    @if ($statistic->source_url)
                                        <a href="{{ $statistic->source_url }}" rel="noopener nofollow" class="text-cyan-300 underline underline-offset-2 hover:no-underline">{{ $source }}</a>
                                    @else
                                        {{ $source }}
                                    @endif
                                @endif
                                @if ($statistic->as_of)
                                    <span class="block">{{ __('projects.as_of') }} <x-date :date="$statistic->as_of" /></span>
                                @endif
                            </dd>
                        @endif
                    </div>
                @endforeach
            </dl>
        </div>
    </section>
@endif
