@props(['pins', 'headingLevel' => 2])
@php
    // ADR-0024: where the projects are, drawn on the server from their map pins over the area they cover, on the
    // Jugol Circuit grid. Nothing loads from a map service; the OpenStreetMap view of the same area is a link.
    // $pins: list of ['title' => string, 'url' => string, 'lat' => float, 'lng' => float, 'tone' => 'cyan'|'saffron'|'ruby'].
    $pins = array_values($pins);
    $lats = array_column($pins, 'lat');
    $lngs = array_column($pins, 'lng');
    $pad = 0.01;
    [$south, $north] = [min($lats) - $pad, max($lats) + $pad];
    [$west, $east] = [min($lngs) - $pad, max($lngs) + $pad];
    // Equirectangular, corrected for latitude so the area keeps its shape.
    $k = cos(deg2rad(($south + $north) / 2));
    $spanX = ($east - $west) * $k;
    $spanY = $north - $south;
    $w = 640;
    $h = (int) max(220, min(420, round($w * $spanY / max($spanX, 0.0001))));
    $x = fn (float $lng): float => round(24 + ($lng - $west) * $k / max($spanX, 0.0001) * ($w - 48), 1);
    $y = fn (float $lat): float => round(24 + ($north - $lat) / max($spanY, 0.0001) * ($h - 48), 1);
    $osm = 'https://www.openstreetmap.org/?minlon='.$west.'&minlat='.$south.'&maxlon='.$east.'&maxlat='.$north;
@endphp
<figure {{ $attributes->class('overflow-hidden rounded-card border border-night-line bg-night-950 text-night-ink') }}>
    <svg viewBox="0 0 {{ $w }} {{ $h }}" class="block h-auto w-full" aria-labelledby="locations-title">
        <title id="locations-title">{{ trans_choice('projects.locations_alt', count($pins), ['count' => count($pins)]) }}</title>
        <defs>
            <pattern id="locations-grid" width="32" height="32" patternUnits="userSpaceOnUse">
                <path d="M32 0H0v32" fill="none" stroke="currentColor" stroke-width="1" class="text-night-line" />
            </pattern>
        </defs>
        <rect width="{{ $w }}" height="{{ $h }}" fill="url(#locations-grid)" />
        @foreach ($pins as $i => $pin)
            @php [$px, $py] = [$x($pin['lng']), $y($pin['lat'])]; @endphp
            <a href="{{ $pin['url'] }}" aria-label="{{ $pin['title'] }}" class="group focus:outline-none">
                <circle cx="{{ $px }}" cy="{{ $py }}" r="14" @class(['opacity-20 group-hover:opacity-40 group-focus-visible:opacity-60', 'fill-cyan-400' => $pin['tone'] === 'cyan', 'fill-saffron-400' => $pin['tone'] === 'saffron', 'fill-ruby-400' => $pin['tone'] === 'ruby']) />
                <circle cx="{{ $px }}" cy="{{ $py }}" r="6" @class(['stroke-night-950', 'fill-cyan-300' => $pin['tone'] === 'cyan', 'fill-saffron-300' => $pin['tone'] === 'saffron', 'fill-ruby-300' => $pin['tone'] === 'ruby']) stroke-width="2" />
                <circle cx="{{ $px }}" cy="{{ $py }}" r="18" fill="none" class="stroke-cyan-200 opacity-0 group-focus-visible:opacity-100" stroke-width="2" />
                <title>{{ $pin['title'] }}</title>
            </a>
        @endforeach
    </svg>
    <figcaption class="flex flex-wrap items-center justify-between gap-3 border-t border-night-line px-4 py-3 text-sm text-night-muted">
        <span>{{ __('projects.locations_note') }}</span>
        <a href="{{ $osm }}" rel="noopener" class="text-cyan-200 underline underline-offset-4 hover:text-cyan-100">{{ __('projects.locations_osm') }}</a>
    </figcaption>
</figure>
