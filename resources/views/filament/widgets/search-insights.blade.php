<x-filament-widgets::widget>
    <x-filament::section :heading="$this::title()" icon="heroicon-o-magnifying-glass">
        @php $insights = $this->insights(); @endphp
        <div style="display: grid; gap: 1.5rem; grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr))">
            @foreach (['top' => __('dashboard.search.top'), 'empty' => __('dashboard.search.empty')] as $list => $heading)
                <div>
                    <h3 class="hinta-subhead">{{ $heading }}</h3>
                    <ol class="hinta-list" style="margin-top: .5rem">
                        @forelse ($insights[$list] as $term)
                            <li style="display: flex; justify-content: space-between; gap: .75rem; padding: .125rem 0" lang="{{ $term->locale }}">
                                <span>{{ $term->term }}</span>
                                <span class="hinta-num hinta-muted">{{ $term->searches }}</span>
                            </li>
                        @empty
                            <li class="hinta-muted">{{ __('dashboard.search.none') }}</li>
                        @endforelse
                    </ol>
                </div>
            @endforeach
        </div>
        <p class="hinta-list" style="margin-top: 1rem">
            <a href="{{ \App\Filament\Resources\SearchTerms\SearchTermResource::getUrl() }}" class="hinta-link">{{ __('dashboard.search.all') }}</a>
        </p>
    </x-filament::section>
</x-filament-widgets::widget>
