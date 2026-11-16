<x-layouts.public :title="__('structure.title')" :description="__('structure.description')">
    <x-page-hero :title="__('structure.title')" :lead="__('structure.description')" tone="plaster" :crumbs="[[__('nav.about'), route('structure')]]" />
    <div class="mx-auto max-w-page px-4 pt-2 pb-12 sm:px-6">

        @if ($tree === [])
            <x-ui.empty-state class="mt-10">{{ __('directory.empty') }}</x-ui.empty-state>
        @else
            <ul class="mt-8 space-y-3">
                @foreach ($tree as $node)
                    @include('pages.partials.structure-node', ['node' => $node, 'level' => 1])
                @endforeach
            </ul>
        @endif

        <p class="mt-12 flex flex-wrap gap-x-6 gap-y-2 text-sm">
            <a href="{{ route('leaders.index') }}" class="text-link underline-offset-4 hover:underline">{{ __('leaders.title') }}</a>
            <a href="{{ route('offices.index') }}" class="text-link underline-offset-4 hover:underline">{{ __('offices.title') }}</a>
        </p>
    </div>
</x-layouts.public>
