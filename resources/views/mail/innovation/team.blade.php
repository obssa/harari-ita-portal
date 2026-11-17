<x-mail::message>
# {{ __('innovation.mail.team.heading', ['challenge' => $challenge]) }}

@if (filled($name))
{{ __('events.registration.mail.greeting', ['name' => $name]) }}

@endif
{{ __('innovation.mail.team.body', ['team' => $team, 'challenge' => $challenge]) }}

{{ __('innovation.mail.team.deadline') }} {{ $closes }}

**{{ __('support.reference') }}: {{ $reference }}**

<x-mail::button :url="$url">
{{ __('innovation.mail.team.button') }}
</x-mail::button>

{{ __('support.mail.footer') }}
</x-mail::message>
