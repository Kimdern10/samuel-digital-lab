<x-mail::message>
# New quote request

**{{ $quote->name }}**{{ $quote->business ? ' from '.$quote->business : '' }} wants a quote.

<x-mail::table>
| | |
| :-- | :-- |
| What they need | {{ $quote->service }} |
| Budget | {{ $quote->budget }} |
| Phone / WhatsApp | {{ $quote->phone }} |
| Email | {{ $quote->email ?: '—' }} |
</x-mail::table>

**Their message**

{{ $quote->details }}

<x-mail::button :url="$whatsAppUrl" color="success">
Reply on WhatsApp
</x-mail::button>

[Open in the admin]({{ $adminUrl }})

{{ config('app.name') }}
</x-mail::message>
