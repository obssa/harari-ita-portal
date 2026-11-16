@props(['code', 'all' => false])
@php
    // Error pages run outside the language routes and must not depend on the
    // database: the language comes from the URL, cookie or browser.
    $locale = \App\Domain\Translation\Support\Locales::fromRequest(request());
    app()->setLocale($locale);
    $languages = $all ? \App\Domain\Translation\Support\Locales::all() : [$locale];
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __("errors.{$code}.title") }} · {{ __('site.name') }}</title>
    <link rel="icon" href="/favicon.ico?v=2" sizes="48x48">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png?v=2">
    @vite(['resources/css/app.css'])
</head>
{{-- ADR-0014: error pages are always night: the code large in the display face, the circuit drawn in below, the
     message in each language asked for and the way home in the action colour. --}}
<body class="flex min-h-screen flex-col bg-night-900 text-night-ink antialiased" style="color-scheme: dark">
    <div aria-hidden="true" class="pointer-events-none fixed inset-0 -z-0 overflow-hidden">
        <x-hex-field id="error-hex" fade="right" class="absolute inset-y-0 end-0 h-full w-2/3 text-cyan-500/15" />
        <x-circuit :draw="true" class="absolute inset-x-0 bottom-14 h-40 w-full text-cyan-500/40 sm:h-56" />
    </div>
    <main class="relative mx-auto flex w-full max-w-page flex-1 flex-col justify-center px-4 py-16 sm:px-6">
        <a href="{{ url('/'.$locale) }}" class="mb-10 inline-flex w-fit items-center gap-3">
            <img src="/brand/emblem-128.webp" alt="" width="40" height="40" class="size-10">
            <img src="/brand/wordmark-dark.webp" alt="{{ __('site.name') }}" height="28" class="h-7 w-auto">
        </a>
        <p class="kicker [--color-eyebrow:var(--color-cyan-300)]">{{ __('errors.code', ['code' => $code]) }}</p>
        <p aria-hidden="true" class="mt-2 w-fit bg-linear-to-r from-cyan-300 to-saffron-400 bg-clip-text font-display text-8xl leading-none font-bold tracking-tighter text-transparent sm:text-9xl">{{ $code }}</p>
        @foreach ($languages as $language)
            <section lang="{{ $language }}" @class(['max-w-prose', 'mt-8' => $loop->first, 'mt-8 border-t border-night-line pt-8' => ! $loop->first])>
                <h1 class="heading-1 text-night-ink">{{ __("errors.{$code}.title", [], $language) }}</h1>
                <p class="mt-4 text-lg text-night-muted">{{ __("errors.{$code}.body", [], $language) }}</p>
            </section>
        @endforeach
        <p class="mt-10">
            <x-ui.button :href="url('/'.$locale)" variant="action">{{ __('errors.home') }}</x-ui.button>
        </p>
    </main>
    <p class="relative border-t border-night-line bg-night-950 px-4 py-4 text-center font-mono text-xs text-night-muted sm:px-6">{{ __('site.name') }}</p>
</body>
</html>
