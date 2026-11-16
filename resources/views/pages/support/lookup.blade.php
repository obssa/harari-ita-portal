@php
    $field = 'mt-1 block w-full rounded-control border border-line bg-surface px-3 py-2 focus:border-cyan-600 focus:outline-none focus:ring-2 focus:ring-cyan-500/30';
@endphp
<x-layouts.public :title="__('support.track.title')" :description="__('support.track.description')">
    {{-- ADR-0017 / ADR-0003: the reference and the contact given with it, both needed; each field says when it is wrong. --}}
    <x-page-hero :title="__('support.track.title')" :lead="__('support.track.description')" :kicker="__('support.public.kicker')" tone="night"
                 :crumbs="[[__('support.public.title'), route('support.create')]]" />

    <div class="mx-auto max-w-page px-4 pt-10 pb-16 sm:px-6">
        <form method="post" action="{{ route('support.verify') }}" class="max-w-xl space-y-6 rounded-card border border-line bg-surface p-6" novalidate>
            @csrf
            <div>
                <label for="reference" class="font-medium">{{ __('support.reference') }}</label>
                <input id="reference" name="reference" type="text" value="{{ old('reference', $reference) }}" required maxlength="20" autocomplete="off" spellcheck="false" dir="ltr"
                       placeholder="{{ __('support.track.placeholder') }}"
                       @error('reference') aria-invalid="true" aria-describedby="reference-error" @enderror class="{{ $field }} font-mono uppercase">
                @error('reference')<p id="reference-error" class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="contact" class="font-medium">{{ __('support.track.contact') }}</label>
                <input id="contact" name="contact" type="text" value="{{ old('contact') }}" required maxlength="190" autocomplete="email" dir="ltr"
                       aria-describedby="contact-help @error('contact') contact-error @enderror" @error('contact') aria-invalid="true" @enderror class="{{ $field }}">
                <p id="contact-help" class="mt-1 text-sm text-ink-muted">{{ __('support.track.contact_help') }}</p>
                @error('contact')<p id="contact-error" class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
            </div>
            <x-ui.button type="submit">{{ __('support.track.submit') }}</x-ui.button>
        </form>
    </div>
</x-layouts.public>
