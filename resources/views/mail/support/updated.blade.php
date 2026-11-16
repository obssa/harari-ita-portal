<x-mail::message>
# {{ __("support.mail.{$kind}.heading") }}

@if (filled($name))
{{ __('events.registration.mail.greeting', ['name' => $name]) }}

@endif
{{ __("support.mail.{$kind}.body") }}

**{{ __('support.reference') }}: {{ $reference }}**

<x-mail::button :url="$url">
{{ __('support.mail.button') }}
</x-mail::button>

{{ __('support.mail.footer') }}
</x-mail::message>
