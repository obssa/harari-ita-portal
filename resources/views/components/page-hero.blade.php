@props(['title', 'lead' => null, 'kicker' => null, 'tone' => 'plaster', 'crumbs' => [], 'headingLang' => null])
@php
    // ADR-0012: two looks. Night (night, ink, primary, ruby, cyan): the dark band with the hexagon field.
    // Light (plaster, saffron): a calm band with the field faint at the end; saffron adds the orange bar.
    $dark = in_array($tone, ['night', 'ink', 'ruby', 'primary', 'cyan'], true);
@endphp
<section {{ $attributes->class([
    'relative isolate overflow-hidden border-b',
    'border-night-line bg-night-900 text-night-ink' => $dark,
    'border-line bg-surface-muted' => ! $dark,
]) }}>
    <x-hex-field id="page-hex" fade="right" :class="\Illuminate\Support\Arr::toCssClasses([
        'absolute inset-y-0 end-0 -z-10 h-full w-3/5',
        'text-cyan-500/20' => $dark,
        'text-primary-700/10 dark:text-cyan-500/15' => ! $dark,
    ])" />
    @if ($tone === 'saffron')
        <span aria-hidden="true" class="absolute inset-x-0 top-0 h-1 bg-saffron-500"></span>
    @endif
    <div class="mx-auto flex max-w-page flex-col gap-5 px-4 pt-8 pb-10 sm:px-6 sm:pt-10 sm:pb-12">
        <nav aria-label="{{ __('news.breadcrumb') }}">
            <ol @class([
                'flex flex-wrap items-center gap-x-2 gap-y-1 font-mono text-xs tracking-wide uppercase',
                'text-night-muted [&_a:hover]:text-night-ink' => $dark,
                'text-ink-muted [&_a:hover]:text-ink' => ! $dark,
            ])>
                <li><a href="{{ route('home') }}">{{ __('nav.home') }}</a></li>
                @foreach ($crumbs as $crumb)
                    <li class="flex items-center gap-2">
                        <span aria-hidden="true" @class(['text-cyan-400' => $dark, 'text-cyan-700 dark:text-cyan-300' => ! $dark])>/</span>
                        <a href="{{ $crumb[1] }}">{{ $crumb[0] }}</a>
                    </li>
                @endforeach
                <li class="flex min-w-0 items-center gap-2">
                    <span aria-hidden="true" @class(['text-cyan-400' => $dark, 'text-cyan-700 dark:text-cyan-300' => ! $dark])>/</span>
                    <span aria-current="page" @class(['line-clamp-1 normal-case', 'text-night-ink' => $dark, 'text-ink' => ! $dark])>{{ $title }}</span>
                </li>
            </ol>
        </nav>
        <div class="flex flex-wrap items-end justify-between gap-x-8 gap-y-5">
            <div class="max-w-3xl">
                @if ($kicker)
                    <p @class(['kicker', '[--color-eyebrow:var(--color-cyan-300)]' => $dark])>{{ $kicker }}</p>
                @endif
                <h1 @if ($headingLang) lang="{{ $headingLang }}" @endif @class(['heading-1', 'mt-3' => $kicker, 'text-night-ink' => $dark])>{{ $title }}</h1>
                @if ($lead)
                    <p @class(['mt-4 max-w-prose text-lg', 'text-night-muted' => $dark, 'text-ink-muted' => ! $dark])>{{ $lead }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex flex-wrap gap-2">{{ $actions }}</div>
            @endisset
        </div>
        @if (trim((string) $slot) !== '')
            <div>{{ $slot }}</div>
        @endif
    </div>
</section>
