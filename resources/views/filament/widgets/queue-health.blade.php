<x-filament-widgets::widget>
    <x-filament::section :heading="$this::title()" icon="heroicon-o-cpu-chip">
        @php $health = $this->health(); @endphp
        <p class="hinta-chips">
            <x-filament::badge :color="$health['healthy'] ? 'success' : 'danger'">{{ $health['healthy'] ? __('dashboard.queues.healthy') : __('dashboard.queues.unhealthy') }}</x-filament::badge>
            {{ $health['heartbeat'] ? __('dashboard.queues.heartbeat', ['ago' => $health['heartbeat']->diffForHumans()]) : __('dashboard.queues.no_heartbeat') }}
        </p>
        <dl class="hinta-list" style="margin-top: .75rem">
            @forelse ($health['queues'] as $queue)
                <div class="hinta-row">
                    <dt>{{ $queue['queue'] }}</dt>
                    <dd class="hinta-num">
                        {{ trans_choice('dashboard.queues.waiting', $queue['waiting'], ['count' => $queue['waiting']]) }}
                        @if ($queue['oldest'])
                            · {{ __('dashboard.queues.oldest', ['ago' => $queue['oldest']->diffForHumans()]) }}
                        @endif
                    </dd>
                </div>
            @empty
                <div class="hinta-muted" style="padding: .5rem 0">{{ __('dashboard.queues.empty') }}</div>
            @endforelse
            <div class="hinta-row">
                <dt>{{ __('dashboard.queues.failed') }}</dt>
                <dd class="hinta-num">{{ $health['failed'] }}</dd>
            </div>
        </dl>
    </x-filament::section>
</x-filament-widgets::widget>
