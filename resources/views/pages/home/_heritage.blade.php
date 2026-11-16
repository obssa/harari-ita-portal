@use('App\Domain\Content\Models\PageTranslation')
@php
    // The Harar page of the content pack (ADR-0063), linked when it is published in this language.
    $locale = app()->getLocale();
    $hararPage = PageTranslation::query()->with('page')->where('locale', $locale)->where('slug', 'harar')->first();
    $hararPath = $hararPage !== null && $hararPage->isLive() ? $hararPage->page?->path($locale) : null;
@endphp
{{-- ADR-0011: rooted in Harar: credited photographs of Jugol beside the facts that make it a World Heritage
     city, as mono stat tiles. --}}
<section aria-labelledby="home-heritage" class="border-y border-line bg-surface-muted">
    <div class="mx-auto grid max-w-page items-center gap-12 px-4 py-20 sm:px-6 lg:grid-cols-[1fr_1.1fr]">
        <div class="flex flex-col gap-4 lg:order-last">
            <p class="kicker">{{ __('homepage.heritage.kicker') }}</p>
            <h2 id="home-heritage" class="heading-2">{{ __('homepage.heritage.title') }}</h2>
            <p class="text-lg leading-relaxed text-ink-muted">{{ __('homepage.heritage.text') }}</p>
            <dl class="mt-2 grid grid-cols-3 border-y border-line">
                @foreach ([['2006', 'homepage.heritage.fact_unesco'], ['82', 'homepage.heritage.fact_mosques'], ['6', 'homepage.heritage.fact_gates']] as [$figure, $label])
                    <div class="flex flex-col gap-1 border-e border-line py-4 pe-3 last:border-e-0 [&:not(:first-child)]:ps-4">
                        <dt class="font-mono text-[0.7rem] leading-snug text-ink-muted uppercase">{{ __($label) }}</dt>
                        <dd class="order-first font-display text-3xl font-semibold tracking-tight text-primary-700 tabular-nums dark:text-cyan-300">{{ $figure }}</dd>
                    </div>
                @endforeach
            </dl>
            <p class="mt-3 flex flex-wrap gap-3">
                @if ($hararPath)
                    <x-ui.button :href="route('page.show', ['path' => $hararPath])">{{ __('homepage.heritage.discover') }}</x-ui.button>
                @endif
                <x-ui.button :href="route('offices.index')" :variant="$hararPath ? 'secondary' : 'primary'">{{ __('homepage.heritage.offices') }}</x-ui.button>
                <x-ui.button :href="route('leaders.index')" variant="secondary">{{ __('homepage.heritage.leaders') }}</x-ui.button>
            </p>
        </div>
        <figure class="reveal">
            <div class="grid h-[24rem] grid-cols-5 grid-rows-2 gap-3 sm:h-[30rem]">
                <div class="relative col-span-3 row-span-2 overflow-hidden rounded-card">
                    <x-harar-photo name="alley" sizes="(min-width: 64rem) 30rem, 60vw" class="size-full object-cover" />
                </div>
                <div class="col-span-2 overflow-hidden rounded-card">
                    <x-harar-photo name="wall" sizes="(min-width: 64rem) 20rem, 40vw" class="size-full object-cover" />
                </div>
                <div class="relative col-span-2 overflow-hidden rounded-card bg-night-900">
                    <x-hex-field id="heritage-hex" fade="none" class="absolute inset-0 size-full text-cyan-500/30" />
                    <img src="/brand/emblem-256.png" alt="" width="256" height="256" class="absolute inset-0 m-auto size-24 sm:size-28" loading="lazy">
                </div>
            </div>
            <figcaption class="mt-3 flex flex-wrap gap-x-3 gap-y-1 text-[11px] text-ink-muted">
                <x-harar-credit name="alley" /><span aria-hidden="true">·</span>
                <x-harar-credit name="wall" />
            </figcaption>
        </figure>
    </div>
</section>
