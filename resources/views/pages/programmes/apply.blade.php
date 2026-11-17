@php
    $field = 'mt-1 block w-full rounded-control border border-line bg-surface px-3 py-2 focus:border-cyan-600 focus:outline-none focus:ring-2 focus:ring-cyan-500/30';
    $steps = [__('innovation.public.apply_step.you'), __('innovation.public.apply_step.answers'), __('innovation.public.apply_step.send')];
@endphp
<x-layouts.public :title="__('innovation.programmes.apply_to', ['programme' => $translation->title])" :description="$translation->summary">
    {{-- ADR-0021: one form in three parts (the stepper shows them); nothing is lost if a part is wrong. --}}
    <x-page-hero :title="__('innovation.programmes.apply_to', ['programme' => $translation->title])" :kicker="$cohort?->name ?? $programme->kind->label()" tone="night"
                 :crumbs="[[__('innovation.programmes.title'), route('programmes.index')], [$translation->title, route('programmes.show', ['slug' => $translation->slug])]]">
        <x-ui.stepper :steps="$steps" :current="0" tone="night" class="max-w-3xl" />
    </x-page-hero>

    <div class="mx-auto max-w-page px-4 pt-10 pb-16 sm:px-6">
        <div class="max-w-2xl">
            @if (! $cohort)
                <x-ui.empty-state>{{ __('innovation.programmes.none_open') }}
                    <x-slot:action><x-ui.button :href="route('programmes.show', ['slug' => $translation->slug])" variant="secondary" size="sm">{{ $translation->title }}</x-ui.button></x-slot:action>
                </x-ui.empty-state>
            @else
                @if ($errors->any())
                    <x-ui.alert tone="danger" class="mb-8">{{ __('support.public.check') }}</x-ui.alert>
                @endif
                <p class="mb-8 font-mono text-xs text-ink-muted">{{ __('innovation.field.closes_at') }}: <x-date :date="$cohort->closes_at" :time="true" /></p>
                <form method="post" action="{{ route('applications.store', ['slug' => $translation->slug]) }}" class="space-y-10" novalidate>
                    @csrf
                    <fieldset class="space-y-4">
                        <legend class="kicker">01 · {{ $steps[0] }}</legend>
                        <div>
                            <label for="applicant_name" class="font-medium">{{ __('support.field.name') }}</label>
                            <input id="applicant_name" name="applicant_name" type="text" value="{{ old('applicant_name') }}" required maxlength="120" autocomplete="name"
                                   @error('applicant_name') aria-invalid="true" aria-describedby="applicant_name-error" @enderror class="{{ $field }}">
                            @error('applicant_name')<p id="applicant_name-error" class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="applicant_email" class="font-medium">{{ __('support.field.email') }}</label>
                                <input id="applicant_email" name="applicant_email" type="email" value="{{ old('applicant_email') }}" required maxlength="190" autocomplete="email" dir="ltr"
                                       @error('applicant_email') aria-invalid="true" aria-describedby="applicant_email-error" @enderror class="{{ $field }}">
                                @error('applicant_email')<p id="applicant_email-error" class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="applicant_phone" class="font-medium">{{ __('support.field.phone') }}</label>
                                <input id="applicant_phone" name="applicant_phone" type="tel" value="{{ old('applicant_phone') }}" maxlength="20" autocomplete="tel" inputmode="tel" dir="ltr"
                                       @error('applicant_phone') aria-invalid="true" aria-describedby="applicant_phone-error" @enderror class="{{ $field }}">
                                @error('applicant_phone')<p id="applicant_phone-error" class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div>
                            <label for="team_name" class="font-medium">{{ __('innovation.field.team_name') }}</label>
                            <input id="team_name" name="team_name" type="text" value="{{ old('team_name') }}" maxlength="200" aria-describedby="team_name-help" class="{{ $field }}">
                            <p id="team_name-help" class="mt-1 text-sm text-ink-muted">{{ __('innovation.public.team_help') }}</p>
                        </div>
                    </fieldset>

                    @if ($questions !== [])
                        <fieldset class="space-y-4">
                            <legend class="kicker">02 · {{ $steps[1] }}</legend>
                            @foreach ($questions as $question)
                                @php $name = "answers.{$question['key']}"; $id = 'answer-'.$question['key']; @endphp
                                <div>
                                    <label for="{{ $id }}" class="font-medium">{{ $question['label'] }}@if (! $question['required']) <span class="text-sm font-normal text-ink-muted">({{ __('innovation.public.optional') }})</span>@endif</label>
                                    @if ($question['long'])
                                        <textarea id="{{ $id }}" name="answers[{{ $question['key'] }}]" rows="5" maxlength="3000" @if ($question['required']) required @endif
                                                  @error($name) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror class="{{ $field }}">{{ old($name) }}</textarea>
                                    @else
                                        <input id="{{ $id }}" name="answers[{{ $question['key'] }}]" type="text" value="{{ old($name) }}" maxlength="300" @if ($question['required']) required @endif
                                               @error($name) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror class="{{ $field }}">
                                    @endif
                                    @error($name)<p id="{{ $id }}-error" class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                                </div>
                            @endforeach
                        </fieldset>
                    @endif

                    <fieldset class="space-y-4">
                        <legend class="kicker">03 · {{ $steps[2] }}</legend>
                        <label class="flex items-start gap-3">
                            <input type="checkbox" name="consent" value="1" @checked(old('consent')) required class="mt-1 size-5 rounded border-line text-primary-700"
                                   @error('consent') aria-invalid="true" aria-describedby="consent-error" @enderror>
                            <span>{{ __('innovation.public.application_consent') }} <a href="{{ route('page.show', ['path' => 'privacy']) }}" class="text-link underline underline-offset-4">{{ __('people.privacy_link') }}</a></span>
                        </label>
                        @error('consent')<p id="consent-error" class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                        <x-forms.protection :turnstile-key="$turnstileKey" />
                        @error('form')<p class="text-sm text-danger-600">{{ $message }}</p>@enderror
                        <x-ui.button type="submit" variant="action" size="lg">{{ __('innovation.public.send_application') }}</x-ui.button>
                    </fieldset>
                </form>
            @endif
        </div>
    </div>
</x-layouts.public>
