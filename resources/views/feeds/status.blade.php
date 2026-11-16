{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>{{ __('site.name') }} — {{ __('status.title') }}</title>
        <link>{{ route('status') }}</link>
        <description>{{ __('status.description') }}</description>
        <language>{{ $locale }}</language>
        <atom:link href="{{ route('status.rss') }}" rel="self" type="application/rss+xml" />
        @foreach ($incidents as $incident)
            @php $latest = $incident->updates->first(); @endphp
            <item>
                <title>{{ $incident->title($locale) }} ({{ $incident->resolved_at ? __('status.stage.resolved') : $incident->impact->label() }})</title>
                <link>{{ route('status').'#incident-'.$incident->id }}</link>
                <guid isPermaLink="false">{{ $incident->id }}-{{ $latest?->id }}</guid>
                <pubDate>{{ ($latest?->created_at ?? $incident->started_at)->toRssString() }}</pubDate>
                @if ($latest)
                    <description>{{ $latest->stage->label() }}: {{ $latest->body($locale) }}</description>
                @endif
            </item>
        @endforeach
    </channel>
</rss>
