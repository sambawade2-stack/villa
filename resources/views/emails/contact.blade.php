<x-mail::message>
# {{ __('Nouveau message depuis le site') }}

**{{ __('Sujet') }} :** {{ $payload['subject'] }}

**{{ __('De') }} :** {{ $payload['name'] }} — {{ $payload['email'] }}
@if ($payload['phone'])
**{{ __('Téléphone') }} :** {{ $payload['phone'] }}
@endif

---

{{ $payload['message'] }}

<x-mail::button :url="'mailto:'.$payload['email']">
{{ __('Répondre') }}
</x-mail::button>
</x-mail::message>
