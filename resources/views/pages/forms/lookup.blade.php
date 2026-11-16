@php
    $field = 'mt-1 block w-full rounded-control border border-line bg-surface px-3 py-2 focus:border-cyan-600 focus:outline-none focus:ring-2 focus:ring-cyan-500/30';
@endphp
<x-layouts.public :title="__('forms.lookup_title')" :alternates="null">
    {{-- ADR-0003: the reference and the contact given with it, both needed; each field says when it is wrong. --}}
    <x-page-hero :title="__('forms.lookup_title')" :lead="__('forms.lookup_help')" tone="night" :crumbs="[[__('forms.contact_title'), route('contact')]]" />
    <div class="mx-auto max-w-page px-4 pt-10 pb-16 sm:px-6">
        <form method="post" action="{{ route('tickets.verify') }}" class="max-w-xl space-y-6 rounded-card border border-line bg-surface p-6" novalidate>
            @csrf
            <div>
                <label for="reference" class="font-medium">{{ __('forms.reference') }}</label>
                <input id="reference" name="reference" type="text" value="{{ old('reference', $typed) }}" placeholder="{{ __('forms.reference_placeholder') }}" maxlength="20" autocomplete="off" spellcheck="false" dir="ltr" required
                       @error('reference') aria-invalid="true" aria-describedby="reference-error" @enderror class="{{ $field }} font-mono uppercase">
                @error('reference')<p id="reference-error" class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="contact" class="font-medium">{{ __('forms.lookup_contact') }}</label>
                <input id="contact" name="contact" type="text" value="{{ old('contact') }}" maxlength="190" autocomplete="email" dir="ltr" required
                       aria-describedby="contact-help @error('contact') contact-error @enderror" @error('contact') aria-invalid="true" @enderror class="{{ $field }}">
                <p id="contact-help" class="mt-1 text-sm text-ink-muted">{{ __('forms.lookup_contact_help') }}</p>
                @error('contact')<p id="contact-error" class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
            </div>
            <x-ui.button type="submit">{{ __('forms.lookup_button') }}</x-ui.button>
        </form>
    </div>
</x-layouts.public>
