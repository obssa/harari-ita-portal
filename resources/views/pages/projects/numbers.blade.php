@use('App\Domain\Organisation\Queries\PublicDirectory')
@php
    $locale = app()->getLocale();
    $number = fn (int $v): string => \Illuminate\Support\Number::format($v, locale: 'en');
@endphp
<x-layouts.public :title="__('numbers.title')" :description="__('numbers.description')">
    {{-- ADR-0027: counted figures first (from the portal's own records), then the sourced statistics, then the projects. --}}
    <x-page-hero :title="__('numbers.title')" :lead="__('numbers.description')" :kicker="__('numbers.kicker')" tone="night">
        <x-slot:actions>
            <x-ui.button :href="route('datasets.index')" variant="light" size="sm">{{ __('opendata.title') }}</x-ui.button>
            <x-ui.button :href="route('projects.index')" variant="light" size="sm">{{ __('projects.all_projects') }}</x-ui.button>
        </x-slot:actions>
    </x-page-hero>

    <div class="mx-auto max-w-page px-4 pt-10 pb-16 sm:px-6">
        @if ($live !== [])
            <section aria-labelledby="numbers-live">
                <p class="kicker">{{ __('numbers.live_kicker') }}</p>
                <h2 id="numbers-live" class="heading-2 mt-2">{{ __('numbers.live_title') }}</h2>
                <p class="mt-2 max-w-prose text-ink-muted">{{ __('numbers.live_lead') }}</p>
                <ul class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($live as $figure)
                        <li>
                            <x-ui.kpi :label="$figure['label']" :value="$number($figure['value'])" :source="$figure['source']" class="h-full">
                                @if ($figure['url'])
                                    <a href="{{ $figure['url'] }}" class="mt-3 inline-block text-sm text-link underline-offset-4 hover:underline">{{ __('numbers.see_records') }}<span class="sr-only">: {{ $figure['label'] }}</span></a>
                                @endif
                            </x-ui.kpi>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($total > 0)
            <section aria-labelledby="numbers-projects" class="mt-14 grid gap-6 lg:grid-cols-2">
                <h2 id="numbers-projects" class="sr-only">{{ __('projects.by_status') }}</h2>
                <x-charts.bars id="chart-status" :title="trans_choice('projects.chart_title', $total, ['count' => $total])" :items="$byStatus" :note="__('numbers.source.projects')" />
                @if ($byPillar !== [])
                    <x-charts.bars id="chart-pillar" :title="__('numbers.by_pillar')" :items="$byPillar" :note="__('numbers.source.projects')" />
                @endif
            </section>
        @endif

        @if ($regional->isNotEmpty())
            <section aria-labelledby="numbers-regional" class="mt-14">
                <h2 id="numbers-regional" class="heading-2">{{ __('projects.regional') }}</h2>
                <p class="mt-2 max-w-prose text-ink-muted">{{ __('numbers.statistics_lead') }}</p>
                <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($regional as $statistic)
                        <x-statistic-tile :statistic="$statistic" />
                    @endforeach
                </div>
            </section>
        @endif

        @foreach ($sectors as $index => $item)
            <section aria-labelledby="sector-{{ $index }}" class="mt-14 border-t border-line pt-10">
                <h2 id="sector-{{ $index }}" class="heading-2">{{ $item['name'] }}</h2>
                <div class="mt-6 grid gap-8 lg:grid-cols-3">
                    <div class="lg:col-span-2">
                        @if ($item['statistics']->isNotEmpty())
                            <div class="grid gap-4 sm:grid-cols-2">
                                @foreach ($item['statistics'] as $statistic)
                                    <x-statistic-tile :statistic="$statistic" />
                                @endforeach
                            </div>
                        @endif
                        @if ($item['completed']->isNotEmpty())
                            <h3 class="heading-3 mt-8">{{ __('projects.recently_completed') }}</h3>
                            <ul class="mt-4 grid gap-4 sm:grid-cols-3">
                                @foreach ($item['completed'] as $project)
                                    @php $shown = PublicDirectory::shown($project, $locale); @endphp
                                    @continue($shown === null)
                                    <li class="flex"><x-project-card :project="$project" :translation="$shown" :heading-level="4" class="w-full" /></li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    @if ($item['total'] > 0)
                        <div>
                            <x-charts.bars :id="'chart-sector-'.$index" :title="trans_choice('projects.chart_title', $item['total'], ['count' => $item['total']])" :items="$item['chart']" />
                            <a href="{{ route('projects.index', ['sector' => $item['sector']->id]) }}" class="mt-3 inline-block text-sm text-link underline-offset-4 hover:underline">{{ __('projects.sector_projects') }}</a>
                        </div>
                    @endif
                </div>
            </section>
        @endforeach

        @if ($live === [] && $total === 0 && $regional->isEmpty() && $sectors->isEmpty())
            <x-ui.empty-state class="mt-10">{{ __('directory.empty') }}</x-ui.empty-state>
        @endif
    </div>
</x-layouts.public>
