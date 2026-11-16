@if ($variant === 'drawer')
    {{-- Phones and tablets (ADR-0009): one square button opens a sheet under the header with search and the
         sections, each opening in place with its numbered links. No script needed. --}}
    <details name="site-nav" class="group/menu">
        <summary class="inline-flex size-10 cursor-pointer list-none items-center justify-center rounded-control bg-night-900 text-night-ink transition-colors hover:bg-primary-800 dark:bg-night-700 dark:hover:bg-night-600 group-open/menu:bg-saffron-500 group-open/menu:text-ink-brand [&::-webkit-details-marker]:hidden">
            <svg aria-hidden="true" class="size-5 group-open/menu:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h10M4 17h16"/></svg>
            <svg aria-hidden="true" class="hidden size-5 group-open/menu:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
            <span class="sr-only">{{ __('nav.menu') }}</span>
        </summary>
        <div class="absolute inset-x-0 top-full z-40 max-h-[calc(100dvh-4rem)] overflow-y-auto overscroll-contain border-t border-line bg-surface shadow-2xl shadow-night-950/20">
            <div class="mx-auto max-w-page px-4 pt-4 pb-8 sm:px-6">
                @unless (request()->routeIs('search'))
                    <form method="get" action="{{ route('search') }}" role="search" class="flex gap-2">
                        <label for="drawer-search" class="sr-only">{{ __('search.label') }}</label>
                        <input id="drawer-search" type="search" name="q" maxlength="100" minlength="2" required placeholder="{{ __('search.short') }}"
                               class="min-w-0 flex-1 rounded-control border border-line bg-surface-muted px-4 py-2.5 text-base focus:border-cyan-600 focus:ring-2 focus:ring-cyan-500/30 focus:outline-none">
                        <button type="submit" class="inline-flex size-11 shrink-0 items-center justify-center rounded-control bg-primary-700 text-white hover:bg-primary-800">
                            <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                            <span class="sr-only">{{ __('search.button') }}</span>
                        </button>
                    </form>
                @endunless

                <nav aria-label="{{ __('nav.main') }}" class="mt-4">
                    <ul class="divide-y divide-line border-y border-line">
                        @foreach ($items as $i => $item)
                            <li>
                                @if ($item['children'] !== [])
                                    <details name="nav-section" class="group/section" @if ($item['active']) open @endif>
                                        <summary class="flex cursor-pointer list-none items-center gap-3 py-3.5 [&::-webkit-details-marker]:hidden">
                                            <span aria-hidden="true" class="font-mono text-xs text-cyan-700 dark:text-cyan-300">{{ sprintf('%02d', $i + 1) }}</span>
                                            <span @class(['flex-1 font-display text-lg font-semibold', 'text-primary-700 dark:text-cyan-300' => $item['active']])>{{ $item['label'] }}</span>
                                            <svg aria-hidden="true" class="size-4 shrink-0 text-ink-muted transition-transform group-open/section:rotate-45" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                                        </summary>
                                        <ul class="grid gap-px pb-3 ps-7 sm:grid-cols-2">
                                            <li class="sm:col-span-2">
                                                <a href="{{ $item['url'] }}" class="inline-flex items-center gap-1.5 py-2 text-sm font-semibold text-link">
                                                    {{ __('nav.section_all', ['section' => $item['label']]) }}
                                                    <svg aria-hidden="true" class="size-4 rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                                                </a>
                                            </li>
                                            @foreach ($item['children'] as $child)
                                                <li>
                                                    <a href="{{ $child['url'] }}" @if ($child['external']) rel="noopener" @endif class="block rounded-control border-s-2 border-transparent px-3 py-2 hover:border-cyan-500 hover:bg-surface-muted">
                                                        <span class="block font-medium text-ink">{{ $child['label'] }}</span>
                                                        @if (filled($child['hint'] ?? null))
                                                            <span class="block text-xs text-ink-muted">{{ $child['hint'] }}</span>
                                                        @endif
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </details>
                                @else
                                    <a href="{{ $item['url'] }}" @if ($item['active']) aria-current="page" @endif @if ($item['external']) rel="noopener" @endif class="flex items-center gap-3 py-3.5">
                                        <span aria-hidden="true" class="font-mono text-xs text-cyan-700 dark:text-cyan-300">{{ sprintf('%02d', $i + 1) }}</span>
                                        <span @class(['flex-1 font-display text-lg font-semibold', 'text-primary-700 dark:text-cyan-300' => $item['active']])>{{ $item['label'] }}</span>
                                        <svg aria-hidden="true" class="size-4 shrink-0 text-ink-muted rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </nav>

                @php $socialLinks = \App\Support\SocialLinks::all(); @endphp
                @if ($socialLinks !== [])
                    <ul class="mt-6 flex flex-wrap items-center gap-2">
                        @foreach ($socialLinks as $platform => $link)
                            <li>
                                <a href="{{ $link['url'] }}" rel="noopener me" class="inline-flex size-10 items-center justify-center rounded-control border border-line text-ink-muted transition-colors hover:border-cyan-500 hover:text-ink">
                                    <x-social-icon :platform="$platform" class="size-5" />
                                    <span class="sr-only">{{ $link['name'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </details>
@else
    {{-- Desktops (ADR-0009): each section opens a full-width panel under the header: a night card with the
         hexagon field introduces the section, the links follow numbered. name="site-nav" closes the others. --}}
    <nav aria-label="{{ __('nav.main') }}">
        <ul class="flex items-center gap-0.5 text-[0.9375rem] font-medium">
            @foreach ($items as $item)
                <li>
                    @if ($item['children'] !== [])
                        <details name="site-nav" class="group">
                            <summary @class([
                                'relative inline-flex cursor-pointer list-none items-center gap-1.5 rounded-control px-3 py-2 transition-colors duration-150 [&::-webkit-details-marker]:hidden',
                                'after:absolute after:inset-x-3 after:-bottom-[0.95rem] after:h-0.5 after:bg-cyan-500 text-primary-700 dark:text-cyan-300' => $item['active'],
                                'text-ink hover:bg-surface-muted group-open:bg-surface-muted' => ! $item['active'],
                            ])>
                                {{ $item['label'] }}
                                <svg aria-hidden="true" class="size-3 opacity-60 transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="m6 9 6 6 6-6"/></svg>
                            </summary>
                            <div class="absolute inset-x-0 top-full z-40 border-y border-line bg-surface shadow-2xl shadow-night-950/15">
                                <div class="mx-auto grid max-w-page gap-8 px-4 py-8 sm:px-6 lg:grid-cols-[20rem_1fr]">
                                    <div class="relative isolate flex flex-col gap-3 overflow-hidden rounded-card bg-night-900 p-6 text-night-ink">
                                        <x-hex-field id="nav-hex-{{ $loop->index }}" fade="right" class="absolute inset-0 -z-10 size-full text-cyan-500/25" />
                                        <p class="kicker [--color-eyebrow:var(--color-cyan-300)]">{{ sprintf('%02d', $loop->iteration) }} / {{ sprintf('%02d', $loop->count) }}</p>
                                        <p class="font-display text-2xl font-semibold tracking-tight">{{ $item['label'] }}</p>
                                        @if (filled($item['description'] ?? null))
                                            <p class="text-sm text-night-muted">{{ $item['description'] }}</p>
                                        @endif
                                        <a href="{{ $item['url'] }}" class="mt-auto inline-flex items-center gap-1.5 pt-2 text-sm font-semibold text-cyan-300 hover:text-white">
                                            {{ __('nav.section_all', ['section' => $item['label']]) }}
                                            <svg aria-hidden="true" class="size-4 rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                                        </a>
                                    </div>
                                    <ul class="grid content-start gap-x-6 gap-y-1 sm:grid-cols-2 xl:grid-cols-3">
                                        @foreach ($item['children'] as $child)
                                            <li>
                                                <a href="{{ $child['url'] }}" @if ($child['external']) rel="noopener" @endif
                                                   class="group/link flex h-full gap-3 rounded-control border-s-2 border-transparent px-3 py-3 transition-colors duration-150 hover:border-cyan-500 hover:bg-surface-muted">
                                                    <span aria-hidden="true" class="pt-0.5 font-mono text-xs text-ink-muted group-hover/link:text-cyan-700 dark:group-hover/link:text-cyan-300">{{ sprintf('%02d', $loop->iteration) }}</span>
                                                    <span>
                                                        <span class="block font-semibold text-ink">{{ $child['label'] }}</span>
                                                        @if (filled($child['hint'] ?? null))
                                                            <span class="mt-0.5 block text-sm text-ink-muted">{{ $child['hint'] }}</span>
                                                        @endif
                                                    </span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </details>
                    @else
                        <a href="{{ $item['url'] }}" @if ($item['active']) aria-current="page" @endif @if ($item['external']) rel="noopener" @endif
                           @class([
                               'relative inline-block rounded-control px-3 py-2 transition-colors duration-150',
                               'after:absolute after:inset-x-3 after:-bottom-[0.95rem] after:h-0.5 after:bg-cyan-500 text-primary-700 dark:text-cyan-300' => $item['active'],
                               'text-ink hover:bg-surface-muted' => ! $item['active'],
                           ])>{{ $item['label'] }}</a>
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>
@endif
