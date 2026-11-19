@use('App\Domain\Innovation\Models\Challenge')
@php
    $order = [Challenge::PHASE_UPCOMING, Challenge::PHASE_REGISTRATION, Challenge::PHASE_BUILDING, Challenge::PHASE_JUDGING, Challenge::PHASE_RESULTS];
    $current = array_search($phase, $order, true);
    $timeline = [
        [__('innovation.phase.registration'), $challenge->registration_opens_at, true],
        [__('innovation.phase.building'), $challenge->registration_closes_at, true],
        [__('innovation.phase.judging'), $challenge->submission_closes_at, true],
        [__('innovation.phase.results'), $challenge->results_published_at ?? $challenge->results_on, $challenge->results_published_at !== null],
    ];
    $countdown = match ($phase) {
        Challenge::PHASE_UPCOMING => [__('innovation.challenges.opens_in'), $challenge->registration_opens_at],
        Challenge::PHASE_REGISTRATION => [__('innovation.challenges.registration_closes_in'), $challenge->registration_closes_at],
        Challenge::PHASE_BUILDING => [__('innovation.challenges.submissions_close_in'), $challenge->submission_closes_at],
        default => null,
    };
    $weights = array_sum(array_column($criteria, 'weight'));
@endphp
<x-layouts.public :title="$translation->title" :description="$translation->summary" :alternates="$alternates" :json-ld="$jsonLd">
    {{-- ADR-0022: a challenge: the brief, the phases on one line, the prizes and rules, how it is judged and, once
         published, the results. The countdown aims at the next date. --}}
    <x-page-hero :title="$translation->title" :lead="$translation->summary" :kicker="__('innovation.phase.'.$phase)" tone="night"
                 :crumbs="[[__('innovation.challenges.title'), route('challenges.index')]]" :heading-lang="$translation->locale">
        @if ($phase === Challenge::PHASE_REGISTRATION)
            <x-slot:actions>
                <x-ui.button :href="route('teams.create', ['slug' => $translation->slug])" variant="action" size="lg">{{ __('innovation.challenges.register') }}</x-ui.button>
            </x-slot:actions>
        @elseif ($phase === Challenge::PHASE_BUILDING)
            {{-- ADR-0053: while teams build, the page leads them to send their work. --}}
            <x-slot:actions>
                <x-ui.button :href="route('teams.lookup')" variant="action" size="lg">{{ __('innovation.challenges.send_submission') }}</x-ui.button>
            </x-slot:actions>
        @endif
    </x-page-hero>

    <div class="mx-auto max-w-page px-4 pt-10 pb-16 sm:px-6">
        <ol class="grid gap-3 sm:grid-cols-4" aria-label="{{ __('innovation.challenges.phases') }}">
            @foreach ($timeline as $i => [$label, $date, $known])
                @php $step = $i + 1; @endphp
                <li @if ($step === $current) aria-current="step" @endif @class([
                    'rounded-card border p-4',
                    'border-cyan-600 bg-surface ring-2 ring-cyan-500/30' => $step === $current,
                    'border-line bg-surface' => $step !== $current,
                ])>
                    <p class="font-mono text-xs text-ink-muted">{{ sprintf('%02d', $step) }}@if ($step < $current) · {{ __('ui.step_done') }}@endif</p>
                    <p class="mt-1 font-display text-lg font-semibold tracking-tight">{{ $label }}</p>
                    <p class="mt-1 text-sm text-ink-muted">
                        @if ($date)
                            @unless ($known)<span>{{ __('innovation.challenges.expected') }}</span> @endunless<x-date :date="$date" :time="$known" />
                        @else
                            {{ __('innovation.challenges.date_tba') }}
                        @endif
                    </p>
                </li>
            @endforeach
        </ol>

        <div class="mt-12 grid gap-12 lg:grid-cols-[1fr_22rem]">
            <div class="min-w-0 space-y-12" lang="{{ $translation->locale }}">
                @if ($results !== [])
                    <section aria-labelledby="challenge-results">
                        <h2 id="challenge-results" class="heading-2">{{ __('innovation.challenges.results') }}</h2>
                        <p class="mt-2 text-ink-muted">{{ __('innovation.challenges.results_help') }}</p>
                        <table class="mt-5 w-full text-start">
                            <thead class="border-b border-line font-mono text-xs tracking-wider text-ink-muted uppercase">
                                <tr><th scope="col" class="py-2 pe-4 text-start">{{ __('innovation.field.rank') }}</th><th scope="col" class="py-2 pe-4 text-start">{{ __('innovation.field.team_name') }}</th><th scope="col" class="py-2 pe-4 text-start">{{ __('innovation.field.award') }}</th><th scope="col" class="py-2 text-end">{{ __('innovation.field.score') }}</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($results as $row)
                                    <tr class="border-b border-line">
                                        <td class="py-3 pe-4 font-display text-xl font-bold tabular-nums">{{ $row['rank'] }}</td>
                                        <td class="py-3 pe-4">{{ $row['name'] ?? __('innovation.challenges.team_unnamed') }}</td>
                                        <td class="py-3 pe-4">@if ($row['award'])<x-ui.badge tone="saffron">{{ $row['award'] }}</x-ui.badge>@endif</td>
                                        <td class="py-3 text-end font-mono tabular-nums">{{ $row['score'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </section>
                @endif
                @if (trim($body) !== '')
                    <section aria-labelledby="challenge-brief">
                        <h2 id="challenge-brief" class="heading-2">{{ __('innovation.field.brief') }}</h2>
                        <div class="prose-content mt-5 max-w-prose">{!! $body !!}</div>
                    </section>
                @endif
                @if ($translation->prizeList() !== [])
                    <section aria-labelledby="challenge-prizes">
                        <h2 id="challenge-prizes" class="heading-2">{{ __('innovation.field.prizes') }}</h2>
                        <ol class="mt-5 grid gap-2 sm:grid-cols-2">
                            @foreach ($translation->prizeList() as $prize)
                                <li class="flex items-start gap-3 rounded-control border border-line bg-surface px-4 py-3">
                                    <span aria-hidden="true" class="font-mono text-sm text-saffron-700 dark:text-saffron-300">{{ sprintf('%02d', $loop->iteration) }}</span>
                                    <span>{{ $prize }}</span>
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif
                @if ($criteria !== [])
                    <section aria-labelledby="challenge-judging">
                        <h2 id="challenge-judging" class="heading-2">{{ __('innovation.challenges.judging') }}</h2>
                        <p class="mt-2 max-w-prose text-ink-muted">{{ __('innovation.challenges.judging_help') }}</p>
                        <table class="mt-5 w-full max-w-xl text-start">
                            <caption class="sr-only">{{ __('innovation.challenges.judging') }}</caption>
                            <thead class="border-b border-line font-mono text-xs tracking-wider text-ink-muted uppercase">
                                <tr><th scope="col" class="py-2 pe-4 text-start">{{ __('innovation.challenges.criterion') }}</th><th scope="col" class="py-2 text-end">{{ __('innovation.field.weight') }}</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($criteria as $criterion)
                                    @php $share = $weights > 0 ? (int) round($criterion['weight'] * 100 / $weights) : 0; @endphp
                                    <tr class="border-b border-line">
                                        <td class="py-3 pe-4">
                                            {{ $criterion['label'] }}
                                            <span aria-hidden="true" class="mt-2 block h-1.5 rounded-full bg-line"><span class="block h-full rounded-full bg-cyan-600 dark:bg-cyan-400" style="width: {{ $share }}%"></span></span>
                                        </td>
                                        <td class="py-3 text-end font-mono tabular-nums">{{ $share }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </section>
                @endif
                @if (filled($translation->rules))
                    <section aria-labelledby="challenge-rules">
                        <h2 id="challenge-rules" class="heading-2">{{ __('innovation.field.rules') }}</h2>
                        <p class="mt-4 max-w-prose leading-relaxed whitespace-pre-line">{{ $translation->rules }}</p>
                    </section>
                @endif
            </div>

            <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start" aria-label="{{ __('innovation.challenges.take_part') }}">
                <section class="relative isolate overflow-hidden rounded-card border border-night-line bg-night-900 p-6 text-night-ink">
                    <x-hex-field id="challenge-hex" fade="top" class="absolute inset-x-0 top-0 -z-10 h-32 w-full text-cyan-500/20" />
                    @if ($countdown)
                        <x-countdown :until="$countdown[1]" :label="$countdown[0]" tone="night" />
                    @else
                        <h2 class="kicker [--color-eyebrow:var(--color-cyan-300)]">{{ __('innovation.phase.'.$phase) }}</h2>
                        <p class="mt-2 text-night-muted">{{ $phase === Challenge::PHASE_RESULTS ? __('innovation.challenges.results_out') : __('innovation.challenges.judging_now') }}</p>
                    @endif
                    <dl class="mt-5 grid grid-cols-2 gap-3 border-t border-night-line pt-4 text-sm">
                        <div><dt class="font-mono text-[11px] tracking-wider text-night-muted uppercase">{{ __('innovation.field.max_team_size') }}</dt><dd class="mt-1 text-base tabular-nums">{{ $challenge->max_team_size }}</dd></div>
                        <div><dt class="font-mono text-[11px] tracking-wider text-night-muted uppercase">{{ __('innovation.admin.teams') }}</dt><dd class="mt-1 text-base tabular-nums">{{ $teams }}</dd></div>
                    </dl>
                    @if ($phase === Challenge::PHASE_REGISTRATION)
                        <p class="mt-6"><x-ui.button :href="route('teams.create', ['slug' => $translation->slug])" variant="action" class="w-full">{{ __('innovation.challenges.register') }}</x-ui.button></p>
                    @elseif ($phase === Challenge::PHASE_BUILDING)
                        <p class="mt-6"><x-ui.button :href="route('teams.lookup')" variant="action" class="w-full">{{ __('innovation.challenges.send_submission') }}</x-ui.button></p>
                    @endif
                </section>
                <p class="text-sm"><a href="{{ route('teams.lookup') }}" class="text-link underline underline-offset-4">{{ __('innovation.track.team_title') }}</a></p>
                <section class="rounded-card border border-line bg-surface p-5 text-sm">
                    <h2 class="font-semibold">{{ __('involved.volunteer.title') }}</h2>
                    <p class="mt-1 text-ink-muted">{{ __('innovation.mentor_text') }}</p>
                    <p class="mt-3"><x-ui.button :href="route('volunteer.create')" variant="secondary" size="sm">{{ __('volunteers.form.link') }}</x-ui.button></p>
                </section>
            </aside>
        </div>
    </div>
    @if ($countdown)
        @vite('resources/js/countdown.js')
    @endif
</x-layouts.public>
