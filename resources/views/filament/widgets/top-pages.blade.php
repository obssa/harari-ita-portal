<x-filament-widgets::widget>
    <x-filament::section :heading="$this::title()" icon="heroicon-o-document-chart-bar">
        <ol class="hinta-list">
            @forelse ($this->pages() as $page)
                <li class="hinta-row" lang="{{ $page['locale'] }}">
                    <a href="{{ url($page['label']) }}" target="_blank" rel="noopener" class="hinta-ellipsis">{{ $page['label'] }}</a>
                    <span class="hinta-num hinta-muted">{{ \Illuminate\Support\Number::format($page['views']) }}</span>
                </li>
            @empty
                <li class="hinta-muted">{{ __('dashboard.traffic.none') }}</li>
            @endforelse
        </ol>
    </x-filament::section>
</x-filament-widgets::widget>
