<x-mail::message>
# {{ __("academy.mail.{$kind}.heading") }}

{{ __('academy.mail.greeting', ['name' => $name]) }}

{{ __("academy.mail.{$kind}.body", ['course' => $title, 'position' => (string) $position]) }}

**{{ $title }}** · {{ $cohort->name }}<br>
{{ app(\App\Services\Calendar\EthiopianCalendar::class)->format($cohort->starts_on) }} – {{ app(\App\Services\Calendar\EthiopianCalendar::class)->format($cohort->ends_on) }}

<x-mail::button :url="$url">
{{ __('academy.mail.button') }}
</x-mail::button>

{{ __('academy.mail.footer') }}
</x-mail::message>
