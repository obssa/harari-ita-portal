<x-layouts.print :title="$title">
    @forelse ($certificates as $certificate)
        <x-academy.certificate :certificate="$certificate" />
    @empty
        <p class="text-ink-muted">{{ __('academy.certificate.none') }}</p>
    @endforelse
</x-layouts.print>
