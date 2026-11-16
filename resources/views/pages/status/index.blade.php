@use('App\Domain\Status\Enums\ComponentState')
@php
    $locale = app()->getLocale();
    $calendar = app(\App\Services\Calendar\EthiopianCalendar::class);
    $components = $board->components();
    $active = $board->active();
    $upcoming = $board->upcoming();
    $history = $board->history();
@endphp
<x-layouts.public :title="__('status.title')" :description="__('status.description')">
    {{-- ADR-0018: the overall state first, in words and colour; then each system, what is happening now, what is
         planned and the last thirty days. --}}
    <x-page-hero :title="__('status.title')" :lead="__('status.description')" :kicker="__('support.public.kicker')" tone="night"
                 :crumbs="Route::has('services.index') ? [[__('services.title'), route('services.index')]] : []">
        <div @class([
            'flex flex-wrap items-center gap-4 rounded-card border px-5 py-4',
            'border-success-600/50 bg-success-600/10' => $overall === ComponentState::Operational,
            'border-cyan-400/50 bg-cyan-400/10' => $overall === ComponentState::Maintenance,
            'border-saffron-400/60 bg-saffron-400/10' => $overall === ComponentState::Degraded,
            'border-ruby-500/60 bg-ruby-500/15' => in_array($overall, [ComponentState::PartialOutage, ComponentState::MajorOutage], true),
        ]) role="status">
            <x-ui.status-pill :state="$overall->pill()" class="border-night-line bg-night-950 text-night-ink">{{ $overall->label() }}</x-ui.status-pill>
            <p class="font-display text-xl font-semibold tracking-tight text-night-ink">{{ __("status.overall.{$overall->value}") }}</p>
        </div>
    </x-page-hero>

    <div class="mx-auto max-w-page px-4 pt-10 pb-16 sm:px-6">
        <div class="grid gap-12 lg:grid-cols-[1fr_22rem]">
            <div class="min-w-0 space-y-12">
                @if ($active->isNotEmpty())
                    <section aria-labelledby="status-active">
                        <h2 id="status-active" class="heading-2">{{ __('status.active') }}</h2>
                        <div class="mt-5 space-y-4">
                            @foreach ($active as $incident)
                                @include('pages.status._incident', ['incident' => $incident])
                            @endforeach
                        </div>
                    </section>
                @endif

                <section aria-labelledby="status-systems">
                    <h2 id="status-systems" class="heading-2">{{ __('status.systems') }}</h2>
                    @if ($components === [])
                        <x-ui.empty-state class="mt-5">{{ __('status.no_systems') }}</x-ui.empty-state>
                    @else
                        <ul class="mt-5 divide-y divide-line rounded-card border border-line bg-surface">
                            @foreach ($components as $row)
                                <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                                    <span class="font-medium">{{ $row['component']->name($locale) }}</span>
                                    <x-ui.status-pill :state="$row['state']->pill()">{{ $row['state']->label() }}</x-ui.status-pill>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                @if ($upcoming->isNotEmpty())
                    <section aria-labelledby="status-upcoming">
                        <h2 id="status-upcoming" class="heading-2">{{ __('status.upcoming') }}</h2>
                        <div class="mt-5 space-y-4">
                            @foreach ($upcoming as $incident)
                                @include('pages.status._incident', ['incident' => $incident])
                            @endforeach
                        </div>
                    </section>
                @endif

                <section aria-labelledby="status-history">
                    <h2 id="status-history" class="heading-2">{{ __('status.history', ['days' => \App\Domain\Status\Support\StatusBoard::HISTORY_DAYS]) }}</h2>
                    @if ($history->isEmpty())
                        <p class="mt-4 text-ink-muted">{{ __('status.no_history') }}</p>
                    @else
                        <div class="mt-5 space-y-4">
                            @foreach ($history as $incident)
                                @include('pages.status._incident', ['incident' => $incident])
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>

            <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start" aria-label="{{ __('status.follow') }}">
                <section class="relative isolate overflow-hidden rounded-card border border-night-line bg-night-900 p-6 text-night-ink">
                    <x-circuit class="absolute inset-x-0 bottom-0 -z-10 h-24 w-full text-cyan-500/25" />
                    <h2 class="kicker [--color-eyebrow:var(--color-cyan-300)]">{{ __('status.follow') }}</h2>
                    <p class="mt-2 text-sm text-night-muted">{{ __('status.follow_text') }}</p>
                    <ul class="mt-4 flex flex-wrap gap-2 pb-12 font-mono text-xs">
                        <li><a href="{{ route('status.rss') }}" class="inline-flex rounded-control border border-night-line px-3 py-1.5 uppercase hover:border-cyan-400">{{ __('status.feed_rss') }}</a></li>
                        <li><a href="{{ route('status.json') }}" class="inline-flex rounded-control border border-night-line px-3 py-1.5 uppercase hover:border-cyan-400">{{ __('status.feed_json') }}</a></li>
                    </ul>
                </section>
                @if (Route::has('support.create'))
                    <section class="rounded-card border border-line bg-surface p-5">
                        <h2 class="font-display text-lg font-semibold tracking-tight">{{ __('status.report_title') }}</h2>
                        <p class="mt-1 text-sm text-ink-muted">{{ __('status.report_text') }}</p>
                        <p class="mt-4"><x-ui.button :href="route('support.create')" variant="action" size="sm">{{ __('nav.cta_support') }}</x-ui.button></p>
                    </section>
                @endif
            </aside>
        </div>
    </div>
</x-layouts.public>
