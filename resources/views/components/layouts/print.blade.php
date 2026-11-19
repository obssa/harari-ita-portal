@props(['title', 'download' => null])
{{-- ADR-0030: a bare page for printing certificates: no site chrome, a toolbar that is not printed, never indexed. --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} · {{ __('site.name') }}</title>
    <link rel="icon" href="/favicon.ico?v=2" sizes="48x48">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-surface-muted text-ink antialiased print:bg-white">
    <div class="sticky top-0 z-10 border-b border-line bg-canvas print:hidden">
        <div class="mx-auto flex max-w-page flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6">
            <p class="font-display text-base font-semibold">{{ $title }}</p>
            <div class="flex items-center gap-3">
                {{ $actions ?? '' }}
                {{-- With a PDF to download (ADR-0030 addendum), that is the main action and printing comes second. --}}
                @if ($download)
                    <a href="{{ $download }}" download class="inline-flex items-center gap-2 rounded-control bg-primary-700 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4"><path d="M12 4v11m0 0-4-4m4 4 4-4M5 19h14"/></svg>
                        {{ __('academy.certificate.download') }}
                    </a>
                @endif
                <button type="button" data-print @class([
                    'inline-flex items-center gap-2 rounded-control px-4 py-2 text-sm font-semibold focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600',
                    'bg-primary-700 text-white hover:bg-primary-800' => ! $download,
                    'border border-line bg-surface text-ink hover:bg-surface-muted' => $download,
                ])>
                    {{ $download ? __('academy.certificate.print_only') : __('academy.certificate.print') }}
                </button>
            </div>
        </div>
        <p class="mx-auto max-w-page px-4 pb-3 text-sm text-ink-muted sm:px-6">{{ $download ? __('academy.certificate.download_help') : __('academy.certificate.print_help') }}</p>
    </div>
    <main class="mx-auto flex max-w-page flex-col items-center gap-8 px-4 py-8 sm:px-6 print:block print:max-w-none print:p-0">
        {{ $slot }}
    </main>
    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">document.querySelector('[data-print]')?.addEventListener('click',function(){window.print()});</script>
</body>
</html>
