@php
    // The news feed by default; advisories and opportunities pass their own channel (ADR-0032, ADR-0034).
    $channel ??= ['title' => __('site.name').' — '.__('news.title'), 'link' => route('news.index'), 'description' => __('news.description'), 'self' => route('feed.rss')];
@endphp
{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">
    <channel>
        <title>{{ $channel['title'] }}</title>
        <link>{{ $channel['link'] }}</link>
        <description>{{ $channel['description'] }}</description>
        <language>{{ $locale }}</language>
        <atom:link href="{{ $channel['self'] }}" rel="self" type="application/rss+xml" />
        @foreach ($items as $item)
            <item>
                <title>{{ $item['title'] }}</title>
                <link>{{ $item['url'] }}</link>
                <guid isPermaLink="true">{{ $item['url'] }}</guid>
                <pubDate>{{ $item['date']->toRssString() }}</pubDate>
                @if ($item['summary'])
                    <description>{{ $item['summary'] }}</description>
                @endif
                @foreach ($item['tags'] as $tag)
                    <category>{{ $tag }}</category>
                @endforeach
                <content:encoded><![CDATA[{!! str_replace(']]>', ']]]]><![CDATA[>', $item['html']) !!}]]></content:encoded>
            </item>
        @endforeach
    </channel>
</rss>
