@use('App\Domain\Innovation\Enums\StartupStage')
@php
    $field = 'mt-1 block w-full rounded-control border border-line bg-surface px-3 py-2 focus:border-cyan-600 focus:outline-none focus:ring-2 focus:ring-cyan-500/30';
    $text = [
        ['name', 'innovation.public.field.name', 'text', 200, true, 'organization'],
        ['web_address', 'innovation.field.website', 'url', 300, false, 'url'],
        ['area', 'innovation.public.field.area', 'text', 120, false, null],
    ];
@endphp
<x-layouts.public :title="__('innovation.public.apply_title')" :description="__('innovation.public.apply_description')">
    {{-- ADR-0020: founders ask to be listed in the language they write in; the Agency reviews before anything is public. --}}
    <x-page-hero :title="__('innovation.public.apply_title')" :lead="__('innovation.public.apply_description')" :kicker="__('nav.innovation')" tone="night"
                 :crumbs="[[__('innovation.startups.title'), route('startups.index')]]" />

    <div class="mx-auto max-w-page px-4 pt-10 pb-16 sm:px-6">
        <div class="grid gap-12 lg:grid-cols-[1fr_20rem]">
            <div class="min-w-0 max-w-2xl">
                @if ($errors->any())
                    <x-ui.alert tone="danger" class="mb-8">{{ __('support.public.check') }}</x-ui.alert>
                @endif
                <form method="post" action="{{ route('startups.apply.store') }}" class="space-y-6" novalidate>
                    @csrf
                    <fieldset class="space-y-4">
                        <legend class="kicker">{{ __('innovation.public.about_startup') }}</legend>
                        @foreach ($text as [$name, $label, $type, $max, $required, $autocomplete])
                            <div>
                                <label for="{{ $name }}" class="font-medium">{{ __($label) }}</label>
                                <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name) }}" maxlength="{{ $max }}" @if ($required) required @endif @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
                                       @if ($type === 'url') dir="ltr" @endif
                                       @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror class="{{ $field }}">
                                @error($name)<p id="{{ $name }}-error" class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                            </div>
                        @endforeach
                        <div>
                            <label for="summary" class="font-medium">{{ __('innovation.public.field.summary') }}</label>
                            <textarea id="summary" name="summary" rows="3" required maxlength="400" aria-describedby="summary-help @error('summary') summary-error @enderror" @error('summary') aria-invalid="true" @enderror class="{{ $field }}">{{ old('summary') }}</textarea>
                            <p id="summary-help" class="mt-1 text-sm text-ink-muted">{{ __('innovation.public.summary_help') }}</p>
                            @error('summary')<p id="summary-error" class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="products" class="font-medium">{{ __('innovation.field.products') }}</label>
                            <textarea id="products" name="products" rows="3" maxlength="2000" aria-describedby="products-help" class="{{ $field }}">{{ old('products') }}</textarea>
                            <p id="products-help" class="mt-1 text-sm text-ink-muted">{{ __('admin.services.help.one_per_line') }}</p>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="stage" class="font-medium">{{ __('innovation.field.stage') }}</label>
                                <select id="stage" name="stage" required @error('stage') aria-invalid="true" aria-describedby="stage-error" @enderror class="{{ $field }}">
                                    <option value="">—</option>
                                    @foreach (StartupStage::cases() as $option)
                                        <option value="{{ $option->value }}" @selected(old('stage') === $option->value)>{{ $option->label() }}</option>
                                    @endforeach
                                </select>
                                @error('stage')<p id="stage-error" class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="sector_id" class="font-medium">{{ __('innovation.field.sector') }}</label>
                                <select id="sector_id" name="sector_id" class="{{ $field }}">
                                    <option value="">—</option>
                                    @foreach ($sectors as $id => $name)
                                        <option value="{{ $id }}" @selected(old('sector_id') === $id)>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="founded_year" class="font-medium">{{ __('innovation.field.founded_year') }}</label>
                                <input id="founded_year" name="founded_year" type="number" inputmode="numeric" min="1950" max="{{ now()->format('Y') }}" value="{{ old('founded_year') }}" dir="ltr"
                                       @error('founded_year') aria-invalid="true" aria-describedby="founded_year-error" @enderror class="{{ $field }}">
                                @error('founded_year')<p id="founded_year-error" class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="team_size" class="font-medium">{{ __('innovation.field.team_size') }}</label>
                                <input id="team_size" name="team_size" type="number" inputmode="numeric" min="1" value="{{ old('team_size') }}" dir="ltr" class="{{ $field }}">
                            </div>
                        </div>
                        <label class="flex items-start gap-3">
                            <input type="checkbox" name="label_claimed" value="1" @checked(old('label_claimed')) class="mt-1 size-5 rounded border-line text-primary-700">
                            <span>{{ __('innovation.public.label_claim') }}</span>
                        </label>
                    </fieldset>

                    <fieldset class="space-y-4">
                        <legend class="kicker">{{ __('innovation.public.contact') }}</legend>
                        <p class="text-sm text-ink-muted">{{ __('innovation.public.contact_help') }}</p>
                        <div>
                            <label for="founder_name" class="font-medium">{{ __('support.field.name') }}</label>
                            <input id="founder_name" name="founder_name" type="text" value="{{ old('founder_name') }}" required maxlength="120" autocomplete="name"
                                   @error('founder_name') aria-invalid="true" aria-describedby="founder_name-error" @enderror class="{{ $field }}">
                            @error('founder_name')<p id="founder_name-error" class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="founder_email" class="font-medium">{{ __('support.field.email') }}</label>
                                <input id="founder_email" name="founder_email" type="email" value="{{ old('founder_email') }}" required maxlength="190" autocomplete="email" dir="ltr"
                                       @error('founder_email') aria-invalid="true" aria-describedby="founder_email-error" @enderror class="{{ $field }}">
                                @error('founder_email')<p id="founder_email-error" class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="founder_phone" class="font-medium">{{ __('support.field.phone') }}</label>
                                <input id="founder_phone" name="founder_phone" type="tel" value="{{ old('founder_phone') }}" maxlength="20" autocomplete="tel" inputmode="tel" dir="ltr"
                                       @error('founder_phone') aria-invalid="true" aria-describedby="founder_phone-error" @enderror class="{{ $field }}">
                                @error('founder_phone')<p id="founder_phone-error" class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </fieldset>

                    <div>
                        <label class="flex items-start gap-3">
                            <input type="checkbox" name="consent" value="1" @checked(old('consent')) required class="mt-1 size-5 rounded border-line text-primary-700"
                                   @error('consent') aria-invalid="true" aria-describedby="consent-error" @enderror>
                            <span>{{ __('innovation.public.consent') }} <a href="{{ route('page.show', ['path' => 'privacy']) }}" class="text-link underline underline-offset-4">{{ __('people.privacy_link') }}</a></span>
                        </label>
                        @error('consent')<p id="consent-error" class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>

                    <x-forms.protection :turnstile-key="$turnstileKey" />
                    @error('form')<p class="text-sm text-danger-600">{{ $message }}</p>@enderror
                    <x-ui.button type="submit" variant="action" size="lg">{{ __('innovation.public.submit') }}</x-ui.button>
                </form>
            </div>
            <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start" aria-label="{{ __('support.public.next') }}">
                <section class="relative isolate overflow-hidden rounded-card border border-night-line bg-night-900 p-6 text-night-ink">
                    <x-circuit class="absolute inset-x-0 bottom-0 -z-10 h-28 w-full text-cyan-500/25" />
                    <h2 class="kicker [--color-eyebrow:var(--color-cyan-300)]">{{ __('support.public.next') }}</h2>
                    <ol class="mt-4 space-y-4 pb-16 text-sm">
                        @foreach (['received', 'review', 'listed'] as $step)
                            <li class="flex gap-3">
                                <span aria-hidden="true" class="grid size-7 shrink-0 place-items-center rounded-control border border-night-line font-mono text-xs text-cyan-300">{{ sprintf('%02d', $loop->iteration) }}</span>
                                <span>{{ __("innovation.public.step.{$step}") }}</span>
                            </li>
                        @endforeach
                    </ol>
                </section>
                <p class="text-sm"><a href="{{ route('startups.listing') }}" class="text-link underline underline-offset-4">{{ __('innovation.track.title') }}</a></p>
            </aside>
        </div>
    </div>
</x-layouts.public>
