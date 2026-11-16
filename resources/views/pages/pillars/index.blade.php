@use('App\Domain\Organisation\Queries\PublicDirectory')
@php $locale = app()->getLocale(); @endphp
<x-layouts.public :title="__('pillars.title')" :description="__('pillars.description')">
    <x-page-hero :title="__('pillars.title')" :lead="__('pillars.description')" :kicker="__('homepage.section_kicker.pillars')" tone="night" :crumbs="[]" />
    <div class="mx-auto max-w-page px-4 pt-2 pb-16 sm:px-6">

        @if ($pillars->isEmpty())
            <x-ui.empty-state class="mt-10">{{ __('directory.empty') }}</x-ui.empty-state>
        @else
            {{-- ADR-0012: the pillars as gates, as on the homepage: a hairline card with the arch, a colour node, a mono
                 number and a colour edge that lights on hover. --}}
            <ul class="mt-10 grid gap-4 sm:grid-cols-2">
                @foreach ($pillars as $pillar)
                    @php
                        $shown = PublicDirectory::shown($pillar, $locale);
                        $tone = $pillar->accentTone();
                        $node = match ($tone) { 'cyan' => 'bg-cyan-500', 'saffron' => 'bg-saffron-500', 'ruby' => 'bg-ruby-600', 'night' => 'bg-night-ink', default => 'bg-primary-500' };
                        $edge = match ($tone) { 'cyan' => 'hover:border-cyan-500', 'saffron' => 'hover:border-saffron-500', 'ruby' => 'hover:border-ruby-600', 'night' => 'hover:border-night-700', default => 'hover:border-primary-500' };
                        $arch = match ($tone) { 'cyan' => 'text-cyan-500', 'saffron' => 'text-saffron-500', 'ruby' => 'text-ruby-600', 'night' => 'text-night-700', default => 'text-primary-500' };
                    @endphp
                    @continue($shown === null)
                    <li lang="{{ $shown->locale }}">
                        <article class="reveal group relative flex h-full min-h-64 flex-col gap-6 overflow-hidden rounded-card border border-line bg-surface p-8 transition duration-200 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-night-950/5 {{ $edge }}">
                            <span aria-hidden="true" class="absolute inset-x-0 top-0 h-1 origin-left scale-x-0 transition-transform duration-300 group-hover:scale-x-100 rtl:origin-right {{ $node }}"></span>
                            <svg aria-hidden="true" class="pointer-events-none absolute -end-6 -bottom-10 h-52 w-auto opacity-[0.08] {{ $arch }}" viewBox="0 0 80 100" fill="none" stroke="currentColor" stroke-width="6"><path d="M10 100V40Q10 12 40 4q30 8 30 36v60"/></svg>
                            <div class="flex items-center gap-3">
                                <span aria-hidden="true" class="size-3 rotate-45 {{ $node }}"></span>
                                <span class="font-mono text-xs text-ink-muted">{{ sprintf('%02d', $loop->iteration) }}</span>
                            </div>
                            <div class="relative">
                                <h2 class="font-display text-3xl font-bold tracking-tight">
                                    <a href="{{ route('pillars.show', ['locale' => $shown->locale, 'slug' => $shown->slug]) }}" class="after:absolute after:inset-0">{{ $shown->getAttribute('name') }}</a>
                                </h2>
                                @if (filled($shown->getAttribute('summary')))
                                    <p class="mt-3 max-w-prose text-lg text-ink-muted">{{ $shown->getAttribute('summary') }}</p>
                                @endif
                            </div>
                            <p class="relative mt-auto inline-flex items-center gap-2 font-mono text-xs tracking-wide text-cyan-700 uppercase dark:text-cyan-300">
                                {{ __('homepage.visit') }}
                                <svg aria-hidden="true" class="size-4 transition-transform group-hover:translate-x-1 rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                            </p>
                        </article>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-layouts.public>
