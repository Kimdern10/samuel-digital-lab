<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NewsletterController extends Controller
{
    /**
     * Take someone off the newsletter list using the private link in their email.
     */
    public function unsubscribe(string $token): View
    {
        $subscriber = Subscriber::query()->where('token', $token)->firstOrFail();
        $subscriber->update(['unsubscribed_at' => $subscriber->unsubscribed_at ?? now()]);

        return view('newsletter.unsubscribed', ['subscriber' => $subscriber]);
    }

    /**
     * Download everyone still subscribed as a CSV file (admin only).
     */
    public function export(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                return;
            }

            fputcsv($output, ['email', 'subscribed_on', 'unsubscribe_link']);

            Subscriber::query()->active()->orderBy('id')->each(function (Subscriber $subscriber) use ($output): void {
                fputcsv($output, [
                    $subscriber->email,
                    $subscriber->created_at?->toDateString(),
                    route('newsletter.unsubscribe', $subscriber->token),
                ]);
            });

            fclose($output);
        }, 'subscribers-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }
}
