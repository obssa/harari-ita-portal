@use('App\Domain\Organisation\Queries\PublicDirectory')
@php $locale = app()->getLocale(); @endphp
<x-layouts.public :title="__('innovation.programmes.title')" :description="__('innovation.programmes.description')">
    {{-- ADR-0021: the programmes, each with its kind, its pitch and whether a cohort is taking applications now. --}}
    <x-page-hero :title="__('innovation.programmes.title')" :lead="__('innovation.programmes.description')" :kicker="__('nav.innovation')" tone="night" :crumbs="[]" />
    <div class="mx-auto max-w-page px-4 pt-2 pb-16 sm:px-6">
        @if ($programmes->isEmpty())
            <x-ui.empty-state class="mt-10">{{ __('innovation.programmes.empty') }}</x-ui.empty-state>
        @else
            <ul class="mt-10 grid gap-4 sm:grid-cols-2">
                @foreach ($programmes as $programme)
                    @php
                        $shown = PublicDirectory::shown($programme, $locale);
                        $open = $programme->openCohort();
                        $next = $open === null ? $programme->nextCohort() : null;
                    @endphp
                    @continue($shown === null)
                    <li lang="{{ $shown->locale }}">
                        <article class="group relative flex h-full flex-col gap-4 rounded-card border border-line bg-surface p-7 transition duration-200 hover:-translate-y-0.5 hover:border-cyan-500 hover:shadow-lg hover:shadow-night-950/5">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="kicker">{{ $programme->kind->label() }}</p>
                                @if ($open)
                                    <x-ui.status-pill state="ok">{{ __('innovation.programmes.open') }}</x-ui.status-pill>
                                @elseif ($next)
                                    <x-ui.status-pill state="maintenance">{{ __('innovation.programmes.opens_soon') }}</x-ui.status-pill>
                                @endif
                            </div>
                            <h2 class="font-display text-2xl font-semibold tracking-tight">
                                <a href="{{ route('programmes.show', ['locale' => $shown->locale, 'slug' => $shown->slug]) }}" class="after:absolute after:inset-0 group-hover:underline underline-offset-4">{{ $shown->getAttribute('title') }}</a>
                            </h2>
                            @if (filled($shown->getAttribute('summary')))
                                <p class="text-ink-muted">{{ $shown->getAttribute('summary') }}</p>
                            @endif
                            @if ($open || $next || $programme->partner)
                                <dl class="mt-auto grid gap-1 border-t border-line pt-4 font-mono text-xs text-ink-muted">
                                    @if ($open)
                                        <div class="flex gap-2"><dt>{{ __('innovation.field.closes_at') }}:</dt><dd class="text-ink"><x-date :date="$open->closes_at" /></dd></div>
                                    @elseif ($next)
                                        <div class="flex gap-2"><dt>{{ __('innovation.field.opens_at') }}:</dt><dd class="text-ink"><x-date :date="$next->opens_at" /></dd></div>
                                    @endif
                                    @if ($programme->partner)
                                        <div class="flex gap-2"><dt>{{ __('innovation.field.partner') }}:</dt><dd class="text-ink">{{ $programme->partner }}</dd></div>
                                    @endif
                                </dl>
                            @endif
                        </article>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-layouts.public>
