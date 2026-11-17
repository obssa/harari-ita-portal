<x-layouts.print :title="__('academy.certificate.title')">
    <x-slot:actions>
        <a href="{{ route('enrolments.show', ['token' => $token]) }}" class="text-sm text-link underline underline-offset-4">{{ __('academy.learner.back') }}</a>
    </x-slot:actions>
    <x-academy.certificate :certificate="$certificate" />
</x-layouts.print>
