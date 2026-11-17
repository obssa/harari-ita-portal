@php
    $field = 'mt-1 block w-full rounded-control border border-line bg-surface px-3 py-2 focus:border-cyan-600 focus:outline-none focus:ring-2 focus:ring-cyan-500/30';
    $steps = [__('innovation.public.register_step.team'), __('innovation.public.register_step.contact'), __('innovation.public.apply_step.send')];
@endphp
<x-layouts.public :title="__('innovation.challenges.register_for', ['challenge' => $translation->title])" :description="$translation->summary">
    {{-- ADR-0022: one team registers once; the contact gets the team reference, which opens the submission page. --}}
    <x-page-hero :title="__('innovation.challenges.register_for', ['challenge' => $translation->title])" :kicker="__('innovation.phase.registration')" tone="night"
                 :crumbs="[[__('innovation.challenges.title'), route('challenges.index')], [$translation->title, route('challenges.show', ['slug' => $translation->slug])]]">
        <x-ui.stepper :steps="$steps" :current="0" class="max-w-3xl [&_.text-ink]:text-night-ink [&_.text-ink-muted]:text-night-muted" />
    </x-page-hero>

    <div class="mx-auto max-w-page px-4 pt-10 pb-16 sm:px-6">
        <div class="max-w-2xl">
            @if (! $open)
                <x-ui.empty-state>{{ __('innovation.validation.registration_closed') }}
                    <x-slot:action><x-ui.button :href="route('challenges.show', ['slug' => $translation->slug])" variant="secondary" size="sm">{{ $translation->title }}</x-ui.button></x-slot:action>
                </x-ui.empty-state>
            @else
                @if ($errors->any())
                    <x-ui.alert tone="danger" class="mb-8">{{ __('support.public.check') }}</x-ui.alert>
                @endif
                <p class="mb-8 font-mono text-xs text-ink-muted">{{ __('innovation.field.registration_closes_at') }}: <x-date :date="$challenge->registration_closes_at" :time="true" /></p>
                <form method="post" action="{{ route('teams.store', ['slug' => $translation->slug]) }}" class="space-y-10" novalidate>
                    @csrf
                    <fieldset class="space-y-4">
                        <legend class="kicker">01 · {{ $steps[0] }}</legend>
                        <div>
                            <label for="name" class="font-medium">{{ __('innovation.field.team_name') }}</label>
                            <input id="name" name="name" type="text" value="{{ old('name') }}" required maxlength="120"
                                   @error('name') aria-invalid="true" aria-describedby="name-error" @enderror class="{{ $field }}">
                            @error('name')<p id="name-error" class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="members" class="font-medium">{{ __('innovation.field.members') }} <span class="text-sm font-normal text-ink-muted">({{ __('innovation.public.optional') }})</span></label>
                            <textarea id="members" name="members" rows="4" maxlength="3000" aria-describedby="members-help @error('members') members-error @enderror"
                                      @error('members') aria-invalid="true" @enderror class="{{ $field }}">{{ old('members') }}</textarea>
                            <p id="members-help" class="mt-1 text-sm text-ink-muted">{{ __('innovation.public.members_help', ['max' => $challenge->max_team_size]) }}</p>
                            @error('members')<p id="members-error" class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                        </div>
                        <label class="flex items-start gap-3">
                            <input type="checkbox" name="show_in_results" value="1" @checked(old('show_in_results')) class="mt-1 size-5 rounded border-line text-primary-700">
                            <span>{{ __('innovation.public.show_in_results') }}</span>
                        </label>
                    </fieldset>

                    <fieldset class="space-y-4">
                        <legend class="kicker">02 · {{ $steps[1] }}</legend>
                        <div>
                            <label for="contact_name" class="font-medium">{{ __('support.field.name') }}</label>
                            <input id="contact_name" name="contact_name" type="text" value="{{ old('contact_name') }}" required maxlength="120" autocomplete="name"
                                   @error('contact_name') aria-invalid="true" aria-describedby="contact_name-error" @enderror class="{{ $field }}">
                            @error('contact_name')<p id="contact_name-error" class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="contact_email" class="font-medium">{{ __('support.field.email') }}</label>
                                <input id="contact_email" name="contact_email" type="email" value="{{ old('contact_email') }}" required maxlength="190" autocomplete="email" dir="ltr"
                                       @error('contact_email') aria-invalid="true" aria-describedby="contact_email-error" @enderror class="{{ $field }}">
                                @error('contact_email')<p id="contact_email-error" class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="contact_phone" class="font-medium">{{ __('support.field.phone') }} <span class="text-sm font-normal text-ink-muted">({{ __('innovation.public.optional') }})</span></label>
                                <input id="contact_phone" name="contact_phone" type="tel" value="{{ old('contact_phone') }}" maxlength="20" autocomplete="tel" inputmode="tel" dir="ltr"
                                       @error('contact_phone') aria-invalid="true" aria-describedby="contact_phone-error" @enderror class="{{ $field }}">
                                @error('contact_phone')<p id="contact_phone-error" class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="space-y-4">
                        <legend class="kicker">03 · {{ $steps[2] }}</legend>
                        <label class="flex items-start gap-3">
                            <input type="checkbox" name="consent" value="1" @checked(old('consent')) required class="mt-1 size-5 rounded border-line text-primary-700"
                                   @error('consent') aria-invalid="true" aria-describedby="consent-error" @enderror>
                            <span>{{ __('innovation.public.team_consent') }} <a href="{{ route('page.show', ['path' => 'privacy']) }}" class="text-link underline underline-offset-4">{{ __('people.privacy_link') }}</a></span>
                        </label>
                        @error('consent')<p id="consent-error" class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                        <x-forms.protection :turnstile-key="$turnstileKey" />
                        @error('form')<p class="text-sm text-danger-600">{{ $message }}</p>@enderror
                        <x-ui.button type="submit" variant="action" size="lg">{{ __('innovation.public.register_team') }}</x-ui.button>
                    </fieldset>
                </form>
            @endif
        </div>
    </div>
</x-layouts.public>
