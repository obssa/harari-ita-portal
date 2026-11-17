@php
    $locale = app()->getLocale();
    $next = $dataset->nextUpdateDue();
    $overdue = $dataset->isOverdue();
    $files = $dataset->distributions;
    $apiUrl = url('/api/v1/datasets/'.$dataset->id);
    $size = fn (?int $bytes): ?string => $bytes === null ? null : \Illuminate\Support\Number::fileSize($bytes, precision: 1);
    $count = fn (?int $n): ?string => $n === null ? null : (string) \Illuminate\Support\Number::format($n, locale: 'en');
    $publisher = $office?->getAttribute('name') ?? __('site.name');
@endphp
<x-layouts.public :title="$translation->title" :description="$translation->summary" :alternates="$alternates" :json-ld="$jsonLd">
    {{-- ADR-0025: what the data is, the files to take, a look at the first rows, then the terms and the facts. --}}
    <x-page-hero :title="$translation->title" :lead="$translation->summary" :kicker="$publisher" tone="night"
                 :crumbs="[[__('opendata.title'), route('datasets.index')]]" :heading-lang="$translation->locale">
        <p class="flex flex-wrap items-center gap-2">
            @foreach ($files->map(fn ($d) => $d->format)->unique(fn ($f) => $f->value) as $format)
                <x-ui.badge tone="cyan">{{ $format->code() }}</x-ui.badge>
            @endforeach
            <x-ui.badge tone="ink">{{ $dataset->licence->spdx() }}</x-ui.badge>
            @if ($overdue)
                <x-ui.badge tone="warning">{{ __('opendata.overdue') }}</x-ui.badge>
            @endif
        </p>
    </x-page-hero>

    <article class="mx-auto max-w-page px-4 pt-10 pb-16 sm:px-6">
        <div class="grid gap-10 lg:grid-cols-3">
            <div class="min-w-0 lg:col-span-2">
                <section aria-labelledby="data-files">
                    <h2 id="data-files" class="heading-2">{{ __('opendata.files') }}</h2>
                    @if ($files->isEmpty())
                        <x-ui.empty-state class="mt-4">{{ __('opendata.no_files') }}</x-ui.empty-state>
                    @else
                        <ul class="mt-4 divide-y divide-line rounded-card border border-line bg-surface">
                            @foreach ($files as $file)
                                <li class="flex flex-wrap items-center justify-between gap-4 p-4 sm:p-5">
                                    <div class="min-w-0">
                                        <p class="flex flex-wrap items-center gap-2 font-medium">
                                            <x-ui.badge tone="cyan">{{ $file->format->code() }}</x-ui.badge>
                                            <span>{{ $file->title() }}</span>
                                        </p>
                                        <p class="mt-1 font-mono text-xs text-ink-muted">
                                            {{ collect([
                                                $size($file->byte_size),
                                                $file->row_count !== null ? trans_choice('opendata.rows', $file->row_count, ['count' => $count($file->row_count)]) : null,
                                                $file->columns ? trans_choice('opendata.columns', count($file->columns), ['count' => count($file->columns)]) : null,
                                                $file->download_count > 0 ? trans_choice('opendata.downloads', $file->download_count, ['count' => $count($file->download_count)]) : null,
                                            ])->filter()->implode(' · ') }}
                                        </p>
                                        @if ($file->checksum)
                                            <p class="mt-1 truncate font-mono text-[0.7rem] text-ink-muted" dir="ltr"><span class="sr-only">{{ __('opendata.checksum') }}: </span>{{ 'sha256 '.$file->checksum }}</p>
                                        @endif
                                    </div>
                                    @if ($file->format->isFile())
                                        <x-ui.button :href="route('datasets.download', ['slug' => $translation->slug, 'distribution' => $file->id])" variant="accent" size="sm">
                                            {{ __('opendata.download_format', ['format' => $file->format->code()]) }}<span class="sr-only">: {{ $file->title() }}</span>
                                        </x-ui.button>
                                    @else
                                        <x-ui.button :href="$file->url" variant="secondary" size="sm" rel="noopener">{{ __('opendata.open_api') }}<span class="sr-only">: {{ $file->title() }}</span></x-ui.button>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                @if ($preview)
                    <section aria-labelledby="data-preview" class="mt-12">
                        <h2 id="data-preview" class="heading-2">{{ __('opendata.preview') }}</h2>
                        <p class="mt-2 max-w-prose text-ink-muted">{{ __('opendata.preview_lead', ['rows' => count($preview->preview), 'total' => $count($preview->row_count) ?? '—', 'file' => $preview->title()]) }}</p>
                        <div class="mt-4 overflow-x-auto rounded-card border border-line bg-surface" tabindex="0" role="region" aria-labelledby="data-preview">
                            <table class="w-full min-w-max text-start text-sm">
                                <caption class="sr-only">{{ __('opendata.preview_caption', ['file' => $preview->title()]) }}</caption>
                                <thead class="bg-surface-muted">
                                    <tr>
                                        @foreach ($preview->columns ?? [] as $column)
                                            <th scope="col" class="border-b border-line px-3 py-2 text-start font-mono text-xs font-semibold whitespace-nowrap">{{ $column }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($preview->preview as $row)
                                        <tr class="border-b border-line/60 last:border-0">
                                            @foreach ($row as $cell)
                                                <td class="px-3 py-2 whitespace-nowrap tabular-nums">{{ $cell }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif

                @if (trim($body) !== '')
                    <section aria-labelledby="data-about" class="mt-12">
                        <h2 id="data-about" class="heading-2">{{ __('opendata.about') }}</h2>
                        <div class="prose-content mt-4 max-w-prose">{!! $body !!}</div>
                    </section>
                @endif

                @if (filled($translation->methodology))
                    <section aria-labelledby="data-method" class="mt-12">
                        <h2 id="data-method" class="heading-3">{{ __('opendata.field.methodology') }}</h2>
                        <p class="mt-3 max-w-prose whitespace-pre-line text-ink-muted">{{ $translation->methodology }}</p>
                    </section>
                @endif

                <section aria-labelledby="data-cite" class="mt-12">
                    <h2 id="data-cite" class="heading-3">{{ __('opendata.cite') }}</h2>
                    <x-ui.code class="mt-3" :label="__('opendata.cite_label')" :wrap="true">{{ __('opendata.cite_text', ['publisher' => $publisher, 'title' => $translation->title, 'year' => ($dataset->data_updated_on ?? $dataset->created_at)?->year, 'url' => route('datasets.show', ['slug' => $translation->slug])]) }}</x-ui.code>
                </section>
            </div>

            <aside aria-label="{{ __('opendata.facts') }}" class="space-y-6">
                <dl class="space-y-4 rounded-card border border-line bg-surface p-5 text-sm">
                    <div>
                        <dt class="kicker">{{ __('opendata.field.licence') }}</dt>
                        <dd class="mt-1"><a href="{{ $dataset->licence->url() }}" rel="license noopener" class="text-link underline-offset-4 hover:underline">{{ $dataset->licence->label() }}</a></dd>
                    </div>
                    @if ($office)
                        <div>
                            <dt class="kicker">{{ __('opendata.field.publisher') }}</dt>
                            <dd class="mt-1"><a href="{{ route('offices.show', ['locale' => $office->locale, 'slug' => $office->slug]) }}" class="text-link underline-offset-4 hover:underline">{{ $office->getAttribute('name') }}</a></dd>
                        </div>
                    @endif
                    <div>
                        <dt class="kicker">{{ __('opendata.field.frequency') }}</dt>
                        <dd class="mt-1">{{ $dataset->frequency->label() }}</dd>
                    </div>
                    @if ($dataset->data_updated_on)
                        <div>
                            <dt class="kicker">{{ __('opendata.field.data_updated_on') }}</dt>
                            <dd class="mt-1"><x-date :date="$dataset->data_updated_on" /></dd>
                        </div>
                    @endif
                    @if ($next)
                        <div>
                            <dt class="kicker">{{ __('opendata.field.next_due') }}</dt>
                            <dd class="mt-1"><x-date :date="$next" />@if ($overdue) <x-ui.badge tone="warning" class="ms-1">{{ __('opendata.overdue') }}</x-ui.badge>@endif</dd>
                        </div>
                    @endif
                    @if ($dataset->temporal_from || $dataset->temporal_to)
                        <div>
                            <dt class="kicker">{{ __('opendata.period') }}</dt>
                            <dd class="mt-1">
                                @if ($dataset->temporal_from)<x-date :date="$dataset->temporal_from" />@endif
                                –
                                @if ($dataset->temporal_to)<x-date :date="$dataset->temporal_to" />@endif
                            </dd>
                        </div>
                    @endif
                    @if (filled($translation->coverage))
                        <div>
                            <dt class="kicker">{{ __('opendata.field.coverage') }}</dt>
                            <dd class="mt-1">{{ $translation->coverage }}</dd>
                        </div>
                    @endif
                    @if ($dataset->pillar)
                        <div>
                            <dt class="kicker">{{ __('projects.pillar') }}</dt>
                            <dd class="mt-1">{{ $dataset->pillar->name() }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="kicker">{{ __('opendata.field.downloads') }}</dt>
                        <dd class="mt-1 tabular-nums" dir="ltr">{{ $count($dataset->download_count) }}</dd>
                    </div>
                    @if ($dataset->contact_email)
                        <div>
                            <dt class="kicker">{{ __('opendata.field.contact_email') }}</dt>
                            <dd class="mt-1"><a href="mailto:{{ $dataset->contact_email }}" class="text-link underline-offset-4 hover:underline" dir="ltr">{{ $dataset->contact_email }}</a></dd>
                        </div>
                    @endif
                </dl>

                @if ($dataset->keywords)
                    <section aria-labelledby="data-keywords">
                        <h2 id="data-keywords" class="kicker">{{ __('opendata.field.keywords') }}</h2>
                        <ul class="mt-2 flex flex-wrap gap-1.5">
                            @foreach ($dataset->keywords as $keyword)
                                <li><a href="{{ route('datasets.index', ['q' => $keyword]) }}" class="inline-flex rounded-md bg-surface-muted px-2 py-0.5 text-sm ring-1 ring-line ring-inset hover:ring-cyan-500">{{ $keyword }}</a></li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                <section aria-labelledby="data-api" class="rounded-card border border-night-line bg-night-900 p-5 text-night-ink">
                    <h2 id="data-api" class="kicker [--color-eyebrow:var(--color-cyan-300)]">{{ __('opendata.api') }}</h2>
                    <p class="mt-2 text-sm text-night-muted">{{ __('opendata.api_lead') }}</p>
                    <p class="mt-3 font-mono text-xs break-all text-cyan-200" dir="ltr">{{ 'GET '.$apiUrl }}</p>
                </section>
            </aside>
        </div>
    </article>
</x-layouts.public>
