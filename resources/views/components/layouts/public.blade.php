@props(['title' => null, 'description' => null, 'alternates' => null, 'image' => null, 'type' => 'website', 'jsonLd' => [], 'flushFooter' => false])
@php
    $locale = app()->getLocale();
    $ethiopic = in_array($locale, ['am', 'har'], true);
    try {
        $hotline = filled(app(\App\Settings\SiteSettings::class)->hotline) ? app(\App\Settings\SiteSettings::class)->hotline : null;
    } catch (\Throwable) {
        $hotline = null;
    }
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' · ' : '' }}{{ __('site.name') }}</title>
    <meta name="description" content="{{ $description ?? __('site.description') }}">
    <meta name="theme-color" content="#0B4A5C">
    <link rel="icon" href="/favicon.ico?v=2" sizes="48x48">
    <link rel="icon" href="/favicon-96.png?v=2" type="image/png" sizes="96x96">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png?v=2">
    <link rel="manifest" href="/site.webmanifest?v=2">
    {{-- Only the fonts of the page's own script are preloaded: on Ethiopic pages Inter draws the odd Latin word and
         may arrive later, so it does not compete with the Ethiopic font and the hero image on slow phones. --}}
    @if ($ethiopic)
        <link rel="preload" href="/fonts/portal/noto-sans-ethiopic-wght-v5.woff2" as="font" type="font/woff2" crossorigin>
    @else
        <link rel="preload" href="/fonts/portal/inter-latin-wght-v5.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="/fonts/portal/space-grotesk-latin-wght-v5.woff2" as="font" type="font/woff2" crossorigin>
    @endif
    <x-locale-meta :alternates="$alternates" />
    <x-seo :title="$title ?? __('site.name')" :description="$description ?? __('site.description')" :image="$image ?? app(\App\Domain\Content\Support\OgImage::class)->url('site-'.app()->getLocale(), __('site.name'), __('site.office'))" :type="$type" :json-ld="$jsonLd" />
    {{-- ADR-0008: the visitor's colour theme, applied before the first paint. --}}
    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">try{var t=localStorage.getItem('hinta-theme');if(t==='light'||t==='dark'){document.documentElement.dataset.theme=t}}catch(e){}</script>
    {{-- ADR-0040: a cookieless page view count; ADR-0008: the theme switch; the homepage carousel adds its own small script (ADR-0070). --}}
    @vite(['resources/css/app.css', 'resources/js/beacon.js', 'resources/js/theme.js', 'resources/js/menu.js', 'resources/js/motion.js'])
</head>
<body class="flex min-h-screen flex-col bg-canvas text-ink antialiased">
    <a href="#content" class="sr-only focus:not-sr-only focus:fixed focus:start-4 focus:top-4 focus:z-50 focus:rounded-control focus:bg-primary-700 focus:px-4 focus:py-2 focus:text-white">
        {{ __('nav.skip') }}
    </a>

    {{-- ADR-0009: the night strip: the region, today in both calendars, the languages and the theme. --}}
    <div class="bg-night-950 text-night-muted">
        <div class="mx-auto flex h-10 max-w-page items-center justify-between gap-x-4 px-4 font-mono text-[0.72rem] tracking-wide sm:px-6">
            <div class="hidden min-w-0 items-center gap-x-5 md:flex">
                <p class="flex items-center gap-2 uppercase">
                    <span aria-hidden="true" class="size-1.5 rounded-full bg-cyan-400 shadow-[0_0_0_3px] shadow-cyan-400/20"></span>
                    {{ __('site.parent') }}
                </p>
                <p class="hidden items-center gap-2 lg:flex">
                    <span class="sr-only">{{ __('calendar.today') }}: </span><x-date :date="now()" />
                </p>
                @if ($hotline)
                    <p class="hidden items-center gap-2 xl:flex">
                        <span>{{ __('footer.hotline') }}</span>
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $hotline) }}" class="font-semibold text-night-ink hover:text-cyan-300">{{ $hotline }}</a>
                    </p>
                @endif
            </div>
            <div class="flex min-w-0 flex-1 items-center justify-between gap-x-2 md:flex-none md:justify-end">
                <div class="-ms-2 min-w-0 overflow-x-auto [scrollbar-width:none] md:ms-0"><x-language-switcher :alternates="$alternates" /></div>
                <span aria-hidden="true" class="hidden h-4 w-px bg-night-line sm:block"></span>
                <x-theme-toggle class="text-night-ink" />
            </div>
        </div>
    </div>

    {{-- ADR-0009: the emblem and wordmark, search, the call to action and (below desktops) the menu button; on
         desktops the main menu has its own row below, so the eight sections fit in every language (Afaan Oromo's
         labels are the longest) without wrapping. The header stays pinned at the top while reading. --}}
    <header class="sticky top-0 z-30 border-b border-line bg-surface/85 backdrop-blur-xl supports-[backdrop-filter]:bg-surface/75">
        <div class="mx-auto flex h-16 max-w-page items-center gap-4 px-4 sm:px-6 lg:h-[4.5rem]">
            <a href="{{ route('home') }}" @if (request()->routeIs('home')) aria-current="page" @endif class="flex shrink-0 items-center gap-2.5 rounded-control no-underline">
                <img src="/brand/emblem-128.webp" alt="" width="44" height="44" class="size-10 shrink-0 lg:size-11">
                <img src="/brand/wordmark.webp" alt="" width="387" height="112" class="h-6 w-auto dark:hidden lg:h-7">
                <img src="/brand/wordmark-dark.webp" alt="" width="387" height="112" class="hidden h-6 w-auto dark:block lg:h-7">
                <span class="sr-only">{{ __('site.name') }}</span>
                <span aria-hidden="true" class="hidden max-w-[11rem] border-s border-line ps-3 text-xs leading-tight text-ink-muted xl:block">{{ __('site.office') }}</span>
            </a>
            <div class="ms-auto flex shrink-0 items-center gap-2">
                @unless (request()->routeIs('search'))
                <details name="site-nav" class="group/search">
                    <summary class="inline-flex size-10 cursor-pointer list-none items-center justify-center rounded-control text-ink transition-colors hover:bg-surface-muted group-open/search:bg-surface-muted [&::-webkit-details-marker]:hidden">
                        <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                        <span class="sr-only">{{ __('search.short') }}</span>
                    </summary>
                    <div class="absolute inset-x-0 top-full z-40 border-y border-line bg-surface shadow-2xl shadow-night-950/15">
                        <form method="get" action="{{ route('search') }}" role="search" class="mx-auto flex max-w-page items-center gap-3 px-4 py-6 sm:px-6">
                            <svg aria-hidden="true" class="hidden size-5 shrink-0 text-cyan-700 sm:block dark:text-cyan-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 7 5 5-5 5M13 18h6"/></svg>
                            <label for="site-search" class="sr-only">{{ __('search.label') }}</label>
                            <input id="site-search" type="search" name="q" maxlength="100" minlength="2" required placeholder="{{ __('search.placeholder') }}"
                                   class="min-w-0 flex-1 border-0 border-b-2 border-line bg-transparent px-1 py-2 font-display text-xl focus:border-cyan-500 focus:ring-0 focus:outline-none sm:text-2xl">
                            <button type="submit" class="rounded-control bg-primary-700 px-5 py-2.5 font-semibold text-white hover:bg-primary-800">{{ __('search.button') }}</button>
                        </form>
                    </div>
                </details>
                @endunless
                <a href="{{ \Illuminate\Support\Facades\Route::has('support.create') ? route('support.create') : route('contact') }}"
                   class="hidden items-center gap-2 rounded-control bg-saffron-500 px-4 py-2.5 text-sm font-semibold text-ink-brand transition-colors hover:bg-saffron-400 sm:inline-flex">
                    {{ __('nav.cta_support') }}
                    <svg aria-hidden="true" class="size-4 rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </a>
                <div class="xl:hidden"><x-site-nav variant="drawer" /></div>
            </div>
        </div>
        <div class="hidden border-t border-line xl:block">
            <div class="mx-auto flex h-12 max-w-page items-center px-4 sm:px-6"><x-site-nav class="-ms-3" /></div>
        </div>
    </header>

    <main id="content" tabindex="-1" class="flex-1 focus:outline-none">
        {{ $slot }}
    </main>

    <x-site-footer :flush="$flushFooter" />
</body>
</html>
