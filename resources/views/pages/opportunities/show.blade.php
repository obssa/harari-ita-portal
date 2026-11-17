@use('App\Domain\Opportunities\Enums\AddendumKind')
@use('App\Domain\Opportunities\Models\Opportunity')
@use('App\Domain\Organisation\Queries\PublicDirectory')
@php
    $locale = app()->getLocale();
    $state = $opportunity->state();
    $open = $opportunity->isOpen();
    $cancellation = $addenda->first(fn ($a) => $a->kind === AddendumKind::Cancellation);
    $dt = 'font-mono text-[11px] tracking-wider text-night-muted uppercase';
@endphp
<x-layouts.public :title="$translation->title" :description="$translation->summary" :alternates="$alternates" :json-ld="$jsonLd">
    {{-- ADR-0032, ADR-0033: an opportunity: its facts and deadline beside the text, the documents, and the dated log of
         addenda, clarifications, extensions and the cancellation, newest first. --}}
    <x-page-hero :title="$translation->title" :lead="$translation->summary" :kicker="$opportunity->type->label()" tone="night"
                 :crumbs="[[__('opportunities.title'), route('opportunities.index')], [$opportunity->type->plural(), route('opportunities.index', ['type' => $opportunity->type->value])]]" :heading-lang="$translation->locale">
        <ul class="flex flex-wrap items-center gap-2" aria-label="{{ __('opportunities.facts') }}">
            <li><x-ui.status-pill :state="match ($state) { 'open' => 'ok', 'closing' => 'degraded', 'upcoming' => 'maintenance', default => 'down' }" tone="night">{{ __('opportunities.state.'.$state) }}</x-ui.status-pill></li>
            @if (filled($opportunity->reference))
                <li><x-ui.badge tone="ink"><span dir="ltr">{{ $opportunity->reference }}</span></x-ui.badge></li>
            @endif
            @if ($addenda->isNotEmpty())
                <li><a href="#opportunity-log" class="font-mono text-xs text-cyan-300 underline-offset-4 hover:underline">{{ trans_choice('opportunities.addendum.count', $addenda->count(), ['count' => $addenda->count()]) }}</a></li>
            @endif
        </ul>
        @if ($open && ($opportunity->apply_url || $opportunity->apply_email))
            <x-slot:actions>
                @if ($opportunity->apply_url)
                    <x-ui.button :href="$opportunity->apply_url" variant="action" size="lg" rel="noopener">{{ __('opportunities.apply_online') }}</x-ui.button>
                @else
                    <x-ui.button :href="'mailto:'.$opportunity->apply_email" variant="action" size="lg">{{ __('opportunities.apply_email', ['email' => $opportunity->apply_email]) }}</x-ui.button>
                @endif
            </x-slot:actions>
        @endif
    </x-page-hero>

    <div class="mx-auto max-w-page px-4 pt-10 pb-16 sm:px-6">
        <div class="grid gap-12 lg:grid-cols-[1fr_22rem]">
            <div class="min-w-0 space-y-12">
                @if ($state === Opportunity::STATE_CANCELLED)
                    <x-ui.alert tone="danger" :title="__('opportunities.cancelled_title')">
                        <p lang="{{ $cancellation?->shownLocale() ?? $locale }}">{{ $cancellation?->body() ?: __('opportunities.cancelled_text') }}</p>
                    </x-ui.alert>
                @elseif ($state === Opportunity::STATE_CLOSED)
                    <x-ui.alert tone="info" :title="__('opportunities.closed_title')">
                        <p>{{ __('opportunities.closed_text') }}</p>
                    </x-ui.alert>
                @endif
                @if ($opportunity->wasExtended() && ! $opportunity->isCancelled())
                    <x-ui.alert tone="warning" :title="__('opportunities.extended_title')">
                        <p>{{ __('opportunities.extended_text') }} <x-date :date="$opportunity->closes_at" :time="true" />. {{ __('opportunities.extended_first') }} <x-date :date="$opportunity->original_closes_at" :time="true" />.</p>
                    </x-ui.alert>
                @endif

                @if (filled($translation->outcome))
                    <section aria-labelledby="opportunity-outcome" class="rounded-card border border-cyan-600/40 bg-cyan-50 p-6 dark:bg-night-900" lang="{{ $translation->locale }}">
                        <h2 id="opportunity-outcome" class="heading-3">{{ __('opportunities.field.outcome') }}</h2>
                        <p class="mt-3 leading-relaxed whitespace-pre-line">{{ $translation->outcome }}</p>
                    </section>
                @endif

                @if (trim($body) !== '')
                    <section aria-labelledby="opportunity-details" lang="{{ $translation->locale }}">
                        <h2 id="opportunity-details" class="heading-2">{{ __('opportunities.field.body') }}</h2>
                        <div class="prose-content mt-5 max-w-prose">{!! $body !!}</div>
                    </section>
                @endif

                @if ($translation->eligibilityList() !== [])
                    <section aria-labelledby="opportunity-eligibility" lang="{{ $translation->locale }}">
                        <h2 id="opportunity-eligibility" class="heading-2">{{ __('opportunities.field.eligibility') }}</h2>
                        <ul class="mt-5 space-y-2">
                            @foreach ($translation->eligibilityList() as $line)
                                <li class="flex items-start gap-3 rounded-control border border-line bg-surface px-4 py-3">
                                    <svg aria-hidden="true" class="mt-0.5 size-5 shrink-0 text-cyan-600 dark:text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                    <span>{{ $line }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if (filled($translation->how_to_apply))
                    <section aria-labelledby="opportunity-apply" lang="{{ $translation->locale }}">
                        <h2 id="opportunity-apply" class="heading-2">{{ __('opportunities.field.how_to_apply') }}</h2>
                        <p class="mt-4 max-w-prose text-lg leading-relaxed whitespace-pre-line">{{ $translation->how_to_apply }}</p>
                    </section>
                @endif

                @if ($documents->isNotEmpty())
                    <section aria-labelledby="opportunity-documents">
                        <h2 id="opportunity-documents" class="heading-2">{{ __('opportunities.field.documents') }}</h2>
                        <ul class="mt-5 grid gap-4 md:grid-cols-2">
                            @foreach ($documents as $item)
                                <li><x-document-row :document="$item['document']" :translation="$item['translation']" :heading-level="3" /></li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                <section aria-labelledby="opportunity-log-title" id="opportunity-log" class="scroll-mt-24">
                    <h2 id="opportunity-log-title" class="heading-2">{{ __('opportunities.addendum.log') }}</h2>
                    <p class="mt-2 max-w-prose text-ink-muted">{{ __('opportunities.addendum.log_help') }}</p>
                    @if ($addenda->isEmpty())
                        <x-ui.empty-state class="mt-6">{{ __('opportunities.addendum.none') }}</x-ui.empty-state>
                    @else
                        <ol class="relative mt-6 space-y-6 before:absolute before:inset-y-2 before:start-4 before:w-px before:bg-line">
                            @foreach ($addenda as $addendum)
                                @php $shownLocale = $addendum->shownLocale(); @endphp
                                <li id="addendum-{{ $addendum->number }}" class="relative flex gap-4 scroll-mt-24">
                                    <span aria-hidden="true" class="relative grid size-8 shrink-0 place-items-center rounded-control bg-night-900 font-mono text-xs text-cyan-300 ring-4 ring-canvas">{{ $addendum->number }}</span>
                                    <article class="min-w-0 flex-1 rounded-card border border-line bg-surface p-5" lang="{{ $shownLocale }}">
                                        <p class="flex flex-wrap items-center gap-2 text-xs">
                                            <x-ui.badge :tone="$addendum->kind->tone()">{{ $addendum->kind->label() }} {{ $addendum->number }}</x-ui.badge>
                                            <span class="font-mono text-ink-muted"><x-date :date="$addendum->published_at" :time="true" /></span>
                                            @if ($addendum->corrected_at)
                                                <span class="font-mono text-ink-muted">· {{ __('opportunities.addendum.corrected_on') }} <x-date :date="$addendum->corrected_at" /></span>
                                            @endif
                                        </p>
                                        <h3 class="mt-2 font-semibold leading-snug">
                                            @if ($addendum->kind === AddendumKind::Clarification)<span class="sr-only">{{ __('opportunities.addendum.field.question') }}: </span>@endif{{ $addendum->title() }}
                                        </h3>
                                        @if ($addendum->body() !== '')
                                            <p class="mt-2 leading-relaxed whitespace-pre-line text-ink-muted">@if ($addendum->kind === AddendumKind::Clarification)<span class="font-semibold text-ink">{{ __('opportunities.addendum.field.answer') }}:</span> @endif{{ $addendum->body() }}</p>
                                        @endif
                                        @if ($addendum->kind === AddendumKind::Extension && $addendum->new_closes_at)
                                            <p class="mt-2 text-sm">{{ __('opportunities.addendum.new_deadline') }} <x-date :date="$addendum->new_closes_at" :time="true" /> · <span class="text-ink-muted">{{ __('opportunities.addendum.was') }} <x-date :date="$addendum->previous_closes_at" :time="true" /></span></p>
                                        @endif
                                        @if ($addendum->document && ($docShown = PublicDirectory::shown($addendum->document, $locale)) && $addendum->document->access === \App\Domain\Documents\Models\Document::ACCESS_PUBLIC)
                                            <p class="mt-3 text-sm"><a href="{{ route('documents.show', ['locale' => $docShown->locale, 'slug' => $docShown->slug]) }}" class="font-semibold text-link underline-offset-4 hover:underline">{{ $docShown->getAttribute('title') }}</a></p>
                                        @endif
                                    </article>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </section>
            </div>

            <aside class="space-y-4 max-lg:order-first lg:sticky lg:top-24 lg:self-start" aria-labelledby="opportunity-facts">
                <section class="relative isolate overflow-hidden rounded-card border border-night-line bg-night-900 p-6 text-night-ink">
                    <x-hex-field id="opportunity-hex" fade="top" class="absolute inset-x-0 top-0 -z-10 h-32 w-full text-cyan-500/20" />
                    <h2 id="opportunity-facts" class="kicker [--color-eyebrow:var(--color-cyan-300)]">{{ __('opportunities.facts') }}</h2>
                    @if ($open)
                        <x-countdown class="mt-4" :until="$opportunity->closes_at" :label="__('opportunities.closes_in')" tone="night" />
                        @vite('resources/js/countdown.js')
                    @elseif ($state === Opportunity::STATE_UPCOMING && $opportunity->opens_at)
                        <x-countdown class="mt-4" :until="$opportunity->opens_at" :label="__('opportunities.opens_in')" tone="night" />
                        @vite('resources/js/countdown.js')
                    @endif
                    <dl class="mt-5 space-y-3 text-sm">
                        <div><dt class="{{ $dt }}">{{ __('opportunities.field.type') }}</dt><dd class="mt-0.5">{{ $opportunity->type->label() }}</dd></div>
                        @if ($office)
                            <div><dt class="{{ $dt }}">{{ __('opportunities.field.office') }}</dt><dd class="mt-0.5">{{ $office->getAttribute('name') }}</dd></div>
                        @endif
                        @if ($pillar)
                            <div><dt class="{{ $dt }}">{{ __('admin.leaders.field.pillar') }}</dt><dd class="mt-0.5">{{ $pillar->getAttribute('name') }}</dd></div>
                        @endif
                        @if ($opportunity->opens_at)
                            <div><dt class="{{ $dt }}">{{ __('opportunities.field.opens_at') }}</dt><dd class="mt-0.5"><x-date :date="$opportunity->opens_at" :time="true" /></dd></div>
                        @endif
                        <div><dt class="{{ $dt }}">{{ __('opportunities.field.closes_at') }}</dt><dd class="mt-0.5"><x-date :date="$opportunity->closes_at" :time="true" /></dd></div>
                        @if ($opportunity->bid_opening_at)
                            <div><dt class="{{ $dt }}">{{ __('opportunities.field.bid_opening_at') }}</dt><dd class="mt-0.5"><x-date :date="$opportunity->bid_opening_at" :time="true" /></dd></div>
                        @endif
                        @if ($opportunity->positions)
                            <div><dt class="{{ $dt }}">{{ __('opportunities.field.positions') }}</dt><dd class="mt-0.5">{{ \Illuminate\Support\Number::format($opportunity->positions, locale: 'en') }}</dd></div>
                        @endif
                        @if ($opportunity->employment_type)
                            <div><dt class="{{ $dt }}">{{ __('opportunities.field.employment_type') }}</dt><dd class="mt-0.5">{{ $opportunity->employment_type->label() }}</dd></div>
                        @endif
                        @if ($opportunity->apply_email)
                            <div><dt class="{{ $dt }}">{{ __('opportunities.field.apply_email') }}</dt><dd class="mt-0.5 break-all"><a href="mailto:{{ $opportunity->apply_email }}" class="text-cyan-300 underline-offset-4 hover:underline" dir="ltr">{{ $opportunity->apply_email }}</a></dd></div>
                        @endif
                    </dl>
                    @if (! $opportunity->isCancelled())
                        <p class="mt-5 border-t border-night-line pt-4 text-sm">
                            <a href="{{ route('opportunities.ics', ['slug' => $translation->slug]) }}" class="inline-flex items-center gap-2 font-semibold text-cyan-300 underline-offset-4 hover:underline">
                                <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                                {{ __('opportunities.add_to_calendar') }}
                            </a>
                        </p>
                    @endif
                </section>
                <section aria-labelledby="opportunity-follow" class="rounded-card border border-line bg-surface p-5 text-sm">
                    <h2 id="opportunity-follow" class="font-semibold">{{ __('opportunities.follow_title') }}</h2>
                    <p class="mt-1 text-ink-muted">{{ __('opportunities.follow_text') }}</p>
                    <p class="mt-3 flex flex-wrap gap-x-4 gap-y-1">
                        <a href="{{ route('opportunities.feed') }}" class="font-semibold text-link underline-offset-4 hover:underline">{{ __('opportunities.feed') }}</a>
                        <a href="{{ route('newsletter.create') }}" class="font-semibold text-link underline-offset-4 hover:underline">{{ __('opportunities.subscribe') }}</a>
                    </p>
                </section>
            </aside>
        </div>
    </div>
</x-layouts.public>
