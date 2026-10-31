<x-mail::message>
# Invoice {{ $invoice->number }}

Hi {{ $invoice->client->name }},

Here is your invoice for **{{ $invoice->title }}**.

<x-mail::table>
| Item | Amount |
| :--- | ---: |
@foreach ($invoice->items as $item)
| {{ $item['description'] }} | {{ \App\Models\Invoice::naira($item['amount']) }} |
@endforeach
| **Total** | **{{ \App\Models\Invoice::naira($invoice->amount) }}** |
</x-mail::table>

@if ($invoice->due_on)
Please pay by **{{ $invoice->due_on->format('j F Y') }}**.
@endif

<x-mail::button :url="$invoiceUrl" color="success">
{{ $canPayOnline ? 'View and pay online' : 'View your invoice' }}
</x-mail::button>

@if (filled($bankDetails))
You can also pay by bank transfer:

{!! nl2br(e($bankDetails)) !!}

Use **{{ $invoice->number }}** as the payment description.
@endif

The PDF invoice is attached.

{{ $businessName }}
</x-mail::message>
