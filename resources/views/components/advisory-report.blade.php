{{-- ADR-0034: where to report. National cybersecurity incidents belong to INSA (CLAUDE.md); problems with systems the
     Agency supports go to its own support desk. --}}
<section {{ $attributes->class('rounded-card border border-line bg-surface p-5') }} aria-labelledby="advisory-report">
    <h2 id="advisory-report" class="font-semibold">{{ __('advisories.report.title') }}</h2>
    <p class="mt-2 text-sm text-ink-muted">{{ __('advisories.report.text') }}</p>
    <ul class="mt-4 space-y-2 text-sm">
        <li>
            <a href="{{ config('portal.advisories.incident_url') }}" rel="noopener" class="inline-flex items-center gap-2 font-semibold text-link underline-offset-4 hover:underline">
                <svg aria-hidden="true" class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4 6v6c0 4.5 3.4 8.3 8 9 4.6-.7 8-4.5 8-9V6l-8-3Z"/></svg>
                {{ __('advisories.report.insa') }}
            </a>
        </li>
        @if (Route::has('support.create'))
            <li>
                <a href="{{ route('support.create') }}" class="inline-flex items-center gap-2 font-semibold text-link underline-offset-4 hover:underline">
                    <svg aria-hidden="true" class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2.4-.6-.6-2.4 2.5-2.5Z"/></svg>
                    {{ __('advisories.report.support') }}
                </a>
            </li>
        @endif
    </ul>
</section>
