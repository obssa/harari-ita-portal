<x-filament-widgets::widget>
    <x-filament::section :heading="$this::title()" icon="heroicon-o-chart-bar-square" :description="__('dashboard.glance.help')">
        {{-- ADR-0037: the live figures, counted from the records; each opens its list where the person may see it. --}}
        <dl class="hinta-figures">
            @foreach ($this->figures() as $figure)
                <div class="hinta-figure">
                    <dt class="hinta-muted">{{ $figure['label'] }}</dt>
                    <dd class="hinta-big hinta-num">
                        @if ($figure['url'])
                            <a class="hinta-link" href="{{ $figure['url'] }}">{{ \Illuminate\Support\Number::format($figure['value'], locale: 'en') }}</a>
                        @else
                            {{ \Illuminate\Support\Number::format($figure['value'], locale: 'en') }}
                        @endif
                    </dd>
                </div>
            @endforeach
        </dl>
    </x-filament::section>
</x-filament-widgets::widget>
