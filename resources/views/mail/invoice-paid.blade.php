<x-mail::message>
# Thank you for your payment

Hi {{ $invoice->client->name }},

We have received **{{ \App\Models\Invoice::naira($invoice->amount) }}** for invoice {{ $invoice->number }} ({{ $invoice->title }}) on {{ $invoice->paid_at?->format('j F Y') }}.

Your PDF receipt is attached. You can also download it any time from your account.

<x-mail::button :url="$invoiceUrl" color="success">
View receipt
</x-mail::button>

{{ $businessName }}
</x-mail::message>
