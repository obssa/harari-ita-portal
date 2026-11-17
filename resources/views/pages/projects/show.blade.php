@php
    $budget = $project->publicBudget();
    $spent = $project->publicSpent();
    $rag = $project->publicRag();
    $birr = fn (float $amount): string => __('projects.birr', ['amount' => \Illuminate\Support\Number::format($amount, precision: 0, locale: 'en')]);
@endphp
<x-layouts.public :title="$translation->title" :description="$translation->summary" :alternates="$alternates" :image="$image" :json-ld="$jsonLd">
    {{-- ADR-0024: the project opens on night with where it stands; milestones, indicators and money follow. --}}
    <x-page-hero :title="$translation->title" :lead="$translation->summary" :kicker="$project->pillar?->name() ?? __('projects.title')" tone="night"
                 :crumbs="[[__('projects.title'), route('projects.index')]]" :heading-lang="$translation->locale">
        <div class="flex max-w-3xl flex-col gap-5">
            <p class="flex flex-wrap items-center gap-2">
                <x-ui.badge :tone="$project->status->tone()">{{ $project->status->label() }}</x-ui.badge>
                @if ($rag)
                    <x-ui.badge :tone="$rag->tone()">{{ __('projects.rag_label') }}: {{ $rag->label() }}</x-ui.badge>
                @endif
                @if ($project->sector)
                    <span class="font-mono text-xs tracking-wide text-night-muted uppercase">{{ $project->sector->name() }}</span>
                @endif
            </p>
            @if ($project->progress !== null)
                <x-progress-bar :value="$project->progress" tone="night" class="max-w-md" />
            @endif
        </div>
    </x-page-hero>

    <article class="mx-auto max-w-page px-4 pt-10 pb-16 sm:px-6">
        <div class="grid gap-10 lg:grid-cols-3">
            <div class="min-w-0 lg:col-span-2">
                @if ($project->cover)
                    <x-picture :media="$project->cover" preset="landscape" sizes="(min-width: 64rem) 42rem, 100vw" loading="eager" fetchpriority="high" class="mb-8 w-full rounded-card" />
                @endif

                @if (trim($body) !== '')
                    <div class="prose-content max-w-prose">{!! $body !!}</div>
                @endif

                @if ($project->milestones->isNotEmpty())
                    <section aria-labelledby="project-milestones" class="mt-12">
                        <p class="kicker">{{ __('projects.milestones_done', ['done' => $project->milestones->filter(fn ($m) => $m->isDone())->count(), 'total' => $project->milestones->count()]) }}</p>
                        <h2 id="project-milestones" class="heading-2 mt-2">{{ __('projects.milestones') }}</h2>
                        <x-milestone-timeline :milestones="$project->milestones" class="mt-6" />
                    </section>
                @endif

                @if ($project->kpis->isNotEmpty())
                    <section aria-labelledby="project-kpis" class="mt-12">
                        <h2 id="project-kpis" class="heading-2">{{ __('projects.kpis') }}</h2>
                        <p class="mt-2 max-w-prose text-ink-muted">{{ __('projects.kpis_lead') }}</p>
                        <div class="mt-6 grid gap-4 sm:grid-cols-2">
                            @foreach ($project->kpis as $kpi)
                                <x-kpi-card :kpi="$kpi" />
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($project->statistics->isNotEmpty())
                    <section aria-labelledby="project-numbers" class="mt-12">
                        <h2 id="project-numbers" class="heading-2">{{ __('projects.numbers') }}</h2>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            @foreach ($project->statistics as $statistic)
                                <x-statistic-tile :statistic="$statistic" />
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($photos->isNotEmpty() && $galleryTranslation)
                    <section aria-labelledby="project-photos" class="mt-12">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <h2 id="project-photos" class="heading-2">{{ __('projects.photos') }}</h2>
                            <a href="{{ route('gallery.show', ['locale' => $galleryTranslation->locale, 'slug' => $galleryTranslation->slug]) }}" class="text-sm text-link underline-offset-4 hover:underline">{{ __('projects.all_photos') }}</a>
                        </div>
                        <ul class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3">
                            @foreach ($photos as $item)
                                <li><x-picture :media="$item->media" preset="square" sizes="(min-width: 40rem) 14rem, 50vw" class="aspect-square w-full rounded object-cover" /></li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if ($project->hasMap())
                    <section aria-labelledby="project-map" class="mt-12">
                        <h2 id="project-map" class="heading-3">{{ __('offices.map') }}</h2>
                        <x-map-frame :latitude="$project->latitude" :longitude="$project->longitude" :title="__('offices.map_of', ['office' => $translation->title])" />
                    </section>
                @endif
            </div>

            <aside aria-label="{{ __('projects.details') }}" class="space-y-6">
                <dl class="space-y-4 rounded-card border border-line bg-surface p-5 text-sm">
                    @if (filled($translation->location) || $office)
                        <div>
                            <dt class="kicker">{{ __('projects.where') }}</dt>
                            <dd class="mt-1">
                                {{ $translation->location }}
                                @if ($office)
                                    <a href="{{ route('offices.show', ['locale' => $office->locale, 'slug' => $office->slug]) }}" class="block text-link underline-offset-4 hover:underline">{{ $office->getAttribute('name') }}</a>
                                @endif
                            </dd>
                        </div>
                    @endif
                    @if ($project->pillar)
                        <div>
                            <dt class="kicker">{{ __('projects.pillar') }}</dt>
                            <dd class="mt-1">{{ $project->pillar->name() }}</dd>
                        </div>
                    @endif
                    @if ($project->starts_on)
                        <div>
                            <dt class="kicker">{{ __('projects.starts_on') }}</dt>
                            <dd class="mt-1"><x-date :date="$project->starts_on" /></dd>
                        </div>
                    @endif
                    @if ($project->ends_on)
                        <div>
                            <dt class="kicker">{{ $project->status === \App\Domain\Projects\Enums\ProjectStatus::Completed ? __('projects.completed_on') : __('projects.ends_on') }}</dt>
                            <dd class="mt-1"><x-date :date="$project->ends_on" /></dd>
                        </div>
                    @endif
                    @if ($rag && $project->rag_updated_at)
                        <div>
                            <dt class="kicker">{{ __('projects.rag_label') }}</dt>
                            <dd class="mt-1">{{ $rag->label() }} · <span class="text-ink-muted">{{ __('projects.as_of') }} <x-date :date="$project->rag_updated_at" /></span></dd>
                        </div>
                    @endif
                </dl>

                @if ($budget !== null)
                    <section aria-labelledby="project-money" class="rounded-card border border-line bg-surface p-5 text-sm">
                        <h2 id="project-money" class="kicker">{{ __('projects.budget') }}</h2>
                        <p class="mt-2 font-display text-2xl font-bold tabular-nums" dir="ltr">{{ $birr($budget) }}</p>
                        @if ($spent !== null)
                            @php $share = $budget > 0 ? (int) round(min($spent, $budget) / $budget * 100) : 0; @endphp
                            <p class="mt-4">{{ __('projects.spent') }}: <span class="font-semibold tabular-nums" dir="ltr">{{ $birr($spent) }}</span></p>
                            <x-progress-bar :value="$share" :label="__('projects.spent_share_label')" :caption="__('projects.spent_share')" class="mt-2" />
                            @if ($spent > $budget)
                                <p class="mt-2 text-warning-800 dark:text-warning-200">{{ __('projects.over_budget') }}</p>
                            @endif
                        @endif
                    </section>
                @endif
            </aside>
        </div>
    </article>
</x-layouts.public>
