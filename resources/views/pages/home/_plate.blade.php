{{-- ADR-0012: the logo's hexagon cluster (it replaces the source project's basket plate): a pointy-top honeycomb of
     outlined cells in cyan, a few filled in the logo's orange, ruby and teal. Decoration only. --}}
<svg aria-hidden="true" viewBox="0 0 200 200" class="{{ $class ?? '' }}" fill="none" stroke-width="2.5">
    @foreach ([[100, 100, '#e8912d'], [131, 100, null], [69, 100, null], [115.5, 73, null], [84.5, 73, null], [115.5, 127, '#b8323e'], [84.5, 127, null], [146.5, 73, '#1296ab'], [53.5, 127, null], [100, 46, null], [131, 46, null], [162, 100, '#0b4a5c'], [38, 100, '#e8912d'], [146.5, 127, null], [69, 154, null], [100, 154, null], [53.5, 73, null], [131, 154, null]] as [$x, $y, $fill])
        <path d="M{{ $x }} {{ $y - 16 }}l13.9 8v16l-13.9 8-13.9-8v-16z" @if ($fill) fill="{{ $fill }}" stroke="{{ $fill }}" @else stroke="#1296ab" @endif />
    @endforeach
</svg>
