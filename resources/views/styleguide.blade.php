{{--
    Component styleguide (PLAN.md §12). Developer-facing and never shown in
    production, so its labels are English (exempt from the hard-coded string
    check). ?lang=am|om|har shows the components with that language's strings
    and typography.
--}}
@use('App\Domain\Translation\Support\Locales')
@php
    $lang = Locales::isSupported(request()->query('lang')) ? (string) request()->query('lang') : 'en';
    app()->setLocale($lang);
    \Illuminate\Support\Facades\URL::defaults(['locale' => $lang]);

    $alternates = [];
    foreach (Locales::all() as $l) {
        $alternates[$l] = url('/styleguide?lang='.$l);
    }

    $scales = ['primary', 'saffron', 'cyan', 'ruby'];
    $steps = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950];
@endphp
<x-layouts.public title="Styleguide" :alternates="$alternates">
    <div class="mx-auto max-w-page space-y-14 px-4 py-12 sm:px-6">
        <header>
            <h1 class="heading-1">Styleguide</h1>
            <p class="mt-2 text-ink-muted">Design tokens and components of the public site (resources/css/tokens.css).</p>
        </header>

        <section aria-labelledby="sg-colours">
            <h2 id="sg-colours" class="heading-2">Colours</h2>
            @foreach ($scales as $scale)
                <h3 class="heading-3 mt-6 capitalize">{{ $scale }}</h3>
                <ul class="mt-3 grid grid-cols-4 gap-2 sm:grid-cols-6 lg:grid-cols-11">
                    @foreach ($steps as $step)
                        <li class="overflow-hidden rounded-control border border-line text-xs">
                            <span class="block h-12" style="background: var(--color-{{ $scale }}-{{ $step }})"></span>
                            <span class="block px-2 py-1">{{ $step }}</span>
                        </li>
                    @endforeach
                </ul>
            @endforeach
            <h3 class="heading-3 mt-6">Surfaces</h3>
            <ul class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-7">
                @foreach (['canvas', 'surface', 'surface-muted', 'line', 'ink', 'ink-muted', 'link'] as $token)
                    <li class="overflow-hidden rounded-control border border-line text-xs">
                        <span class="block h-12" style="background: var(--color-{{ $token }})"></span>
                        <span class="block px-2 py-1">{{ $token }}</span>
                    </li>
                @endforeach
            </ul>
        </section>

        <section aria-labelledby="sg-type">
            <h2 id="sg-type" class="heading-2">Typography</h2>
            <div class="mt-4 space-y-3">
                <p class="heading-1">{{ __('site.name') }}</p>
                <p class="heading-2">{{ __('holding.title') }}</p>
                <p class="heading-3">{{ __('site.office') }}</p>
                <p class="max-w-prose">{{ __('holding.body') }}</p>
                <p lang="am" class="max-w-prose">የሐረሪ ክልል ቅርንጫፍ ጽሕፈት ቤት — ሀ ለ ሐ መ ሠ ረ ሰ ሸ ቀ በ ተ ቸ ኀ ነ ኘ አ ከ ኸ ወ ዐ ዘ ዠ የ ደ ጀ ገ ጠ ጨ ጰ ጸ ፀ ፈ ፐ ። ፣ ፤ ፩ ፪ ፫</p>
                <p class="text-sm text-ink-muted">Ethiopic (lang="am"/"har") uses line-height 1.7 and 92% headline size.</p>
            </div>
        </section>

        <section aria-labelledby="sg-buttons">
            <h2 id="sg-buttons" class="heading-2">Buttons and badges</h2>
            <div class="mt-4 flex flex-wrap items-center gap-3">
                <x-ui.button>Primary</x-ui.button>
                <x-ui.button variant="accent">Accent</x-ui.button>
                <x-ui.button variant="secondary">Secondary</x-ui.button>
                <x-ui.button variant="ghost">Ghost</x-ui.button>
                <x-ui.button variant="action">Action</x-ui.button>
                <x-ui.button size="sm">Small</x-ui.button>
                <x-ui.button size="lg" href="#sg-buttons">Large link</x-ui.button>
                <x-ui.button disabled>Disabled</x-ui.button>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach (['primary', 'cyan', 'saffron', 'ruby', 'ink', 'neutral', 'success', 'warning', 'danger'] as $tone)
                    <x-ui.badge :tone="$tone">{{ ucfirst($tone) }}</x-ui.badge>
                @endforeach
            </div>
        </section>

        <section aria-labelledby="sg-cards">
            <h2 id="sg-cards" class="heading-2">Cards and dates</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-ui.card title="Sample news item (placeholder)" kicker="News" href="#sg-cards">
                    <x-slot:badges><x-ui.badge tone="primary">News</x-ui.badge><x-ui.badge>Harar</x-ui.badge></x-slot:badges>
                    Cards show a content-type badge and the office or pillar they belong to.
                    <x-slot:meta><x-date :date="now()" /></x-slot:meta>
                </x-ui.card>
                <x-ui.card title="Card without a link" kicker="Event">
                    Used where a card has no detail page.
                </x-ui.card>
                <div class="flex flex-wrap items-start gap-3">
                    <x-ui.date-tile :date="now()" />
                    <x-ui.date-tile :date="now()->setDate(2026, 9, 11)" />
                    <x-ui.date-tile :date="now()->setDate(2027, 9, 11)" />
                </div>
            </div>
        </section>

        <section aria-labelledby="sg-chips">
            <h2 id="sg-chips" class="heading-2">Chips and steps</h2>
            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.chip href="#sg-chips" :active="true">All</x-ui.chip>
                <x-ui.chip href="#sg-chips">News</x-ui.chip>
                <x-ui.chip href="#sg-chips">Statement</x-ui.chip>
            </div>
            <x-ui.stepper class="mt-6" :steps="['Details', 'Documents', 'Review', 'Submitted']" :current="2" />
        </section>

        <section aria-labelledby="sg-feedback">
            <h2 id="sg-feedback" class="heading-2">Feedback and status</h2>
            <div class="mt-4 grid gap-3 lg:grid-cols-2">
                <x-ui.alert>An information note.</x-ui.alert>
                <x-ui.alert tone="success" title="Saved">Your request was received.</x-ui.alert>
                <x-ui.alert tone="warning">The service is slow right now.</x-ui.alert>
                <x-ui.alert tone="danger" title="Not sent">Check the highlighted fields.</x-ui.alert>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.status-pill>Operational</x-ui.status-pill>
                <x-ui.status-pill state="degraded">Degraded</x-ui.status-pill>
                <x-ui.status-pill state="down">Outage</x-ui.status-pill>
                <x-ui.status-pill state="maintenance">Maintenance</x-ui.status-pill>
            </div>
            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <x-ui.empty-state title="Nothing here yet">Sample empty state with a next step.
                    <x-slot:action><x-ui.button variant="secondary" size="sm" href="#sg-feedback">Go back</x-ui.button></x-slot:action>
                </x-ui.empty-state>
                <x-ui.skeleton />
            </div>
        </section>

        <section aria-labelledby="sg-motifs">
            <h2 id="sg-motifs" class="heading-2">Motifs</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="relative h-48 overflow-hidden rounded-card bg-night-900"><x-circuit :draw="true" class="absolute inset-0 size-full text-cyan-500/50" /></div>
                <div class="relative h-48 overflow-hidden rounded-card bg-night-900"><x-hex-field id="sg-hex" class="absolute inset-0 size-full text-cyan-500/30" /></div>
            </div>
        </section>

        <section aria-labelledby="sg-data">
            <h2 id="sg-data" class="heading-2">Figures and code</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <x-ui.kpi label="Sample figure" value="1,234" source="Source: sample data" />
                <x-ui.kpi label="Sample share" value="56%" source="Source: sample data" />
                <x-ui.kpi label="Sample count" value="78" />
            </div>
            <x-ui.code class="mt-4" label="GET /api/v1/news">curl -s https://example.org/api/v1/news?lang=en</x-ui.code>
        </section>
    </div>
</x-layouts.public>
