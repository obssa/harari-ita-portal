<x-filament-panels::page>
    {{-- ADR-0037: one cohort's applications, a column per stage; each card shows the reference, the team and the score. --}}
    @php $selected = $this->selected(); @endphp
    <div class="hinta-board-filter">
        <label for="board-cohort" class="hinta-subhead" style="margin: 0">{{ __('innovation.admin.cohort') }}</label>
        <x-filament::input.wrapper>
            <x-filament::input.select id="board-cohort" wire:model.live="cohort">
                @foreach ($this->cohorts() as $option)
                    <option value="{{ $option->id }}">{{ $option->programme->title() }} · {{ $option->name }}</option>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>
    </div>
    @if ($selected === null)
        <p class="hinta-empty">{{ __('innovation.admin.no_cohorts') }}</p>
    @else
        <div class="hinta-board">
            @foreach ($this->columns() as $stage => $applications)
                @php $state = \App\Domain\Innovation\Enums\ApplicationStage::from($stage); @endphp
                <section class="hinta-board-col" aria-labelledby="board-{{ $stage }}">
                    <h2 id="board-{{ $stage }}" class="hinta-board-head">
                        <span>{{ $state->label() }}</span>
                        <span class="hinta-board-count">{{ $applications->count() }}</span>
                    </h2>
                    <ul class="hinta-board-list">
                        @forelse ($applications as $application)
                            <li>
                                <a href="{{ \App\Filament\Resources\Applications\ApplicationResource::getUrl('view', ['record' => $application]) }}" class="hinta-card">
                                    <span class="hinta-card-ref">{{ $application->reference }}</span>
                                    <span class="hinta-card-title">{{ \Illuminate\Support\Str::limit($application->team_name ?? $application->applicant_name ?? '', 90) }}</span>
                                    <span class="hinta-muted">{{ $application->score !== null ? __('innovation.admin.score_value', ['score' => $application->score]) : __('innovation.admin.not_scored') }}</span>
                                </a>
                            </li>
                        @empty
                            <li class="hinta-empty">{{ __('support.admin.column_empty') }}</li>
                        @endforelse
                    </ul>
                </section>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
