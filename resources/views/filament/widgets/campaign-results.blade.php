<x-filament-widgets::widget>
    <x-filament::section :heading="$this::title()" icon="heroicon-o-megaphone">
        @php $rows = $this->rows(); @endphp
        @if ($rows === [])
            <p class="hinta-empty">{{ __('campaigns.dashboard.none') }}</p>
        @else
            <table class="hinta-table">
                <thead>
                    <tr>
                        <th scope="col">{{ __('campaigns.field.name') }}</th>
                        <th scope="col">{{ __('campaigns.report.sent') }}</th>
                        <th scope="col">{{ __('campaigns.report.clicked') }}</th>
                        <th scope="col">{{ __('campaigns.report.unsubscribed') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td style="padding: .25rem 0">
                                <a href="{{ \App\Filament\Resources\Campaigns\CampaignResource::getUrl('view', ['record' => $row['campaign']]) }}" class="hinta-link">{{ $row['campaign']->name }}</a>
                                <span class="hinta-muted">· {{ __('newsletter.channel.'.$row['campaign']->channel) }}</span>
                            </td>
                            <td>{{ $row['sent'] }}</td>
                            <td>{{ $row['campaign']->channel === 'email' ? $row['clicked'].'%' : '—' }}</td>
                            <td>{{ $row['unsubscribed'] }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
