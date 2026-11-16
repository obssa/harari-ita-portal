<x-mail::message>
# {{ __('support.mail.received.heading') }}

@if (filled($name))
{{ __('events.registration.mail.greeting', ['name' => $name]) }}

@endif
{{ __('support.mail.received.body') }}

**{{ __('support.reference') }}: {{ $reference }}**<br>
@if ($due)
{{ __('support.mail.received.due', ['date' => app(\App\Services\Calendar\EthiopianCalendar::class)->format($due)]) }}
@endif

<x-mail::button :url="$url">
{{ __('support.mail.button') }}
</x-mail::button>

{{ __('support.mail.footer') }}
</x-mail::message>
