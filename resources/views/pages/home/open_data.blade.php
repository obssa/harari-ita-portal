@use('App\Domain\Organisation\Queries\PublicDirectory')
{{-- ADR-0025: the open-data highlight: the featured or newest datasets, the catalogue and the numbers page. --}}
<section aria-labelledby="home-open-data" class="mx-auto max-w-page px-4 py-12 sm:px-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="kicker">{{ __('opendata.kicker') }}</p>
            <h2 id="home-open-data" class="heading-2 mt-2">{{ $section->heading() }}</h2>
        </div>
        <p class="flex flex-wrap gap-4 text-sm font-semibold">
            <a href="{{ route('datasets.index') }}" class="text-link underline-offset-4 hover:underline">{{ __('opendata.all') }}</a>
            <a href="{{ route('projects.numbers') }}" class="text-link underline-offset-4 hover:underline">{{ __('numbers.title') }}</a>
        </p>
    </div>
    <ul class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($data as $dataset)
            @php $shown = PublicDirectory::shown($dataset, app()->getLocale()); @endphp
            @continue($shown === null)
            <li><x-dataset-card :dataset="$dataset" :translation="$shown" :heading-level="3" /></li>
        @endforeach
    </ul>
</section>
