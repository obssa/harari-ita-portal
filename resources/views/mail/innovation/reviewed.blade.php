<x-mail::message>
# {{ __('innovation.mail.reviewed.heading', ['name' => $startup]) }}

@if (filled($name))
{{ __('events.registration.mail.greeting', ['name' => $name]) }}

@endif
{{ __('innovation.mail.reviewed.body', ['status' => $status]) }}

@if (filled($note))
> {{ $note }}

@endif
**{{ __('support.reference') }}: {{ $reference }}**

{{ __('support.mail.footer') }}
</x-mail::message>
