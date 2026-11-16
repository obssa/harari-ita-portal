{{-- ADR-0011: the KPI strip: figures in the display face on night, mono labels, hairlines between them.
     The figures are what editors enter with their source (PLAN.md: never hard-coded). --}}
<section aria-labelledby="home-counters" class="relative isolate overflow-hidden bg-night-800 text-night-ink">
    <x-hex-field id="kpi-hex" fade="left" class="absolute inset-y-0 start-0 -z-10 h-full w-1/2 text-cyan-500/15" />
    <div class="mx-auto max-w-page px-4 py-16 sm:px-6">
        <p class="kicker [--color-eyebrow:var(--color-cyan-300)]">{{ __('homepage.section_kicker.counters') }}</p>
        <h2 id="home-counters" class="heading-2 mt-2 text-night-ink">{{ $section->heading() }}</h2>
        <dl class="mt-10 grid grid-cols-2 border-t border-night-line lg:grid-cols-4">
            @foreach ($data as $item)
                <div class="reveal flex flex-col-reverse gap-2 border-b border-night-line py-6 pe-4 lg:border-b-0 lg:border-e lg:px-6 lg:first:ps-0 lg:last:border-e-0">
                    <dt class="font-mono text-xs tracking-wide text-night-muted uppercase">{{ $item['label'] }}</dt>
                    <dd class="font-display text-4xl font-semibold tracking-tight text-saffron-400 tabular-nums sm:text-5xl" dir="ltr">{{ $item['value'] }}</dd>
                </div>
            @endforeach
        </dl>
    </div>
</section>
