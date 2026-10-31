<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Support\InvoicePdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InvoicePdfController extends Controller
{
    /**
     * Download an invoice (or receipt, once paid) as a PDF. Clients only see their own.
     */
    public function __invoke(Request $request, Invoice $invoice): Response
    {
        abort_unless($request->user()?->is_admin || $invoice->user_id === $request->user()?->id, Response::HTTP_NOT_FOUND);

        return InvoicePdf::make($invoice)->download(InvoicePdf::filename($invoice));
    }
}
