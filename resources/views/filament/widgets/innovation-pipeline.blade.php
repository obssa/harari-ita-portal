@use('App\Domain\Innovation\Enums\ApplicationStage')
<x-filament-widgets::widget>
    <x-filament::section :heading="$this::title()" icon="heroicon-o-light-bulb">
        {{-- ADR-0037: applications by stage per cohort, and judging progress per challenge; counts only. --}}
        @php $cohorts = $this->cohorts(); $challenges = $this->challenges(); @endphp
        @if ($cohorts === [] && $challenges === [])
            <p class="hinta-empty">{{ __('dashboard.pipeline.empty') }}</p>
        @endif
        @foreach ($cohorts as $row)
            <h3 class="hinta-subhead">{{ $row['cohort']->programme->title() }} · {{ $row['cohort']->name }}</h3>
            <dl class="hinta-list">
                @forelse ($row['stages'] as $stage => $count)
                    @php $enum = ApplicationStage::from($stage); @endphp
                    <div class="hinta-row">
                        <dt><x-filament::badge :color="$enum->color()">{{ $enum->label() }}</x-filament::badge></dt>
                        <dd class="hinta-num hinta-strong">{{ $count }}</dd>
                    </div>
                @empty
                    <div class="hinta-row"><dt class="hinta-muted">{{ __('dashboard.pipeline.no_applications') }}</dt><dd></dd></div>
                @endforelse
            </dl>
        @endforeach
        @if ($cohorts !== [])
            <p style="margin-top: .75rem"><a class="hinta-link" href="{{ \App\Filament\Resources\Applications\ApplicationResource::getUrl('board') }}">{{ __('innovation.admin.board') }}</a></p>
        @endif
        @foreach ($challenges as $row)
            <h3 class="hinta-subhead">{{ $row['challenge']->title() }} · {{ __('innovation.phase.'.$row['phase']) }}</h3>
            <dl class="hinta-list">
                <div class="hinta-row"><dt>{{ __('dashboard.pipeline.submitted') }}</dt><dd class="hinta-num hinta-strong">{{ $row['submitted'] }} / {{ $row['teams'] }}</dd></div>
                <div class="hinta-row"><dt>{{ __('dashboard.pipeline.scored') }}</dt><dd class="hinta-num hinta-strong">{{ $row['scored'] }} / {{ $row['assigned'] }}</dd></div>
            </dl>
        @endforeach
    </x-filament::section>
</x-filament-widgets::widget>
