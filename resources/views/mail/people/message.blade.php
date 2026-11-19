<x-mail::message>
# {{ $heading }}

@if (filled($name))
{{ __('events.registration.mail.greeting', ['name' => $name]) }}

@endif
{{ $body }}

@if (filled($reference))
**{{ __('support.reference') }}: {{ $reference }}**

@endif
@if (filled($url) && filled($button))
<x-mail::button :url="$url">
{{ $button }}
</x-mail::button>

@endif
{{ __('support.mail.footer') }}
</x-mail::message>
