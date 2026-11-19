<x-layouts.public :title="$translation->title" :description="$translation->intro" :alternates="$alternates">
    <div class="mx-auto max-w-page px-4 py-10 sm:px-6">
        <div class="grid gap-10 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <h1 class="heading-1">{{ $translation->title }}</h1>
                @if (filled($translation->intro))
                    <p class="mt-3 max-w-prose whitespace-pre-line text-lg text-ink-muted">{{ $translation->intro }}</p>
                @endif
                @if ($about)
                    <div class="mt-6 max-w-xl rounded-card border border-cyan-600/30 bg-cyan-50 px-5 py-4 dark:bg-night-900">
                        <p class="font-mono text-[11px] tracking-wider text-ink-muted uppercase">{{ __('support.public.requesting') }}</p>
                        <p class="mt-1 font-display text-lg font-semibold tracking-tight">{{ $about['title'] }}</p>
                        <p class="mt-1 text-sm"><a href="{{ $about['url'] }}" class="text-link underline underline-offset-4">{{ __('support.public.request_back') }}</a></p>
                    </div>
                @endif
                @if ($closed)
                    {{-- ADR-0053: an opportunity's form only takes applications from its page while it is open. --}}
                    <x-ui.empty-state :title="__('forms.applications_closed_title')" class="mt-8 max-w-xl">
                        {{ __('forms.applications_closed_text') }}
                        <x-slot:action><x-ui.button :href="route('opportunities.index')" variant="secondary">{{ __('opportunities.title') }}</x-ui.button></x-slot:action>
                    </x-ui.empty-state>
                @else
                    <x-forms.public-form :form="$form" :translation="$translation" :turnstile-key="$turnstileKey" :about="$about" class="mt-8" />
                @endif
            </div>
            <aside class="space-y-3 text-sm text-ink-muted" aria-label="{{ __('forms.after_title') }}">
                <h2 class="heading-3 text-ink">{{ __('forms.after_title') }}</h2>
                <p>{{ __('forms.after', ['days' => $form->response_days]) }}</p>
                <p><a href="{{ route('tickets.lookup') }}" class="text-link underline-offset-4 hover:underline">{{ __('forms.lookup_link') }}</a></p>
            </aside>
        </div>
    </div>
</x-layouts.public>
