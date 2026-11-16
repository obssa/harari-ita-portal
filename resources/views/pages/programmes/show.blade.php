<x-layouts.public :title="$translation->title" :description="$translation->summary" :alternates="$alternates" :json-ld="$jsonLd">
    {{-- ADR-0021: a programme: who it is for, what it gives, its timeline, and the open cohort with the way to apply. --}}
    <x-page-hero :title="$translation->title" :lead="$translation->summary" :kicker="$programme->kind->label()" tone="night"
                 :crumbs="[[__('innovation.programmes.title'), route('programmes.index')]]" :heading-lang="$translation->locale">
        @if ($open)
            <x-slot:actions>
                <x-ui.button :href="route('applications.create', ['slug' => $translation->slug])" variant="action" size="lg">{{ __('innovation.programmes.apply') }}</x-ui.button>
            </x-slot:actions>
        @endif
    </x-page-hero>

    <div class="mx-auto max-w-page px-4 pt-10 pb-16 sm:px-6">
        <div class="grid gap-12 lg:grid-cols-[1fr_22rem]">
            <div class="min-w-0 space-y-12" lang="{{ $translation->locale }}">
                @if (filled($translation->eligibility))
                    <section aria-labelledby="programme-eligibility">
                        <h2 id="programme-eligibility" class="heading-2">{{ __('innovation.field.eligibility') }}</h2>
                        <p class="mt-4 max-w-prose text-lg leading-relaxed whitespace-pre-line">{{ $translation->eligibility }}</p>
                    </section>
                @endif
                @if ($translation->benefitList() !== [])
                    <section aria-labelledby="programme-benefits">
                        <h2 id="programme-benefits" class="heading-2">{{ __('innovation.field.benefits') }}</h2>
                        <ul class="mt-5 grid gap-2 sm:grid-cols-2">
                            @foreach ($translation->benefitList() as $benefit)
                                <li class="flex items-start gap-3 rounded-control border border-line bg-surface px-4 py-3">
                                    <svg aria-hidden="true" class="mt-0.5 size-5 shrink-0 text-cyan-600 dark:text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                    <span>{{ $benefit }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
                @if ($translation->timelineList() !== [])
                    <section aria-labelledby="programme-timeline">
                        <h2 id="programme-timeline" class="heading-2">{{ __('innovation.field.timeline') }}</h2>
                        <ol class="relative mt-6 space-y-6 before:absolute before:inset-y-2 before:start-4 before:w-px before:bg-line">
                            @foreach ($translation->timelineList() as $stage)
                                <li class="relative flex gap-4">
                                    <span aria-hidden="true" class="relative grid size-8 shrink-0 place-items-center rounded-control bg-night-900 font-mono text-sm text-cyan-300 ring-4 ring-canvas">{{ sprintf('%02d', $loop->iteration) }}</span>
                                    <p class="pt-1 text-lg leading-relaxed">{{ $stage }}</p>
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif
                @if (trim($body) !== '')
                    <section aria-labelledby="programme-details">
                        <h2 id="programme-details" class="heading-2">{{ __('innovation.field.body') }}</h2>
                        <div class="prose-content mt-5 max-w-prose">{!! $body !!}</div>
                    </section>
                @endif
            </div>

            <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start" aria-label="{{ __('innovation.programmes.cohort') }}">
                <section class="relative isolate overflow-hidden rounded-card border border-night-line bg-night-900 p-6 text-night-ink">
                    <x-hex-field id="programme-hex" fade="top" class="absolute inset-x-0 top-0 -z-10 h-32 w-full text-cyan-500/20" />
                    <h2 class="kicker [--color-eyebrow:var(--color-cyan-300)]">{{ __('innovation.programmes.cohort') }}</h2>
                    @if ($open)
                        <p class="mt-2 font-display text-xl font-semibold tracking-tight">{{ $open->name }}</p>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="font-mono text-[11px] tracking-wider text-night-muted uppercase">{{ __('innovation.field.closes_at') }}</dt>
                                <dd class="mt-1 text-base"><x-date :date="$open->closes_at" :time="true" /></dd>
                            </div>
                            @if ($open->starts_on)
                                <div>
                                    <dt class="font-mono text-[11px] tracking-wider text-night-muted uppercase">{{ __('innovation.field.starts_on') }}</dt>
                                    <dd class="mt-1 text-base"><x-date :date="$open->starts_on" /></dd>
                                </div>
                            @endif
                            @if ($open->places)
                                <div>
                                    <dt class="font-mono text-[11px] tracking-wider text-night-muted uppercase">{{ __('innovation.field.places') }}</dt>
                                    <dd class="mt-1 text-base">{{ $open->places }}</dd>
                                </div>
                            @endif
                        </dl>
                        <p class="mt-6"><x-ui.button :href="route('applications.create', ['slug' => $translation->slug])" variant="action" class="w-full">{{ __('innovation.programmes.apply') }}</x-ui.button></p>
                    @elseif ($next)
                        <p class="mt-2 text-night-muted">{{ __('innovation.programmes.next', ['name' => $next->name]) }}</p>
                        <p class="mt-1 font-display text-lg"><x-date :date="$next->opens_at" /></p>
                    @else
                        <p class="mt-2 text-night-muted">{{ __('innovation.programmes.none_open') }}</p>
                    @endif
                </section>
                @if ($programme->partner)
                    <p class="font-mono text-xs text-ink-muted">{{ __('innovation.field.partner') }}: {{ $programme->partner }}</p>
                @endif
                <p class="text-sm"><a href="{{ route('applications.lookup') }}" class="text-link underline underline-offset-4">{{ __('innovation.track.application_title') }}</a></p>
            </aside>
        </div>
    </div>
</x-layouts.public>
