@use('App\Domain\Organisation\Queries\PublicDirectory')
@php
    $locale = app()->getLocale();
    $select = 'mt-1 block w-full rounded-control border border-line bg-surface px-3 py-2 focus:border-cyan-600 focus:outline-none focus:ring-2 focus:ring-cyan-500/30';
    $filtered = array_filter($filters) !== [] || $search !== '';
@endphp
<x-layouts.public :title="__('opendata.title')" :description="__('opendata.description')">
    {{-- ADR-0025: the catalogue opens on night with the search; filters below as one plain GET form. --}}
    <x-page-hero :title="__('opendata.title')" :lead="__('opendata.description')" :kicker="__('opendata.kicker')" tone="night">
        <form method="get" action="{{ route('datasets.index') }}" role="search" class="flex max-w-3xl flex-wrap gap-2">
            <label for="data-search" class="sr-only">{{ __('opendata.search') }}</label>
            <input id="data-search" type="search" name="q" value="{{ $search }}" maxlength="80" placeholder="{{ __('opendata.search') }}"
                   class="min-w-0 flex-1 rounded-control border border-night-line bg-night-950 px-5 py-3 font-display text-base text-night-ink placeholder:text-night-muted focus:border-cyan-400 focus:ring-2 focus:ring-cyan-400/40 focus:outline-none">
            @foreach ($filters as $name => $value)
                @if ($value)
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endif
            @endforeach
            <x-ui.button type="submit" variant="action" size="lg">{{ __('services.search_button') }}</x-ui.button>
        </form>
    </x-page-hero>

    <div class="mx-auto max-w-page px-4 pt-2 pb-16 sm:px-6">
        <form method="get" action="{{ route('datasets.index') }}" class="mt-8 grid gap-4 rounded-card border border-line bg-surface p-4 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_1fr_auto] lg:items-end">
            @if ($search !== '')
                <input type="hidden" name="q" value="{{ $search }}">
            @endif
            <div>
                <label for="filter-pillar" class="kicker">{{ __('projects.pillar') }}</label>
                <select id="filter-pillar" name="pillar" class="{{ $select }}">
                    <option value="">{{ __('projects.all_pillars') }}</option>
                    @foreach ($pillars as $id => $name)
                        <option value="{{ $id }}" @selected($filters['pillar'] === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter-publisher" class="kicker">{{ __('opendata.field.publisher') }}</label>
                <select id="filter-publisher" name="publisher" class="{{ $select }}">
                    <option value="">{{ __('opendata.any_publisher') }}</option>
                    @foreach ($publishers as $id => $name)
                        <option value="{{ $id }}" @selected($filters['publisher'] === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter-format" class="kicker">{{ __('opendata.field.format') }}</label>
                <select id="filter-format" name="format" class="{{ $select }}">
                    <option value="">{{ __('services.filter.any') }}</option>
                    @foreach ($formats as $value => $label)
                        <option value="{{ $value }}" @selected($filters['format'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter-licence" class="kicker">{{ __('opendata.field.licence') }}</label>
                <select id="filter-licence" name="licence" class="{{ $select }}">
                    <option value="">{{ __('services.filter.any') }}</option>
                    @foreach ($licences as $value => $label)
                        <option value="{{ $value }}" @selected($filters['licence'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button type="submit">{{ __('services.filter.apply') }}</x-ui.button>
                @if ($filtered)
                    <a href="{{ route('datasets.index') }}" class="text-sm text-link underline-offset-4 hover:underline">{{ __('services.filter.clear') }}</a>
                @endif
            </div>
        </form>

        @if ($datasets->isEmpty())
            <x-ui.empty-state class="mt-10">{{ $filtered ? __('opendata.none_match') : __('opendata.empty') }}</x-ui.empty-state>
        @else
            <p class="mt-8 font-mono text-xs tracking-wide text-ink-muted uppercase" role="status">{{ trans_choice('opendata.count', $datasets->total(), ['count' => $datasets->total()]) }}</p>
            <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($datasets as $dataset)
                    @php $shown = PublicDirectory::shown($dataset, $locale); @endphp
                    @continue($shown === null)
                    <li><x-dataset-card :dataset="$dataset" :translation="$shown" /></li>
                @endforeach
            </ul>
            <div class="mt-10">{{ $datasets->links() }}</div>
        @endif

        <section aria-labelledby="data-reuse" class="relative isolate mt-16 overflow-hidden rounded-card border border-night-line bg-night-900 p-8 text-night-ink sm:p-10">
            <x-circuit class="absolute inset-y-0 end-0 -z-10 h-full w-1/2 text-cyan-500/25" />
            <h2 id="data-reuse" class="font-display text-2xl font-semibold tracking-tight">{{ __('opendata.reuse_title') }}</h2>
            <p class="mt-2 max-w-prose text-night-muted">{{ __('opendata.reuse_text') }}</p>
            <p class="mt-6 flex flex-wrap gap-3">
                <x-ui.button :href="route('datasets.catalog')" variant="light">{{ __('opendata.catalog_link') }}</x-ui.button>
            </p>
        </section>
    </div>
</x-layouts.public>
