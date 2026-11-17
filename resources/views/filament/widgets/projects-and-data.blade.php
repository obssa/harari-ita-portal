@use('App\Domain\Projects\Enums\RagStatus')
<x-filament-widgets::widget>
    <x-filament::section :heading="$this::title()" icon="heroicon-o-presentation-chart-line">
        {{-- ADR-0037: running projects by RAG status (the word, never colour alone), those at risk, and overdue datasets. --}}
        @php $rag = $this->rag(); @endphp
        <h3 class="hinta-subhead" style="margin-top: 0">{{ __('dashboard.projects.running') }}</h3>
        <dl class="hinta-list">
            @foreach (RagStatus::cases() as $status)
                <div class="hinta-row">
                    <dt><x-filament::badge :color="$status->color()">{{ $status->label() }}</x-filament::badge></dt>
                    <dd class="hinta-num hinta-strong">{{ $rag[$status->value] }}</dd>
                </div>
            @endforeach
            @if ($rag['none'] > 0)
                <div class="hinta-row"><dt class="hinta-muted">{{ __('dashboard.projects.no_rag') }}</dt><dd class="hinta-num hinta-strong">{{ $rag['none'] }}</dd></div>
            @endif
        </dl>
        @php $risk = $this->atRisk(); @endphp
        @if ($risk->isNotEmpty())
            <h3 class="hinta-subhead">{{ __('dashboard.projects.at_risk') }}</h3>
            <ul class="hinta-list">
                @foreach ($risk as $project)
                    <li class="hinta-row">
                        <a class="hinta-link hinta-ellipsis" href="{{ \App\Filament\Resources\Projects\ProjectResource::getUrl('edit', ['record' => $project]) }}">{{ $project->title() }}</a>
                        <x-filament::badge :color="$project->rag_status?->color() ?? 'gray'">{{ $project->rag_status?->label() }}</x-filament::badge>
                    </li>
                @endforeach
            </ul>
        @endif
        @php $overdue = $this->overdue(); @endphp
        @if (auth()->user()?->can('datasets.view'))
            <h3 class="hinta-subhead">{{ __('dashboard.projects.datasets_overdue') }}</h3>
            @if ($overdue->isEmpty())
                <p class="hinta-empty">{{ __('dashboard.projects.datasets_current') }}</p>
            @else
                <ul class="hinta-list">
                    @foreach ($overdue as $dataset)
                        <li class="hinta-row">
                            <a class="hinta-link hinta-ellipsis" href="{{ \App\Filament\Resources\Datasets\DatasetResource::getUrl('edit', ['record' => $dataset]) }}">{{ $dataset->title() }}</a>
                            <span class="hinta-muted hinta-num">{{ __('dashboard.projects.due_since', ['date' => \App\Filament\Support\CalendarDate::format()($dataset->nextUpdateDue())]) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
