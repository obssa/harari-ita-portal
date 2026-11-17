@use('App\Domain\Organisation\Queries\PublicDirectory')
@php $locale = app()->getLocale(); @endphp
<x-layouts.public :title="__('innovation.challenges.title')" :description="__('innovation.challenges.description')">
    {{-- ADR-0022: challenges taking teams or being built now, soonest deadline first; then judged and past ones. --}}
    <x-page-hero :title="__('innovation.challenges.title')" :lead="__('innovation.challenges.description')" :kicker="__('nav.innovation')" tone="night" :crumbs="[]">
        <x-slot:actions>
            <x-ui.button :href="route('teams.lookup')" variant="secondary">{{ __('innovation.track.team_title') }}</x-ui.button>
        </x-slot:actions>
    </x-page-hero>
    <div class="mx-auto max-w-page px-4 pt-2 pb-16 sm:px-6">
        @if ($live->isEmpty() && $past->isEmpty())
            <x-ui.empty-state class="mt-10">{{ __('innovation.challenges.empty') }}</x-ui.empty-state>
        @endif
        @if ($live->isNotEmpty())
            <section aria-labelledby="challenges-live" class="mt-10">
                <h2 id="challenges-live" class="heading-2">{{ __('innovation.challenges.live') }}</h2>
                <ul class="mt-6 grid gap-4 sm:grid-cols-2">
                    @foreach ($live as $challenge)
                        @php $shown = PublicDirectory::shown($challenge, $locale); @endphp
                        @continue($shown === null)
                        <li><x-challenge-card :challenge="$challenge" :translation="$shown" :heading-level="3" /></li>
                    @endforeach
                </ul>
            </section>
        @endif
        @if ($past->isNotEmpty())
            <section aria-labelledby="challenges-past" class="mt-14">
                <h2 id="challenges-past" class="heading-2">{{ __('innovation.challenges.past') }}</h2>
                <ul class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($past as $challenge)
                        @php $shown = PublicDirectory::shown($challenge, $locale); @endphp
                        @continue($shown === null)
                        <li><x-challenge-card :challenge="$challenge" :translation="$shown" :heading-level="3" /></li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-layouts.public>
