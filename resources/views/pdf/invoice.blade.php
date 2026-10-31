@php($isPaid = $invoice->status === \App\Enums\InvoiceStatus::Paid)
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $isPaid ? 'Receipt' : 'Invoice' }} {{ $invoice->number }}</title>
    <style>
        @page { margin: 36px 42px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2937; line-height: 1.5; }
        h1 { font-size: 26px; margin: 0; color: #0f172a; letter-spacing: 1px; }
        .muted { color: #6b7280; }
        .brand { font-size: 16px; font-weight: bold; color: #0f172a; }
        .top td { vertical-align: top; }
        table { width: 100%; border-collapse: collapse; }
        .items { margin-top: 28px; }
        .items th { text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; border-bottom: 2px solid #0f172a; padding: 8px 0; }
        .items td { padding: 10px 0; border-bottom: 1px solid #e5e7eb; }
        .right { text-align: right; }
        .total td { border-bottom: none; font-size: 14px; font-weight: bold; padding-top: 14px; }
        .stamp { display: inline-block; padding: 4px 12px; border: 2px solid {{ $isPaid ? '#16a34a' : '#d97706' }}; color: {{ $isPaid ? '#16a34a' : '#d97706' }}; font-weight: bold; letter-spacing: 2px; margin-top: 8px; }
        .box { margin-top: 26px; padding: 14px 16px; background: #f3f4f6; }
        .footer { margin-top: 40px; font-size: 10px; color: #6b7280; text-align: center; }
    </style>
</head>
<body>
    <table class="top">
        <tr>
            <td>
                <div class="brand">{{ $business['business_name'] }}</div>
                <div class="muted">{{ $business['city'] }}</div>
                <div class="muted">{{ $business['email'] }} · {{ $business['whatsapp_primary'] }}</div>
            </td>
            <td class="right">
                <h1>{{ $isPaid ? 'RECEIPT' : 'INVOICE' }}</h1>
                <div>{{ $invoice->number }}</div>
                <div class="stamp">{{ strtoupper($invoice->status->label()) }}</div>
            </td>
        </tr>
    </table>

    <table class="top" style="margin-top: 28px;">
        <tr>
            <td>
                <div class="muted">Billed to</div>
                <strong>{{ $invoice->client->name }}</strong><br>
                {{ $invoice->client->email }}
            </td>
            <td class="right">
                <div><span class="muted">Issued:</span> {{ $invoice->created_at?->format('j M Y') }}</div>
                @if ($isPaid)
                    <div><span class="muted">Paid:</span> {{ $invoice->paid_at?->format('j M Y') }}</div>
                    <div><span class="muted">Method:</span> {{ \App\Models\Invoice::PAYMENT_METHODS[$invoice->payment_method] ?? $invoice->payment_method }}</div>
                    @if ($invoice->payment_reference)
                        <div><span class="muted">Reference:</span> {{ $invoice->payment_reference }}</div>
                    @endif
                @elseif ($invoice->due_on)
                    <div><span class="muted">Due:</span> {{ $invoice->due_on->format('j M Y') }}</div>
                @endif
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>{{ $invoice->title }}{{ $invoice->project ? ' · '.$invoice->project->title : '' }}</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td>{{ $item['description'] }}</td>
                    <td class="right">{{ \App\Models\Invoice::naira($item['amount']) }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td>{{ $isPaid ? 'Total paid' : 'Total due' }}</td>
                <td class="right">{{ \App\Models\Invoice::naira($invoice->amount) }}</td>
            </tr>
        </tbody>
    </table>

    @if ($invoice->notes)
        <div class="box">{!! nl2br(e($invoice->notes)) !!}</div>
    @endif

    @if (! $isPaid && filled($bankDetails))
        <div class="box">
            <strong>Pay by bank transfer</strong><br>
            {!! nl2br(e($bankDetails)) !!}<br>
            Use <strong>{{ $invoice->number }}</strong> as the payment description.
        </div>
    @endif

    <div class="footer">
        {{ $isPaid ? 'Thank you for your payment.' : 'Thank you for your business.' }} · {{ $business['business_name'] }}
    </div>
</body>
</html>
