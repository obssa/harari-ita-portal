@php
    $base = url('/api/v1');
    $example = 'curl -H "X-Api-Key: $KEY" "'.$base.'/datasets?lang='.app()->getLocale().'"';
@endphp
<x-layouts.public :title="__('developers.title')" :description="__('developers.description')">
    {{-- ADR-0026: the API in one page, read from its own OpenAPI document; what needs a key and what does not. --}}
    <x-page-hero :title="__('developers.title')" :lead="__('developers.description')" :kicker="__('developers.kicker')" tone="night">
        <x-slot:actions>
            <x-ui.button :href="route('api.v1.openapi')" variant="action">{{ __('developers.openapi') }}</x-ui.button>
            <x-ui.button :href="route('datasets.index')" variant="light">{{ __('opendata.title') }}</x-ui.button>
        </x-slot:actions>
        <x-ui.code :label="__('developers.example_label')" class="max-w-3xl">{{ $example }}</x-ui.code>
    </x-page-hero>

    <div class="mx-auto max-w-page px-4 pt-10 pb-16 sm:px-6">
        <div class="grid gap-10 lg:grid-cols-3">
            <div class="min-w-0 lg:col-span-2">
                <section aria-labelledby="dev-endpoints">
                    <h2 id="dev-endpoints" class="heading-2">{{ __('developers.endpoints') }}</h2>
                    <p class="mt-2 max-w-prose text-ink-muted">{{ __('developers.endpoints_lead') }}</p>
                    @foreach ($groups as $tag => $operations)
                        <h3 class="kicker mt-8">{{ $tag }}</h3>
                        <div class="mt-3 overflow-x-auto rounded-card border border-line bg-surface">
                            <table class="w-full text-start text-sm">
                                <caption class="sr-only">{{ $tag }}</caption>
                                <thead class="bg-surface-muted text-xs text-ink-muted">
                                    <tr>
                                        <th scope="col" class="px-4 py-2 text-start font-medium">{{ __('developers.address') }}</th>
                                        <th scope="col" class="px-4 py-2 text-start font-medium">{{ __('developers.what') }}</th>
                                        <th scope="col" class="px-4 py-2 text-start font-medium">{{ __('developers.scope') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($operations as $operation)
                                        <tr class="border-t border-line">
                                            <td class="px-4 py-2 font-mono text-xs whitespace-nowrap" dir="ltr"><span class="text-cyan-700 dark:text-cyan-300">{{ $operation['method'] }}</span> {{ $operation['path'] }}</td>
                                            <td class="px-4 py-2">{{ $operation['summary'] }}</td>
                                            <td class="px-4 py-2 font-mono text-xs">{{ $operation['scope'] ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                </section>

                <section aria-labelledby="dev-key" class="mt-12">
                    <h2 id="dev-key" class="heading-2">{{ __('developers.key_title') }}</h2>
                    <ol class="mt-4 max-w-prose list-decimal space-y-2 ps-6">
                        <li>{{ __('developers.key_step_ask') }} <a href="{{ route('contact') }}" class="text-link underline underline-offset-4">{{ __('developers.key_contact') }}</a></li>
                        <li>{{ __('developers.key_step_scopes') }}</li>
                        <li>{{ __('developers.key_step_send') }}</li>
                    </ol>
                    <dl class="mt-6 grid gap-3 sm:grid-cols-2">
                        @foreach ($scopes as $scope => $label)
                            <div class="rounded-card border border-line bg-surface p-4">
                                <dt class="font-mono text-xs text-cyan-700 dark:text-cyan-300">{{ $scope }}</dt>
                                <dd class="mt-1">{{ $label }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>

                <section aria-labelledby="dev-open" class="mt-12">
                    <h2 id="dev-open" class="heading-2">{{ __('developers.open_title') }}</h2>
                    <p class="mt-2 max-w-prose text-ink-muted">{{ __('developers.open_lead') }}</p>
                    <ul class="mt-4 space-y-2">
                        <li><a href="{{ route('api.v1.openapi') }}" class="text-link underline underline-offset-4">{{ __('developers.openapi') }}</a></li>
                        <li><a href="{{ route('datasets.catalog') }}" class="text-link underline underline-offset-4">{{ __('opendata.catalog_link') }}</a></li>
                        <li><a href="{{ route('datasets.index') }}" class="text-link underline underline-offset-4">{{ __('developers.open_files') }}</a></li>
                        <li><a href="{{ route('status.json') }}" class="text-link underline underline-offset-4">{{ __('developers.open_status') }}</a></li>
                    </ul>
                </section>
            </div>

            <aside aria-label="{{ __('developers.facts') }}" class="space-y-6">
                <dl class="space-y-4 rounded-card border border-line bg-surface p-5 text-sm">
                    <div>
                        <dt class="kicker">{{ __('developers.base') }}</dt>
                        <dd class="mt-1 font-mono text-xs break-all" dir="ltr">{{ $base }}</dd>
                    </div>
                    <div>
                        <dt class="kicker">{{ __('developers.limits') }}</dt>
                        <dd class="mt-1">{{ __('developers.limits_text', ['rate' => $rate]) }}</dd>
                    </div>
                    <div>
                        <dt class="kicker">{{ __('developers.paging') }}</dt>
                        <dd class="mt-1">{{ __('developers.paging_text', ['per_page' => $perPage, 'max' => $maxPerPage]) }}</dd>
                    </div>
                    <div>
                        <dt class="kicker">{{ __('developers.languages') }}</dt>
                        <dd class="mt-1">{{ __('developers.languages_text') }}</dd>
                    </div>
                    <div>
                        <dt class="kicker">{{ __('developers.status') }}</dt>
                        <dd class="mt-1"><a href="{{ route('status') }}" class="text-link underline underline-offset-4">{{ __('developers.status_link') }}</a></dd>
                    </div>
                </dl>

                <section aria-labelledby="dev-changes" class="rounded-card border border-line bg-surface p-5">
                    <h2 id="dev-changes" class="kicker">{{ __('developers.changelog') }}</h2>
                    <ol class="mt-3 space-y-4 text-sm">
                        @foreach ($changelog as [$date, $key])
                            <li>
                                <p class="font-mono text-xs text-ink-muted"><x-date :date="\Carbon\CarbonImmutable::parse($date)" /></p>
                                <p class="mt-1">{{ __($key) }}</p>
                            </li>
                        @endforeach
                    </ol>
                </section>
            </aside>
        </div>
    </div>
</x-layouts.public>
