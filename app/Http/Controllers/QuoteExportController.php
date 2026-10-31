<?php

namespace App\Http\Controllers;

use App\Enums\QuoteStatus;
use App\Models\QuoteRequest;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuoteExportController extends Controller
{
    /**
     * Download quote requests as a CSV file (all, or one status).
     */
    public function __invoke(Request $request): StreamedResponse
    {
        $status = QuoteStatus::tryFrom((string) $request->query('status'));

        return response()->streamDownload(function () use ($status): void {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                return;
            }

            fputcsv($output, ['date', 'name', 'business', 'phone', 'email', 'service', 'budget', 'status', 'follow_up_on', 'details', 'notes']);

            QuoteRequest::query()
                ->when($status, fn ($query) => $query->where('status', $status))
                ->latest()
                ->each(function (QuoteRequest $quote) use ($output): void {
                    fputcsv($output, [
                        $quote->created_at?->format('Y-m-d H:i'),
                        $quote->name,
                        $quote->business,
                        $quote->phone,
                        $quote->email,
                        $quote->service,
                        $quote->budget,
                        $quote->status->label(),
                        $quote->follow_up_on?->toDateString(),
                        $quote->details,
                        $quote->notes,
                    ]);
                });

            fclose($output);
        }, 'quote-requests-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }
}
