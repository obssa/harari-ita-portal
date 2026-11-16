@php
    $slides = $data['slides'] ?? [];
    $custom = trim((string) $section->setting('titles.'.app()->getLocale(), ''));
@endphp
@if ($slides !== [])
    {{-- ADR-0070: the carousel an editor arranges under Content → Homepage carousel, then featured stories. --}}
    <section>
        <x-hero-carousel :slides="$slides" :settings="$data['settings']" :label="$custom !== '' ? $custom : __('homepage.carousel.label')" />
    </section>
    <span id="home-after-hero"></span>
@else
    {{-- ADR-0011: the night hero: the promise on the left over the hexagon field and the logo's orange curve,
         a framed, credited Harar photo on the right with the five pillar nodes. Text first, so it paints at once. --}}
    <section aria-label="{{ $section->heading() }}" class="relative isolate overflow-hidden bg-night-900 text-night-ink">
        <x-hex-field id="hero-hex" class="absolute inset-0 -z-20 size-full text-cyan-500/20" />
        <div aria-hidden="true" class="absolute inset-0 -z-10 bg-[radial-gradient(60rem_40rem_at_85%_10%,rgb(16_93_111/0.55),transparent_70%)]"></div>
        <div aria-hidden="true" class="absolute inset-0 -z-10 bg-[linear-gradient(to_right,rgb(29_74_88/0.35)_1px,transparent_1px)] bg-[size:6rem_100%] opacity-60 [mask-image:linear-gradient(to_bottom,#000,transparent_85%)]"></div>

        <div class="mx-auto grid max-w-page gap-12 px-4 pt-16 pb-20 sm:px-6 sm:pt-24 lg:grid-cols-[1.25fr_1fr] lg:items-center lg:pt-28 lg:pb-28">
            <div class="max-w-2xl">
                <p class="kicker animate-rise [--color-eyebrow:var(--color-cyan-300)]">
                    <span aria-hidden="true" class="me-2 inline-block size-1.5 -translate-y-px rounded-full bg-cyan-400 align-middle"></span>{{ __('site.short') }} · {{ __('site.parent') }}
                </p>
                <p class="heading-display animate-rise mt-6 text-night-ink [--rise-delay:100ms]">{{ __('homepage.hero.title') }}</p>
                <p class="animate-rise mt-6 max-w-xl text-lg text-night-muted [--rise-delay:200ms] sm:text-xl">{{ __('site.description') }}</p>
                <p class="animate-rise mt-10 flex flex-wrap gap-3 [--rise-delay:300ms]">
                    <x-ui.button :href="\Illuminate\Support\Facades\Route::has('support.create') ? route('support.create') : route('contact')" variant="action" size="lg">{{ __('nav.cta_support') }}</x-ui.button>
                    <x-ui.button :href="route('pillars.index')" variant="light" size="lg">{{ __('homepage.hero.pillars') }}</x-ui.button>
                </p>
                <dl class="animate-rise mt-12 grid max-w-sm grid-cols-2 divide-x divide-night-line border-y border-night-line font-mono text-xs [--rise-delay:400ms] rtl:divide-x-reverse">
                    <div class="flex flex-col py-3 pe-3"><dt class="text-night-muted uppercase">{{ __('homepage.hero.languages') }}</dt><dd class="order-first font-display text-2xl text-saffron-400">4</dd></div>
                    <div class="flex flex-col py-3 ps-4"><dt class="text-night-muted uppercase">{{ __('homepage.hero.pillars_count') }}</dt><dd class="order-first font-display text-2xl text-saffron-400">5</dd></div>
                </dl>
            </div>

            <figure class="animate-rise relative isolate mx-auto w-full max-w-md [--rise-delay:200ms] lg:max-w-none">
                {{-- The logo's orange curve sweeps behind the frame, never behind text (DESIGN.md). --}}
                <svg aria-hidden="true" class="draw-in pointer-events-none absolute -start-14 hidden sm:block -top-28 -z-10 h-[calc(100%+14rem)] w-auto text-saffron-500" viewBox="0 0 120 400" fill="none">
                    <path d="M96 0C30 70 18 150 52 220c26 54 34 120-24 180" stroke="currentColor" stroke-width="7" stroke-linecap="round"/>
                </svg>
                <div class="relative overflow-hidden rounded-[1.75rem] border border-night-line bg-night-800 p-2 shadow-2xl shadow-night-950/60">
                    <div class="relative aspect-[4/5] overflow-hidden rounded-[1.25rem]">
                        <x-harar-photo name="gate" sizes="(min-width: 64rem) 28rem, (min-width: 40rem) 70vw, 80vw" loading="eager" fetchpriority="high" class="size-full object-cover object-[60%_50%]" />
                        <div aria-hidden="true" class="absolute inset-0 bg-linear-to-t from-night-950/85 via-night-950/10 to-transparent"></div>
                        <div class="absolute inset-x-4 bottom-4 flex items-end justify-between gap-3">
                            <p class="font-mono text-[0.7rem] tracking-wider text-night-ink uppercase">{{ __('harar.photo.gate') }}</p>
                            <img src="/brand/emblem-128.webp" alt="" width="48" height="48" class="size-12 shrink-0">
                        </div>
                    </div>
                </div>
                <ul aria-hidden="true" class="absolute -start-4 top-10 hidden flex-col gap-2 sm:flex">
                    <li class="size-3 rotate-45 bg-pillar-1"></li><li class="size-3 rotate-45 bg-pillar-2"></li><li class="size-3 rotate-45 bg-pillar-3"></li><li class="size-3 rotate-45 bg-pillar-4"></li><li class="size-3 rotate-45 bg-pillar-5"></li>
                </ul>
                <figcaption class="mt-3 text-end text-[11px] text-night-muted"><x-harar-credit name="gate" /></figcaption>
            </figure>
        </div>
        <span id="home-after-hero"></span>
    </section>
@endif
