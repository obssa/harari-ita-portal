@use('App\Domain\Innovation\Enums\LabelStatus')
<x-layouts.public :title="$translation->name" :description="$translation->summary" :alternates="$alternates" :json-ld="$jsonLd">
    {{-- ADR-0020: a startup's profile: the pitch, products and story, with the facts and the label claim beside them. --}}
    <x-page-hero :title="$translation->name" :lead="$translation->summary" :kicker="$sector ?? __('innovation.startups.kicker')" tone="night"
                 :crumbs="[[__('innovation.startups.title'), route('startups.index')]]" :heading-lang="$translation->locale">
        @if ($startup->website)
            <x-slot:actions>
                <x-ui.button :href="$startup->website" variant="light" target="_blank" rel="noopener">{{ __('innovation.field.website') }}</x-ui.button>
            </x-slot:actions>
        @endif
    </x-page-hero>

    <div class="mx-auto max-w-page px-4 pt-10 pb-16 sm:px-6">
        <div class="grid gap-12 lg:grid-cols-[1fr_20rem]">
            <div class="min-w-0 space-y-10" lang="{{ $translation->locale }}">
                @if ($translation->productList() !== [])
                    <section aria-labelledby="startup-products">
                        <h2 id="startup-products" class="heading-2">{{ __('innovation.field.products') }}</h2>
                        <ul class="mt-5 grid gap-2 sm:grid-cols-2">
                            @foreach ($translation->productList() as $product)
                                <li class="rounded-control border border-line bg-surface px-4 py-3">{{ $product }}</li>
                            @endforeach
                        </ul>
                    </section>
                @endif
                @if (trim($body) !== '')
                    <section aria-labelledby="startup-story">
                        <h2 id="startup-story" class="heading-2">{{ __('innovation.field.body') }}</h2>
                        <div class="prose-content mt-5 max-w-prose">{!! $body !!}</div>
                    </section>
                @endif
            </div>

            <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start" aria-label="{{ __('services.at_a_glance') }}">
                <section class="relative isolate overflow-hidden rounded-card border border-night-line bg-night-900 p-6 text-night-ink">
                    <x-hex-field id="startup-hex" fade="top" class="absolute inset-x-0 top-0 -z-10 h-32 w-full text-cyan-500/20" />
                    @if ($startup->logo)
                        <x-picture :media="$startup->logo" preset="square" sizes="5rem" class="mb-4 size-20 rounded-control bg-white object-contain p-1" />
                    @endif
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="font-mono text-[11px] tracking-wider text-night-muted uppercase">{{ __('innovation.field.stage') }}</dt>
                            <dd class="mt-1 text-base">{{ $startup->stage->label() }}</dd>
                        </div>
                        @if ($startup->founded_year)
                            <div>
                                <dt class="font-mono text-[11px] tracking-wider text-night-muted uppercase">{{ __('innovation.field.founded_year') }}</dt>
                                <dd class="mt-1 text-base" dir="ltr">{{ $startup->founded_year }}</dd>
                            </div>
                        @endif
                        @if ($startup->team_size)
                            <div>
                                <dt class="font-mono text-[11px] tracking-wider text-night-muted uppercase">{{ __('innovation.field.team_size') }}</dt>
                                <dd class="mt-1 text-base">{{ $startup->team_size }}</dd>
                            </div>
                        @endif
                        @if ($startup->area)
                            <div>
                                <dt class="font-mono text-[11px] tracking-wider text-night-muted uppercase">{{ __('innovation.field.area') }}</dt>
                                <dd class="mt-1 text-base">{{ $startup->area }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="font-mono text-[11px] tracking-wider text-night-muted uppercase">{{ __('innovation.field.label') }}</dt>
                            <dd class="mt-1 text-base">
                                {{ $startup->label_status->label() }}
                                @if ($startup->label_status === LabelStatus::Verified && $startup->label_verified_at)
                                    <span class="block font-mono text-xs text-night-muted"><x-date :date="$startup->label_verified_at" /></span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </section>
                <p class="text-sm text-ink-muted">{{ __('innovation.startups.label_note') }}</p>
            </aside>
        </div>
    </div>
</x-layouts.public>
