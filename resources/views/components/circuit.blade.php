@props(['draw' => false, 'flow' => false])
@php
    // ADR-0014: the "Jugol circuit": the walled town's lanes abstracted into circuit traces (straight runs that turn
    // at 45 degrees, as the alleys bend) with pads where they meet.
    $traces = [
        'M0 40h120l30 30h110l30-30h110',
        'M0 100h80l30 30h90',
        'M400 160h-90l-30-30h-50',
        'M260 70v60l20 20',
        'M150 70v80l-20 20H0',
        'M330 40V0',
        'M60 40V0M60 100v40l20 20',
        'M400 100h-50l-20-20',
    ];
    // ADR-0051: with `flow`, light runs along the longer traces, each at its own pace, so the pattern never syncs.
    $pulses = [[0, '7s', '0s'], [1, '5s', '1.4s'], [2, '6s', '2.6s'], [4, '6.5s', '0.8s'], [7, '4.5s', '3.3s']];
@endphp
{{-- Traces in currentColor, pads in the logo's orange. Decorative only; `draw` traces the lines in once and `flow`
     sends light along them (both off under reduced motion). --}}
<svg aria-hidden="true" focusable="false" viewBox="0 0 400 200" fill="none" preserveAspectRatio="xMidYMid slice"
     {{ $attributes->class(['pointer-events-none', 'draw-in' => $draw]) }}>
    <g stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        @foreach ($traces as $trace)
            <path d="{{ $trace }}"/>
        @endforeach
    </g>
    @if ($flow)
        <g class="circuit-flow" stroke="#67e8f9" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            @foreach ($pulses as [$index, $duration, $delay])
                <path d="{{ $traces[$index] }}" pathLength="100" style="--flow-duration: {{ $duration }}; --flow-delay: {{ $delay }}"/>
            @endforeach
        </g>
    @endif
    <g fill="#e8912d" @class(['circuit-pads' => $flow])>
        <circle cx="200" cy="130" r="4"/>
        <circle cx="230" cy="130" r="4" style="--pad-delay: 0.8s"/>
        <circle cx="80" cy="160" r="4" style="--pad-delay: 1.6s"/>
        <circle cx="330" cy="80" r="4" style="--pad-delay: 2.4s"/>
    </g>
    <g fill="currentColor">
        <circle cx="120" cy="40" r="3"/>
        <circle cx="290" cy="40" r="3"/>
        <circle cx="260" cy="70" r="3"/>
        <circle cx="150" cy="70" r="3"/>
        <circle cx="60" cy="100" r="3"/>
    </g>
</svg>
