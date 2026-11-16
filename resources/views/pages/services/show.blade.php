@use('App\Domain\Organisation\Queries\PublicDirectory')
@php $locale = app()->getLocale(); @endphp
<x-layouts.public :title="$translation->title" :description="$translation->summary" :alternates="$alternates" :json-ld="$jsonLd">
    {{-- ADR-0016: the service opens on night with the way to start it; the page reads in the order people need it:
         who can use it, what to bring, the steps, then details and questions; the facts sit in a card beside. --}}
    <x-page-hero :title="$translation->title" :lead="$translation->summary" :kicker="$category ?? __('services.title')" tone="night"
                 :crumbs="[[__('services.title'), route('services.index')]]" :heading-lang="$translation->locale">
        <x-slot:actions>
            @if ($service->isOnline())
                <x-ui.button :href="$service->external_url" variant="action" size="lg" target="_blank" rel="noopener">
                    {{ __('services.start_online') }}
                    <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7M9 7h8v8"/></svg>
                    <span class="sr-only">({{ __('services.opens_external') }})</span>
                </x-ui.button>
            @endif
        </x-slot:actions>
    </x-page-hero>

    <div class="mx-auto max-w-page px-4 pt-10 pb-16 sm:px-6">
        @if ($service->proposed)
            <x-ui.alert tone="warning" :title="__('services.proposed')" class="mb-10 max-w-3xl">{{ __('services.proposed_notice') }}</x-ui.alert>
        @endif
        <div class="grid gap-12 lg:grid-cols-[1fr_22rem]">
            <div class="min-w-0 space-y-12" lang="{{ $translation->locale }}">
                @if (filled($translation->eligibility))
                    <section aria-labelledby="service-eligibility">
                        <h2 id="service-eligibility" class="heading-2">{{ __('services.eligibility') }}</h2>
                        <p class="mt-4 max-w-prose text-lg leading-relaxed whitespace-pre-line">{{ $translation->eligibility }}</p>
                    </section>
                @endif

                @if ($translation->documentList() !== [])
                    <section aria-labelledby="service-documents">
                        <h2 id="service-documents" class="heading-2">{{ __('services.documents') }}</h2>
                        <ul class="mt-5 grid gap-2 sm:grid-cols-2">
                            @foreach ($translation->documentList() as $document)
                                <li class="flex items-start gap-3 rounded-control border border-line bg-surface px-4 py-3">
                                    <svg aria-hidden="true" class="mt-0.5 size-5 shrink-0 text-cyan-600 dark:text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 14l2 2 4-4"/></svg>
                                    <span>{{ $document }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if ($translation->stepList() !== [])
                    <section aria-labelledby="service-steps">
                        <h2 id="service-steps" class="heading-2">{{ __('services.steps') }}</h2>
                        <ol class="relative mt-6 space-y-6 before:absolute before:inset-y-2 before:start-4 before:w-px before:bg-line">
                            @foreach ($translation->stepList() as $step)
                                <li class="relative flex gap-4">
                                    <span aria-hidden="true" class="relative grid size-8 shrink-0 place-items-center rounded-control bg-night-900 font-mono text-sm text-cyan-300 ring-4 ring-canvas">{{ sprintf('%02d', $loop->iteration) }}</span>
                                    <p class="pt-1 text-lg leading-relaxed">{{ $step }}</p>
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif

                @if (trim($body) !== '')
                    <section aria-labelledby="service-details">
                        <h2 id="service-details" class="heading-2">{{ __('services.details') }}</h2>
                        <div class="prose-content mt-5 max-w-prose">{!! $body !!}</div>
                    </section>
                @endif

                @if ($translation->faqList() !== [])
                    <section aria-labelledby="service-faq">
                        <h2 id="service-faq" class="heading-2">{{ __('services.faq') }}</h2>
                        <div class="mt-5 divide-y divide-line rounded-card border border-line bg-surface">
                            @foreach ($translation->faqList() as $item)
                                <details class="group px-5 py-4 [&_summary::-webkit-details-marker]:hidden">
                                    <summary class="flex cursor-pointer items-center justify-between gap-4 font-display text-lg font-semibold tracking-tight">
                                        {{ $item['question'] }}
                                        <svg aria-hidden="true" class="size-5 shrink-0 text-cyan-600 transition-transform group-open:rotate-45 dark:text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                                    </summary>
                                    <p class="mt-3 max-w-prose whitespace-pre-line text-ink-muted">{{ $item['answer'] }}</p>
                                </details>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            <aside class="space-y-6 lg:sticky lg:top-24 lg:self-start" aria-label="{{ __('services.at_a_glance') }}">
                <section class="relative isolate overflow-hidden rounded-card border border-night-line bg-night-900 p-6 text-night-ink">
                    <x-hex-field id="service-glance-hex" fade="top" class="absolute inset-x-0 top-0 -z-10 h-32 w-full text-cyan-500/20" />
                    <h2 class="kicker [--color-eyebrow:var(--color-cyan-300)]">{{ __('services.at_a_glance') }}</h2>
                    <dl class="mt-4 space-y-4 text-sm">
                        <div>
                            <dt class="font-mono text-[11px] tracking-wider text-night-muted uppercase">{{ __('services.channels') }}</dt>
                            <dd class="mt-1.5 flex flex-wrap gap-1.5">
                                @foreach ($service->channelList() as $channel)
                                    <span class="rounded-md border border-night-line px-2 py-0.5 font-mono text-xs uppercase">{{ $channel->label() }}</span>
                                @endforeach
                            </dd>
                        </div>
                        @if (filled($translation->fees))
                            <div>
                                <dt class="font-mono text-[11px] tracking-wider text-night-muted uppercase">{{ __('services.fees') }}</dt>
                                <dd class="mt-1 text-base">{{ $translation->fees }}</dd>
                            </div>
                        @endif
                        @if (filled($translation->duration))
                            <div>
                                <dt class="font-mono text-[11px] tracking-wider text-night-muted uppercase">{{ __('services.duration') }}</dt>
                                <dd class="mt-1 text-base">{{ $translation->duration }}</dd>
                            </div>
                        @endif
                        @if ($service->audienceList() !== [])
                            <div>
                                <dt class="font-mono text-[11px] tracking-wider text-night-muted uppercase">{{ __('services.audiences') }}</dt>
                                <dd class="mt-1">{{ implode(' · ', array_map(fn ($a) => $a->label(), $service->audienceList())) }}</dd>
                            </div>
                        @endif
                    </dl>
                    @if ($service->isOnline())
                        <p class="mt-6"><x-ui.button :href="$service->external_url" variant="action" class="w-full" target="_blank" rel="noopener">{{ __('services.start_online') }} <span class="sr-only">({{ __('services.opens_external') }})</span></x-ui.button></p>
                    @endif
                </section>

                @if ($office)
                    <section class="rounded-card border border-line bg-surface p-5" lang="{{ $office->locale }}">
                        <h2 class="kicker">{{ __('services.office') }}</h2>
                        <p class="mt-2 font-display text-lg font-semibold tracking-tight">
                            <a href="{{ route('offices.show', ['locale' => $office->locale, 'slug' => $office->slug]) }}" class="underline-offset-4 hover:underline">{{ $office->getAttribute('name') }}</a>
                        </p>
                        @if (filled($office->getAttribute('address')))
                            <p class="mt-1 text-sm whitespace-pre-line text-ink-muted">{{ $office->getAttribute('address') }}</p>
                        @endif
                        @if (filled($office->getAttribute('hours')))
                            <p class="mt-1 text-sm text-ink-muted">{{ $office->getAttribute('hours') }}</p>
                        @endif
                        <p class="mt-2 space-x-3 text-sm" dir="ltr">
                            @if ($service->office?->phone)<a href="tel:{{ preg_replace('/[^0-9+]/', '', $service->office->phone) }}" class="text-link hover:underline">{{ $service->office->phone }}</a>@endif
                            @if ($service->office?->email)<a href="mailto:{{ $service->office->email }}" class="text-link hover:underline">{{ $service->office->email }}</a>@endif
                        </p>
                    </section>
                @endif

                @if ($service->reviewed_at)
                    <p class="font-mono text-xs text-ink-muted">{{ __('services.reviewed') }}: <x-date :date="$service->reviewed_at" /></p>
                @endif
            </aside>
        </div>

        @if ($related->isNotEmpty())
            <section aria-labelledby="service-related" class="mt-16 border-t border-line pt-10">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <h2 id="service-related" class="heading-2">{{ __('services.related') }}</h2>
                    <a href="{{ route('services.index') }}" class="font-semibold text-link">{{ __('services.all') }}</a>
                </div>
                <ul class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($related as $item)
                        @php $shown = PublicDirectory::shown($item, $locale); @endphp
                        @continue($shown === null)
                        <li><x-service-card :service="$item" :translation="$shown" :heading-level="3" /></li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-layouts.public>
