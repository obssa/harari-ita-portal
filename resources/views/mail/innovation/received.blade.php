<x-mail::message>
# {{ __('innovation.mail.received.heading', ['name' => $startup]) }}

@if (filled($name))
{{ __('events.registration.mail.greeting', ['name' => $name]) }}

@endif
{{ __('innovation.mail.received.body') }}

**{{ __('support.reference') }}: {{ $reference }}**

<x-mail::button :url="$url">
{{ __('innovation.mail.received.button') }}
</x-mail::button>

{{ __('support.mail.footer') }}
</x-mail::message>
