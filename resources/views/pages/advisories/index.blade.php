@use('App\Domain\Content\Enums\AdvisorySeverity')
@use('App\Domain\Content\Queries\PublicPosts')
@php $locale = app()->getLocale(); @endphp
<x-layouts.public :title="__('advisories.title')" :description="__('advisories.description')">
    {{-- ADR-0034: dated safety and awareness guidance, newest first, filtered by severity. --}}
    <x-page-hero :title="__('advisories.title')" :lead="__('advisories.description')" :kicker="__('advisories.kicker')" tone="plaster"
                 :crumbs="[[__('news.title'), route('news.index')]]">
        <x-slot:actions>
            <x-ui.button :href="route('advisories.feed')" variant="secondary" size="sm">{{ __('advisories.feed') }}</x-ui.button>
            <x-ui.button :href="route('newsletter.create')" variant="secondary" size="sm">{{ __('advisories.subscribe') }}</x-ui.button>
        </x-slot:actions>
        <nav aria-label="{{ __('advisories.filter_by_severity') }}">
            <ul class="flex flex-wrap gap-2">
                <li><x-ui.chip :href="route('advisories.index')" :active="! $severity">{{ __('advisories.all') }}</x-ui.chip></li>
                @foreach (AdvisorySeverity::cases() as $option)
                    <li><x-ui.chip :href="route('advisories.index', ['severity' => $option->value])" :active="$severity === $option">{{ $option->label() }}</x-ui.chip></li>
                @endforeach
            </ul>
        </nav>
    </x-page-hero>

    <div class="mx-auto max-w-page px-4 pt-2 pb-16 sm:px-6">
        <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_20rem]">
            <div class="min-w-0">
                @if ($posts->isEmpty())
                    <x-ui.empty-state>{{ __('advisories.empty') }}</x-ui.empty-state>
                @else
                    <ul class="grid gap-6 sm:grid-cols-2">
                        @foreach ($posts as $post)
                            @php $shown = PublicPosts::shown($post, $locale); @endphp
                            @continue($shown === null)
                            <li><x-news-card :post="$post" :shown="$shown" :heading-level="2" /></li>
                        @endforeach
                    </ul>
                    <div class="mt-10">{{ $posts->links() }}</div>
                @endif
            </div>
            <aside class="space-y-4">
                <x-advisory-report />
                <section aria-labelledby="advisory-severities" class="rounded-card border border-line bg-surface p-5 text-sm">
                    <h2 id="advisory-severities" class="font-semibold">{{ __('advisories.severity.title') }}</h2>
                    <dl class="mt-3 space-y-3">
                        @foreach (AdvisorySeverity::cases() as $option)
                            <div>
                                <dt><x-ui.badge :tone="$option->tone()">{{ $option->label() }}</x-ui.badge></dt>
                                <dd class="mt-1 text-ink-muted">{{ __('advisories.severity.'.$option->value.'_help') }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
            </aside>
        </div>
    </div>
</x-layouts.public>
