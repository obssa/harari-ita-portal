@use('App\Domain\Innovation\Enums\ApplicationStage')
@php
    $steps = [__('innovation.application_stage.received'), __('innovation.application_stage.screening'), __('innovation.application_stage.interview'), __('innovation.public.stage.decision')];
    $current = match ($application->stage) {
        ApplicationStage::Received => 1,
        ApplicationStage::Screening => 1,
        ApplicationStage::Interview => 2,
        default => 4,
    };
    $tone = match ($application->stage) {
        ApplicationStage::Accepted => 'success',
        ApplicationStage::Declined, ApplicationStage::Withdrawn => 'danger',
        default => 'warning',
    };
@endphp
<x-layouts.public :title="$application->reference" :description="__('innovation.track.application_description')">
    {{-- ADR-0021: the applicant's view: where the application stands and, once decided, the note from the Agency. --}}
    <x-page-hero :title="$application->reference" :kicker="__('innovation.track.application_title')" tone="night" :crumbs="[[__('innovation.programmes.title'), route('programmes.index')]]">
        <x-ui.stepper :steps="$steps" :current="$current" tone="night" class="max-w-3xl" />
    </x-page-hero>
    <div class="mx-auto max-w-page px-4 pt-10 pb-16 sm:px-6">
        @if (session('status'))
            <x-ui.alert tone="success" class="mb-8 max-w-3xl">{{ session('status') }}</x-ui.alert>
        @endif
        <article class="max-w-3xl rounded-card border border-line bg-surface p-6">
            <p class="kicker">{{ $application->cohort->name }}</p>
            <h2 class="mt-2 font-display text-2xl font-semibold tracking-tight">{{ $application->programme->title() }}</h2>
            <p class="mt-3"><x-ui.badge :tone="$tone">{{ $application->stage->label() }}</x-ui.badge></p>
            @if ($application->stage->isDecision() && filled($application->decision_note))
                <x-ui.alert class="mt-5" :title="__('innovation.public.note_from_agency')">{{ $application->decision_note }}</x-ui.alert>
            @endif
            <p class="mt-5 font-mono text-xs text-ink-muted">{{ __('innovation.field.submitted_at') }}: <x-date :date="$application->created_at" /></p>
            @if ($application->stage->isOpen())
                <form method="post" action="{{ route('applications.withdraw', ['reference' => $application->reference]) }}" class="mt-6 border-t border-line pt-5">
                    @csrf
                    <p class="text-sm text-ink-muted">{{ __('innovation.public.withdraw_help') }}</p>
                    <p class="mt-3"><x-ui.button type="submit" variant="secondary" size="sm">{{ __('innovation.public.withdraw') }}</x-ui.button></p>
                </form>
            @endif
        </article>
    </div>
</x-layouts.public>
