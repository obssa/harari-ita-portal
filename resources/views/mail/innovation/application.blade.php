<x-mail::message>
# {{ __("innovation.mail.application.{$kind}.heading", ['programme' => $programme]) }}

@if (filled($name))
{{ __('events.registration.mail.greeting', ['name' => $name]) }}

@endif
{{ __("innovation.mail.application.{$kind}.body", ['programme' => $programme, 'stage' => $stage]) }}

@if (filled($note))
> {{ $note }}

@endif
**{{ __('support.reference') }}: {{ $reference }}**

<x-mail::button :url="$url">
{{ __('innovation.mail.application.button') }}
</x-mail::button>

{{ __('support.mail.footer') }}
</x-mail::message>
