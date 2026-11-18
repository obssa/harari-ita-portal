@use('App\Domain\Support\Enums\RequesterType')
@use('App\Domain\Support\Enums\TicketCategory')
@use('App\Domain\Support\Actions\StoreAttachments')
@php
    $field = 'mt-1 block w-full rounded-control border border-line bg-surface px-3 py-2 focus:border-cyan-600 focus:outline-none focus:ring-2 focus:ring-cyan-500/30';
    $describe = fn (string $name, ?string $help = null): string => trim(($help ? "{$name}-help " : '').($errors->has($name) ? "{$name}-error" : ''));
@endphp
@php
    // ADR-0053: arriving from a service page, the form is a request for that service.
    $heading = $requested ? __('support.public.request_title') : __('support.public.title');
    $lead = $requested ? __('support.public.request_lead') : __('support.public.description');
@endphp
<x-layouts.public :title="$heading" :description="$lead">
    {{-- ADR-0017: the request form opens on night like the other service pages; what happens next sits beside it. --}}
    <x-page-hero :title="$heading" :lead="$lead" :kicker="__('support.public.kicker')" tone="night"
                 :crumbs="Route::has('services.index') ? [[__('services.title'), route('services.index')]] : []" />

    <div class="mx-auto max-w-page px-4 pt-10 pb-16 sm:px-6">
        <div class="grid gap-12 lg:grid-cols-[1fr_20rem]">
            <div class="min-w-0 max-w-2xl">
                @if ($errors->any())
                    <x-ui.alert tone="danger" class="mb-8">{{ __('support.public.check') }}</x-ui.alert>
                @endif

                @if ($requested)
                    <div class="mb-8 rounded-card border border-cyan-600/30 bg-cyan-50 px-5 py-4 dark:bg-night-900">
                        <p class="font-mono text-[11px] tracking-wider text-ink-muted uppercase">{{ __('support.public.requesting') }}</p>
                        <p class="mt-1 font-display text-lg font-semibold tracking-tight">{{ $requested['title'] }}</p>
                        <p class="mt-1 text-sm"><a href="{{ $requested['url'] }}" class="text-link underline underline-offset-4">{{ __('support.public.request_back') }}</a></p>
                    </div>
                @endif

                <form method="post" action="{{ route('support.store') }}" enctype="multipart/form-data" class="space-y-8" novalidate>
                    @csrf

                    <fieldset @if ($errors->has('requester_type')) aria-describedby="requester_type-error" @endif>
                        <legend class="kicker">{{ __('support.public.who') }}</legend>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            @foreach (RequesterType::cases() as $type)
                                <label class="flex cursor-pointer items-center gap-3 rounded-control border border-line bg-surface px-4 py-3 has-checked:border-cyan-600 has-checked:ring-2 has-checked:ring-cyan-500/30">
                                    <input type="radio" name="requester_type" value="{{ $type->value }}" @checked(old('requester_type', RequesterType::Resident->value) === $type->value) class="size-5 border-line text-primary-700">
                                    <span class="font-medium">{{ $type->label() }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('requester_type')<p id="requester_type-error" class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </fieldset>

                    <div>
                        <label for="requester_office" class="font-medium">{{ __('support.public.office') }}</label>
                        <input id="requester_office" name="requester_office" type="text" value="{{ old('requester_office') }}" maxlength="200" autocomplete="organization"
                               aria-describedby="{{ $describe('requester_office', 'help') }}" @error('requester_office') aria-invalid="true" @enderror class="{{ $field }}">
                        <p id="requester_office-help" class="mt-1 text-sm text-ink-muted">{{ __('support.public.office_help') }}</p>
                        @error('requester_office')<p id="requester_office-error" class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="name" class="font-medium">{{ __('support.field.name') }}</label>
                            <input id="name" name="name" type="text" value="{{ old('name') }}" required maxlength="120" autocomplete="name"
                                   @error('name') aria-invalid="true" aria-describedby="name-error" @enderror class="{{ $field }}">
                            @error('name')<p id="name-error" class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="email" class="font-medium">{{ __('support.field.email') }}</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" maxlength="190" autocomplete="email" dir="ltr"
                                   aria-describedby="{{ $describe('email', 'help') }}" @error('email') aria-invalid="true" @enderror class="{{ $field }}">
                            @error('email')<p id="email-error" class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="phone" class="font-medium">{{ __('support.field.phone') }}</label>
                            <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" maxlength="20" autocomplete="tel" inputmode="tel" dir="ltr"
                                   aria-describedby="{{ $describe('phone', 'help') }}" @error('phone') aria-invalid="true" @enderror class="{{ $field }}">
                            @error('phone')<p id="phone-error" class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                        </div>
                        <p id="email-help" class="text-sm text-ink-muted sm:col-span-2"><span id="phone-help">{{ __('support.public.contact_help') }}</span></p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="category" class="font-medium">{{ __('support.public.category') }}</label>
                            <select id="category" name="category" required @error('category') aria-invalid="true" aria-describedby="category-error" @enderror class="{{ $field }}">
                                <option value="">—</option>
                                @foreach (TicketCategory::cases() as $category)
                                    <option value="{{ $category->value }}" @selected(old('category', $requested ? TicketCategory::ServiceRequest->value : null) === $category->value)>{{ $category->label() }}</option>
                                @endforeach
                            </select>
                            @error('category')<p id="category-error" class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                        </div>
                        @if ($services !== [])
                            <div>
                                <label for="service_item_id" class="font-medium">{{ __('support.public.service') }}</label>
                                <select id="service_item_id" name="service_item_id" class="{{ $field }}">
                                    <option value="">{{ __('support.public.no_service') }}</option>
                                    @foreach ($services as $id => $title)
                                        <option value="{{ $id }}" @selected(old('service_item_id', $requested['id'] ?? null) === $id)>{{ $title }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>

                    <div>
                        <label for="subject" class="font-medium">{{ __('support.public.subject') }}</label>
                        <input id="subject" name="subject" type="text" value="{{ old('subject', $requested['title'] ?? '') }}" required maxlength="200"
                               @error('subject') aria-invalid="true" aria-describedby="subject-error" @enderror class="{{ $field }}">
                        @error('subject')<p id="subject-error" class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="description" class="font-medium">{{ __('support.public.description_label') }}</label>
                        <textarea id="description" name="description" rows="7" required maxlength="5000"
                                  aria-describedby="{{ $describe('description', 'help') }}" @error('description') aria-invalid="true" @enderror class="{{ $field }}">{{ old('description') }}</textarea>
                        <p id="description-help" class="mt-1 text-sm text-ink-muted">{{ __('support.public.description_help') }}</p>
                        @error('description')<p id="description-error" class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="files" class="font-medium">{{ __('support.public.files') }}</label>
                        <input id="files" name="files[]" type="file" multiple accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                               aria-describedby="{{ $describe('files', 'help') }}" @error('files') aria-invalid="true" @enderror
                               class="mt-1 block w-full text-sm file:me-4 file:rounded-control file:border-0 file:bg-night-900 file:px-4 file:py-2 file:font-mono file:text-xs file:text-cyan-300 file:uppercase">
                        <p id="files-help" class="mt-1 text-sm text-ink-muted">{{ __('support.public.files_help', ['count' => StoreAttachments::MAX_FILES, 'max' => 5]) }}</p>
                        @error('files')<p id="files-error" class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="flex items-start gap-3">
                            <input type="checkbox" name="consent" value="1" @checked(old('consent')) required class="mt-1 size-5 rounded border-line text-primary-700"
                                   @error('consent') aria-invalid="true" aria-describedby="consent-error" @enderror>
                            <span>{{ __('support.public.consent', ['days' => config('portal.support.retention_days')]) }} <a href="{{ route('page.show', ['path' => 'privacy']) }}" class="text-link underline underline-offset-4">{{ __('people.privacy_link') }}</a></span>
                        </label>
                        @error('consent')<p id="consent-error" class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>

                    <x-forms.protection :turnstile-key="$turnstileKey" />
                    @error('form')<p class="text-sm text-danger-600">{{ $message }}</p>@enderror

                    <x-ui.button type="submit" variant="action" size="lg">{{ __('support.public.submit') }}</x-ui.button>
                </form>
            </div>

            <aside class="space-y-6 lg:sticky lg:top-24 lg:self-start" aria-label="{{ __('support.public.next') }}">
                <section class="relative isolate overflow-hidden rounded-card border border-night-line bg-night-900 p-6 text-night-ink">
                    <x-circuit class="absolute inset-x-0 bottom-0 -z-10 h-28 w-full text-cyan-500/25" />
                    <h2 class="kicker [--color-eyebrow:var(--color-cyan-300)]">{{ __('support.public.next') }}</h2>
                    <ol class="mt-4 space-y-4 pb-16 text-sm">
                        @foreach (['received', 'answer', 'resolved'] as $step)
                            <li class="flex gap-3">
                                <span aria-hidden="true" class="grid size-7 shrink-0 place-items-center rounded-control border border-night-line font-mono text-xs text-cyan-300">{{ sprintf('%02d', $loop->iteration) }}</span>
                                <span>{{ __("support.public.step.{$step}") }}</span>
                            </li>
                        @endforeach
                    </ol>
                </section>
                <section class="rounded-card border border-line bg-surface p-5">
                    <h2 class="font-display text-lg font-semibold tracking-tight">{{ __('support.public.track_title') }}</h2>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('support.public.track_text') }}</p>
                    <p class="mt-4"><x-ui.button :href="route('support.lookup')" variant="secondary" size="sm">{{ __('support.track.title') }}</x-ui.button></p>
                </section>
            </aside>
        </div>
    </div>
</x-layouts.public>
