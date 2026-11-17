@use('App\Domain\Innovation\Models\Challenge')
@use('App\Domain\Innovation\Actions\ChallengeEntry')
@php
    $field = 'mt-1 block w-full rounded-control border border-line bg-surface px-3 py-2 focus:border-cyan-600 focus:outline-none focus:ring-2 focus:ring-cyan-500/30';
    $open = $challenge->takesSubmissions();
    $links = old('submission_links', $team->submission_links ?? []);
    $own = $challenge->translations->first(fn ($t) => $t->locale === app()->getLocale() && $t->isLive());
    $links = array_pad(array_slice((array) $links, 0, ChallengeEntry::MAX_LINKS), 3, '');
@endphp
<x-layouts.public :title="$team->reference" :description="__('innovation.track.team_description')">
    {{-- ADR-0022: the team's own page: its one submission, which it may change until submissions close, and its
         place once the results are published. --}}
    <x-page-hero :title="$team->name" :lead="$challenge->title()" :kicker="$team->reference" tone="night"
                 :crumbs="[[__('innovation.challenges.title'), route('challenges.index')]]" />
    <div class="mx-auto max-w-page px-4 pt-10 pb-16 sm:px-6">
        @if (session('status'))
            <x-ui.alert tone="success" class="mb-8 max-w-3xl">{{ session('status') }}</x-ui.alert>
        @endif
        <div class="grid max-w-5xl gap-10 lg:grid-cols-[1fr_18rem]">
            <section aria-labelledby="team-submission" class="min-w-0">
                <h2 id="team-submission" class="heading-2">{{ __('innovation.admin.submission') }}</h2>
                @if ($open)
                    <p class="mt-2 text-ink-muted">{{ __('innovation.public.submission_help') }} <x-date :date="$challenge->submission_closes_at" :time="true" /></p>
                    @if ($errors->any())
                        <x-ui.alert tone="danger" class="mt-6">{{ __('support.public.check') }}</x-ui.alert>
                    @endif
                    <form method="post" action="{{ route('teams.submit', ['reference' => $team->reference]) }}" class="mt-6 space-y-5" novalidate>
                        @csrf
                        <div>
                            <label for="submission_title" class="font-medium">{{ __('innovation.field.submission_title') }}</label>
                            <input id="submission_title" name="submission_title" type="text" value="{{ old('submission_title', $team->submission_title) }}" required maxlength="200"
                                   @error('submission_title') aria-invalid="true" aria-describedby="submission_title-error" @enderror class="{{ $field }}">
                            @error('submission_title')<p id="submission_title-error" class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="submission_description" class="font-medium">{{ __('innovation.field.submission_description') }}</label>
                            <textarea id="submission_description" name="submission_description" rows="8" required maxlength="5000" aria-describedby="submission_description-help @error('submission_description') submission_description-error @enderror"
                                      @error('submission_description') aria-invalid="true" @enderror class="{{ $field }}">{{ old('submission_description', $team->submission_description) }}</textarea>
                            <p id="submission_description-help" class="mt-1 text-sm text-ink-muted">{{ __('innovation.public.description_help') }}</p>
                            @error('submission_description')<p id="submission_description-error" class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                        </div>
                        <fieldset>
                            <legend class="font-medium">{{ __('innovation.field.submission_links') }} <span class="text-sm font-normal text-ink-muted">({{ __('innovation.public.optional') }})</span></legend>
                            <p id="links-help" class="mt-1 text-sm text-ink-muted">{{ __('innovation.public.links_help') }}</p>
                            @foreach ($links as $i => $link)
                                <label for="link-{{ $i }}" class="sr-only">{{ __('innovation.public.link_n', ['n' => $i + 1]) }}</label>
                                <input id="link-{{ $i }}" name="submission_links[]" type="url" value="{{ $link }}" maxlength="500" dir="ltr" aria-describedby="links-help"
                                       @error("submission_links.{$i}") aria-invalid="true" @enderror class="{{ $field }} mt-2">
                                @error("submission_links.{$i}")<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                            @endforeach
                        </fieldset>
                        @error('form')<p class="text-sm text-danger-600">{{ $message }}</p>@enderror
                        <x-ui.button type="submit" variant="action">{{ $team->submitted_at ? __('innovation.public.update_submission') : __('innovation.public.send_submission') }}</x-ui.button>
                    </form>
                @elseif ($team->submitted_at)
                    <article class="mt-5 rounded-card border border-line bg-surface p-6">
                        <h3 class="font-display text-xl font-semibold tracking-tight">{{ $team->submission_title }}</h3>
                        <p class="mt-3 leading-relaxed whitespace-pre-line">{{ $team->submission_description }}</p>
                        @if ($team->submission_links)
                            <ul class="mt-4 space-y-1 text-sm">
                                @foreach ($team->submission_links as $link)
                                    <li><a href="{{ $link }}" rel="noopener nofollow" class="text-link break-all underline underline-offset-4" dir="ltr">{{ $link }}</a></li>
                                @endforeach
                            </ul>
                        @endif
                    </article>
                @else
                    <x-ui.empty-state class="mt-5">{{ __('innovation.validation.submissions_closed') }}</x-ui.empty-state>
                @endif
            </section>
            <aside class="space-y-4" aria-label="{{ __('innovation.admin.result') }}">
                <div class="rounded-card border border-line bg-surface p-5">
                    <p class="kicker">{{ __('innovation.field.phase') }}</p>
                    <p class="mt-2 font-display text-lg font-semibold">{{ __('innovation.phase.'.$phase) }}</p>
                    <p class="mt-3">
                        @if ($team->submitted_at)
                            <x-ui.badge tone="success">{{ __('innovation.public.submitted') }}</x-ui.badge>
                        @else
                            <x-ui.badge tone="warning">{{ __('innovation.admin.not_submitted') }}</x-ui.badge>
                        @endif
                    </p>
                    @if ($team->submitted_at)
                        <p class="mt-3 font-mono text-xs text-ink-muted">{{ __('innovation.field.submitted_at') }}: <x-date :date="$team->submitted_at" :time="true" /></p>
                    @endif
                </div>
                @if ($phase === Challenge::PHASE_RESULTS)
                    <div class="rounded-card border border-line bg-surface p-5">
                        <p class="kicker">{{ __('innovation.challenges.results') }}</p>
                        @if ($team->rank)
                            <p class="mt-2 font-display text-4xl font-bold tabular-nums">{{ $team->rank }}</p>
                            <p class="text-sm text-ink-muted">{{ __('innovation.field.rank') }} · {{ __('innovation.field.score') }} {{ $team->score }}</p>
                            @if ($team->award)<p class="mt-3"><x-ui.badge tone="saffron">{{ $team->award }}</x-ui.badge></p>@endif
                        @else
                            <p class="mt-2 text-ink-muted">{{ __('innovation.public.not_ranked') }}</p>
                        @endif
                    </div>
                @endif
                <p class="text-sm"><a href="{{ $own ? route('challenges.show', ['slug' => $own->slug]) : route('challenges.index') }}" class="text-link underline underline-offset-4">{{ __('innovation.public.back_to_challenge') }}</a></p>
            </aside>
        </div>
    </div>
</x-layouts.public>
