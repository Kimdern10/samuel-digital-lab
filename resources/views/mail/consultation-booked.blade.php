<x-mail::message>
# New call booked

**{{ $consultation->name }}** booked a {{ $consultation->minutes }}-minute {{ strtolower($consultation->methodLabel()) }}.

<x-mail::table>
| | |
| :-- | :-- |
| When | {{ $consultation->when() }} |
| Phone / WhatsApp | {{ $consultation->phone }} |
| Email | {{ $consultation->email ?: '—' }} |
</x-mail::table>

@if ($consultation->topic)
**What they want to talk about**

{{ $consultation->topic }}
@endif

<x-mail::button :url="$whatsAppUrl" color="success">
Message on WhatsApp
</x-mail::button>

[See all booked calls]({{ $adminUrl }})

{{ config('app.name') }}
</x-mail::message>
