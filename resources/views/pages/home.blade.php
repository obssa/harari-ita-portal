<x-layouts.public :description="__('site.description')" :flush-footer="true">
    <h1 class="sr-only">{{ __('site.name') }}</h1>
    @php $impactShown = false; @endphp
    @foreach ($sections as $item)
        @include('pages.home.'.$item['section']->key->value, ['section' => $item['section'], 'data' => $item['data']])
        {{-- ADR-0051: the published figures follow the pillars, the work they measure. --}}
        @if ($item['section']->key->value === 'pillars')
            @include('pages.home._impact')
            @php $impactShown = true; @endphp
        @endif
    @endforeach
    @unless ($impactShown)
        @include('pages.home._impact')
    @endunless
    {{-- ADR-0046: Harar's heritage band closes the page, just above the footer. --}}
    @include('pages.home._heritage')
</x-layouts.public>
