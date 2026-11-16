<nav aria-label="{{ __('nav.language') }}">
    <ul class="flex flex-wrap items-center gap-1">
        @foreach ($items as $item)
            <li><a href="{{ $item['url'] }}" lang="{{ $item['locale'] }}" hreflang="{{ $item['locale'] }}" @if ($item['current']) aria-current="true" @endif @class([
                'inline-block rounded-control px-2 py-1 transition-colors duration-150',
                'font-semibold text-night-ink underline decoration-cyan-400 decoration-2 underline-offset-[6px]' => $item['current'],
                'text-night-muted hover:bg-white/5 hover:text-night-ink' => ! $item['current'],
            ])>{{ $item['name'] }}</a></li>
        @endforeach
    </ul>
</nav>
