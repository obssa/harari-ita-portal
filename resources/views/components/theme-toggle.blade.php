@props(['class' => ''])
{{-- ADR-0008: one button cycles the device's setting, light and dark; resources/js/theme.js shows and wires it. --}}
<button type="button" hidden data-theme-toggle
        data-label-system="{{ __('theme.current', ['mode' => __('theme.system')]) }}"
        data-label-light="{{ __('theme.current', ['mode' => __('theme.light')]) }}"
        data-label-dark="{{ __('theme.current', ['mode' => __('theme.dark')]) }}"
        title="{{ __('theme.label') }}"
        class="group/theme inline-flex size-9 items-center justify-center rounded-control text-current transition-colors duration-150 hover:bg-white/10 {{ $class }}">
    {{-- Device setting: a half-filled circle. --}}
    <svg aria-hidden="true" class="size-[18px] group-data-[mode=dark]/theme:hidden group-data-[mode=light]/theme:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="8.5"/><path d="M12 3.5a8.5 8.5 0 0 1 0 17z" fill="currentColor"/></svg>
    {{-- Light: a sun. --}}
    <svg aria-hidden="true" class="hidden size-[18px] group-data-[mode=light]/theme:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2.5v2M12 19.5v2M4.6 4.6l1.4 1.4M18 18l1.4 1.4M2.5 12h2M19.5 12h2M4.6 19.4 6 18M18 6l1.4-1.4"/></svg>
    {{-- Dark: a moon. --}}
    <svg aria-hidden="true" class="hidden size-[18px] group-data-[mode=dark]/theme:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"><path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5z"/></svg>
</button>
