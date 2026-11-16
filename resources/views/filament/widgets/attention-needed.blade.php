<x-filament-widgets::widget>
    <x-filament::section :heading="$this::title()" icon="heroicon-o-bell-alert">
        @php $items = $this->items(); @endphp
        @if ($items === [])
            <p class="hinta-empty">{{ __('dashboard.attention.none') }}</p>
        @else
            <ul class="hinta-list">
                @foreach ($items as $item)
                    <li class="hinta-row">
                        @if ($item['url'])
                            <a href="{{ $item['url'] }}" class="hinta-link">{{ $item['label'] }}</a>
                        @else
                            <span>{{ $item['label'] }}</span>
                        @endif
                        <x-filament::badge :color="$item['tone']">{{ \Illuminate\Support\Number::format($item['count']) }}</x-filament::badge>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
