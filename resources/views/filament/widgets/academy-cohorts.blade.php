<x-filament-widgets::widget>
    <x-filament::section :heading="$this::title()" icon="heroicon-o-academic-cap">
        {{-- ADR-0037: the next cohorts, places taken of places offered, and the waiting list. --}}
        @php $cohorts = $this->cohorts(); @endphp
        @if ($cohorts->isEmpty())
            <p class="hinta-empty">{{ __('dashboard.academy.empty') }}</p>
        @else
            <ul class="hinta-list">
                @foreach ($cohorts as $cohort)
                    <li class="hinta-row">
                        <span style="min-width: 0">
                            <a class="hinta-link hinta-ellipsis" style="display: block" href="{{ \App\Filament\Resources\CourseCohorts\CourseCohortResource::getUrl(auth()->user()?->can('update', $cohort) ? 'edit' : 'view', ['record' => $cohort]) }}">{{ $cohort->course->title() }} · {{ $cohort->name }}</a>
                            <span class="hinta-muted">{{ \App\Filament\Support\CalendarDate::format()($cohort->starts_on) }}</span>
                        </span>
                        <span class="hinta-num hinta-strong" style="white-space: nowrap">
                            {{ $cohort->capacity !== null ? __('dashboard.academy.places', ['taken' => $cohort->enrolled_total, 'total' => $cohort->capacity]) : __('dashboard.academy.enrolled', ['count' => $cohort->enrolled_total]) }}
                            @if ($cohort->waiting_total > 0)
                                <span class="hinta-muted">· {{ __('dashboard.academy.waiting', ['count' => $cohort->waiting_total]) }}</span>
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
