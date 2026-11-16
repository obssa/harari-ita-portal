{{-- ADR-0010: the night band: a strip in the five pillar colours, the hexagon field, the lockup with the
     newsletter, then the pillars, the main listings, quick links, contact and the Agency's pages. --}}
<footer class="relative isolate mt-20 overflow-hidden bg-night-900 text-sm text-night-muted [&_a:hover]:text-night-ink">
    <div aria-hidden="true" class="grid h-1 grid-cols-5">
        <span class="bg-pillar-1"></span><span class="bg-pillar-2"></span><span class="bg-pillar-3"></span><span class="bg-pillar-4"></span><span class="bg-pillar-5"></span>
    </div>
    <x-hex-field id="footer-hex" fade="right" class="absolute inset-y-0 end-0 -z-10 h-full w-2/3 text-cyan-500/15" />

    <div class="mx-auto max-w-page px-4 sm:px-6">
        <div class="grid gap-10 border-b border-night-line py-12 lg:grid-cols-[1.2fr_1fr] lg:items-end">
            <div>
                <img src="/brand/lockup-horizontal-dark.png" alt="{{ __('site.name') }}" width="920" height="370" class="h-20 w-auto sm:h-24" loading="lazy">
                <p class="mt-5 max-w-xl text-base text-night-muted">{{ __('site.description') }}</p>
            </div>
            {{-- ADR-0051: a plain GET form, so cached pages need no session; the newsletter page completes it. --}}
            <form method="get" action="{{ route('newsletter.create') }}" class="w-full lg:justify-self-end lg:max-w-md">
                <label for="footer-newsletter" class="kicker [--color-eyebrow:var(--color-cyan-300)]">{{ __('newsletter.footer_title') }}</label>
                <div class="mt-3 flex gap-2">
                    <input id="footer-newsletter" name="email" type="email" autocomplete="email" dir="ltr" placeholder="{{ __('newsletter.footer_placeholder') }}"
                           class="min-w-0 flex-1 rounded-control border border-night-line bg-night-800 px-3.5 py-2.5 text-night-ink placeholder:text-night-muted/70 focus:border-cyan-400 focus:ring-2 focus:ring-cyan-400/30 focus:outline-none">
                    <button type="submit" class="rounded-control bg-saffron-500 px-4 py-2.5 font-semibold text-ink-brand hover:bg-saffron-400">{{ __('newsletter.footer_button') }}</button>
                </div>
            </form>
        </div>

        <div class="grid gap-10 py-12 sm:grid-cols-2 lg:grid-cols-5">
            @if ($pillars !== [])
                <section aria-labelledby="footer-pillars">
                    <h2 id="footer-pillars" class="kicker [--color-eyebrow:var(--color-cyan-300)]">{{ __('pillars.title') }}</h2>
                    <ul class="mt-4 space-y-2.5">
                        @foreach ($pillars as $pillar)
                            <li class="flex items-center gap-2.5">
                                <span aria-hidden="true" class="size-2 shrink-0 rotate-45 {{ [1 => 'bg-pillar-1', 2 => 'bg-pillar-2', 3 => 'bg-pillar-3', 4 => 'bg-pillar-4', 5 => 'bg-pillar-5'][$pillar['colour']] }}"></span>
                                <a href="{{ $pillar['url'] }}">{{ $pillar['label'] }}</a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <section aria-labelledby="footer-explore">
                <h2 id="footer-explore" class="kicker [--color-eyebrow:var(--color-cyan-300)]">{{ __('footer.explore') }}</h2>
                <ul class="mt-4 space-y-2.5">
                    @foreach ($explore as $link)
                        <li><a href="{{ $link['url'] }}">{{ $link['label'] }}</a></li>
                    @endforeach
                </ul>
            </section>

            @if ($links !== [])
                <section aria-labelledby="footer-links">
                    <h2 id="footer-links" class="kicker [--color-eyebrow:var(--color-cyan-300)]">{{ __('footer.links') }}</h2>
                    <ul class="mt-4 space-y-2.5">
                        @foreach ($links as $link)
                            <li><a href="{{ $link['url'] }}" @if ($link['external']) rel="noopener" @endif>{{ $link['label'] }}</a></li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <section aria-labelledby="footer-contact">
                <h2 id="footer-contact" class="kicker [--color-eyebrow:var(--color-cyan-300)]">{{ __('footer.contact') }}</h2>
                @if ($hasContact())
                    <dl class="mt-4 space-y-3">
                        @foreach ($contact as $label => $value)
                            <div>
                                <dt class="sr-only">{{ __($label) }}</dt>
                                <dd>{{ $value }}</dd>
                            </div>
                        @endforeach
                        @foreach (['footer.phone' => $phone, 'footer.hotline' => $hotline] as $label => $number)
                            @if ($number)
                                <div>
                                    <dt class="font-mono text-xs uppercase">{{ __($label) }}</dt>
                                    <dd><a class="text-night-ink" href="tel:{{ $tel($number) }}">{{ $number }}</a></dd>
                                </div>
                            @endif
                        @endforeach
                        @if ($email)
                            <div>
                                <dt class="font-mono text-xs uppercase">{{ __('footer.email') }}</dt>
                                <dd><a class="break-all text-night-ink" href="mailto:{{ $email }}">{{ $email }}</a></dd>
                            </div>
                        @endif
                    </dl>
                @else
                    <p class="mt-4">{{ __('footer.contact_soon') }}</p>
                @endif
            </section>

            @if ($social !== [])
                <section aria-labelledby="footer-follow">
                    <h2 id="footer-follow" class="kicker [--color-eyebrow:var(--color-cyan-300)]">{{ __('footer.follow') }}</h2>
                    <ul class="mt-4 flex flex-wrap gap-2">
                        @foreach ($social as $platform => $link)
                            <li>
                                <a href="{{ $link['url'] }}" rel="noopener me" class="inline-flex size-10 items-center justify-center rounded-control border border-night-line text-night-ink transition-colors hover:border-cyan-400">
                                    <x-social-icon :platform="$platform" class="size-[1.1rem]" />
                                    <span class="sr-only">{{ $link['name'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    </div>

    <div class="border-t border-night-line bg-night-950">
        <div class="mx-auto flex max-w-page flex-wrap items-center justify-between gap-3 px-4 py-4 font-mono text-xs sm:px-6">
            <p>{{ __('footer.copyright', ['years' => $years, 'name' => __('site.name')]) }}</p>
            <div class="flex items-center gap-4">
                @unless (app()->environment('production'))
                    <p class="text-saffron-300">{{ __('footer.test_site') }}</p>
                @endunless
                <a href="#content" class="inline-flex items-center gap-1.5">
                    {{ __('footer.back_to_top') }}
                    <svg aria-hidden="true" class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 19V5M6 11l6-6 6 6"/></svg>
                </a>
            </div>
        </div>
    </div>
</footer>
