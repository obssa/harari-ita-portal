@use('App\Domain\Innovation\Enums\ListingStatus')
@php
    $steps = [__('innovation.public.stage.received'), __('innovation.public.stage.review'), __('innovation.public.stage.decision')];
    $current = match ($startup->listing_status) {
        ListingStatus::Submitted => 1,
        ListingStatus::ChangesRequested => 1,
        default => 3,
    };
@endphp
<x-layouts.public :title="$startup->reference" :description="__('innovation.track.description')">
    {{-- ADR-0020: the founders' view of their listing: where the review stands and the reviewer's note. --}}
    <x-page-hero :title="$startup->reference" :kicker="__('innovation.track.title')" tone="night" :crumbs="[[__('innovation.startups.title'), route('startups.index')]]">
        <x-ui.stepper :steps="$steps" :current="$current" tone="night" class="max-w-3xl" />
    </x-page-hero>
    <div class="mx-auto max-w-page px-4 pt-10 pb-16 sm:px-6">
        @if (session('status'))
            <x-ui.alert tone="success" class="mb-8 max-w-3xl">{{ session('status') }}</x-ui.alert>
        @endif
        <article class="max-w-3xl rounded-card border border-line bg-surface p-6">
            <p class="kicker">{{ __('innovation.field.listing') }}</p>
            <h2 class="mt-2 font-display text-2xl font-semibold tracking-tight">{{ $startup->name() }}</h2>
            <p class="mt-3"><x-ui.badge :tone="$startup->listing_status === ListingStatus::Listed ? 'success' : ($startup->listing_status === ListingStatus::Declined ? 'danger' : 'warning')">{{ $startup->listing_status->label() }}</x-ui.badge></p>
            @if (filled($startup->review_note))
                <x-ui.alert class="mt-5" :title="__('innovation.public.note_from_agency')">{{ $startup->review_note }}</x-ui.alert>
            @endif
            @if ($startup->listing_status === ListingStatus::Listed)
                @php $shown = \App\Domain\Organisation\Queries\PublicDirectory::shown($startup, app()->getLocale()); @endphp
                @if ($shown)
                    <p class="mt-5"><x-ui.button :href="route('startups.show', ['locale' => $shown->locale, 'slug' => $shown->slug])">{{ __('innovation.public.see_listing') }}</x-ui.button></p>
                @endif
            @elseif ($startup->listing_status === ListingStatus::ChangesRequested)
                <p class="mt-5 text-ink-muted">{{ __('innovation.public.changes_help') }}</p>
            @endif
            <p class="mt-5 font-mono text-xs text-ink-muted">{{ __('innovation.field.submitted_at') }}: <x-date :date="$startup->created_at" /></p>
        </article>
    </div>
</x-layouts.public>
