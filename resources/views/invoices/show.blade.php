@php($isPaid = $invoice->status === \App\Enums\InvoiceStatus::Paid)
<x-layouts::site
    :title="($isPaid ? 'Receipt ' : 'Invoice ').$invoice->number"
    description="Your invoice from {{ \App\Models\Setting::get('business_name') }}."
>
    <section class="page-hero">
        <div class="hero__bg" aria-hidden="true">
            <div class="hero__grid"></div>
            <div class="blob blob--1"></div>
        </div>
        <div class="container">
            <span class="eyebrow eyebrow--dark">{{ $isPaid ? 'Receipt' : 'Invoice' }} {{ $invoice->number }}</span>
            <h1>{{ $invoice->title }}</h1>
            <p class="lede">For {{ $invoice->client->name }}@if ($invoice->project) · {{ $invoice->project->title }}@endif</p>
        </div>
    </section>

    <section class="section">
        <div class="container invoice-page">
            @if (session('payment_success'))
                <p class="invoice-alert invoice-alert--success" role="status">{{ session('payment_success') }}</p>
            @endif
            @if (session('payment_error'))
                <p class="invoice-alert invoice-alert--error" role="alert">{{ session('payment_error') }}</p>
            @endif

            <div class="card invoice-card">
                <div class="invoice-card__head">
                    <span @class(['invoice-status', 'invoice-status--'.$invoice->status->value])>{{ $invoice->status->label() }}</span>
                    @if ($isPaid && $invoice->paid_at)
                        <span class="invoice-card__date">Paid {{ $invoice->paid_at->format('j F Y') }}</span>
                    @elseif ($invoice->isPayable() && $invoice->due_on)
                        <span class="invoice-card__date">Due {{ $invoice->due_on->format('j F Y') }}</span>
                    @endif
                </div>

                <dl class="invoice-lines">
                    @foreach ($invoice->items as $item)
                        <div><dt>{{ $item['description'] }}</dt><dd>{{ \App\Models\Invoice::naira($item['amount']) }}</dd></div>
                    @endforeach
                    <div class="invoice-lines__total"><dt>{{ $invoice->isPayable() ? 'Total to pay' : 'Total' }}</dt><dd>{{ \App\Models\Invoice::naira($invoice->amount) }}</dd></div>
                </dl>

                @if ($invoice->notes)
                    <p class="invoice-note">{!! nl2br(e($invoice->notes)) !!}</p>
                @endif

                @if ($invoice->isPayable())
                    <div class="invoice-pay">
                        @if (\App\Support\Paystack::enabled())
                            <form method="POST" action="{{ \Illuminate\Support\Facades\URL::signedRoute('invoices.public.pay', $invoice) }}">
                                @csrf
                                <button type="submit" class="btn btn--primary btn--block">Pay {{ \App\Models\Invoice::naira($invoice->amount, false) }} with Paystack <x-site-icon name="arrow-right" /></button>
                            </form>
                            <p class="invoice-pay__hint">Card, bank transfer or USSD on Paystack's secure page. Your receipt is emailed as soon as the payment goes through.</p>
                        @endif

                        @if (filled($bankDetails))
                            <div class="invoice-bank">
                                <strong>{{ \App\Support\Paystack::enabled() ? 'Or pay by bank transfer' : 'Pay by bank transfer' }}</strong>
                                <p>{!! nl2br(e($bankDetails)) !!}</p>
                                <p>Use <strong>{{ $invoice->number }}</strong> as the payment description. We'll send your receipt once it arrives.</p>
                            </div>
                        @elseif (! \App\Support\Paystack::enabled())
                            <p class="invoice-pay__hint">Message us on WhatsApp for payment details.</p>
                        @endif
                    </div>
                @endif

                <div class="invoice-card__foot">
                    <a class="btn btn--outline btn--sm" href="{{ \Illuminate\Support\Facades\URL::signedRoute('invoices.public.pdf', $invoice) }}">Download {{ $isPaid ? 'receipt' : 'invoice' }} (PDF)</a>
                    @auth
                        @if (auth()->id() === $invoice->user_id)
                            <a class="link" href="{{ route('portal.invoices.show', $invoice) }}" wire:navigate>Open in your account</a>
                        @endif
                    @else
                        <a class="link" href="{{ route('login') }}">Log in to your account</a>
                    @endauth
                </div>
            </div>
        </div>
    </section>
</x-layouts::site>
