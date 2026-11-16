@props(['draw' => false])
{{-- ADR-0014: the "Jugol circuit": the walled town's lanes abstracted into circuit traces (straight runs that turn at
     45 degrees, as the alleys bend) with pads where they meet. Traces in currentColor, pads in the logo's orange.
     Decorative only; `draw` traces the lines in once (off under reduced motion). --}}
<svg aria-hidden="true" focusable="false" viewBox="0 0 400 200" fill="none" preserveAspectRatio="xMidYMid slice"
     {{ $attributes->class(['pointer-events-none', 'draw-in' => $draw]) }}>
    <g stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M0 40h120l30 30h110l30-30h110"/>
        <path d="M0 100h80l30 30h90"/>
        <path d="M400 160h-90l-30-30h-50"/>
        <path d="M260 70v60l20 20"/>
        <path d="M150 70v80l-20 20H0"/>
        <path d="M330 40V0"/>
        <path d="M60 40V0M60 100v40l20 20"/>
        <path d="M400 100h-50l-20-20"/>
    </g>
    <g fill="#e8912d">
        <circle cx="200" cy="130" r="4"/>
        <circle cx="230" cy="130" r="4"/>
        <circle cx="80" cy="160" r="4"/>
        <circle cx="330" cy="80" r="4"/>
    </g>
    <g fill="currentColor">
        <circle cx="120" cy="40" r="3"/>
        <circle cx="290" cy="40" r="3"/>
        <circle cx="260" cy="70" r="3"/>
        <circle cx="150" cy="70" r="3"/>
        <circle cx="60" cy="100" r="3"/>
    </g>
</svg>
