<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Runs Google PageSpeed Insights (mobile) for a website and returns the numbers
 * shown on the home page speed card.
 */
class PageSpeed
{
    private const string ENDPOINT = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';

    /**
     * @return array{scores: list<array{label: string, value: string}>, stats: list<array{value: string, label: string}>}
     *
     * @throws RuntimeException when Google cannot test the website
     */
    public static function run(string $url): array
    {
        $query = 'url='.rawurlencode($url).'&strategy=mobile'
            .'&category=performance&category=accessibility&category=best-practices&category=seo';

        if (filled($key = config('services.pagespeed.key'))) {
            $query .= '&key='.rawurlencode($key);
        }

        try {
            $response = Http::timeout(90)->get(self::ENDPOINT.'?'.$query);
        } catch (Throwable) {
            throw new RuntimeException('Google PageSpeed could not be reached. Check your internet connection and try again.');
        }

        $result = $response->json('lighthouseResult');

        if (! $response->successful() || ! is_array($result)) {
            $message = (string) $response->json('error.message');

            throw new RuntimeException(match (true) {
                $response->status() === 429 || str_contains($message, 'Quota') => 'Google needs a free PageSpeed API key before it will test websites from here. Add PAGESPEED_API_KEY to your .env file, or type the scores from pagespeed.web.dev into the boxes below.',
                $message !== '' => 'Google could not test that website: '.$message,
                default => 'Google could not test that website. Check the domain and try again.',
            });
        }

        $score = fn (string $category): string => (string) (int) round(((float) data_get($result, "categories.{$category}.score", 0)) * 100);
        $seconds = fn (string $audit): string => number_format(((float) data_get($result, "audits.{$audit}.numericValue", 0)) / 1000, 1).' s';
        $bytes = (float) data_get($result, 'audits.total-byte-weight.numericValue', 0);

        return [
            'scores' => [
                ['label' => 'Performance', 'value' => $score('performance')],
                ['label' => 'Accessibility', 'value' => $score('accessibility')],
                ['label' => 'Best practices', 'value' => $score('best-practices')],
                ['label' => 'SEO', 'value' => $score('seo')],
            ],
            'stats' => [
                ['value' => $seconds('first-contentful-paint'), 'label' => 'first content shows on mobile'],
                ['value' => $seconds('largest-contentful-paint'), 'label' => 'main content loaded on mobile'],
                ['value' => $bytes >= 1048576 ? number_format($bytes / 1048576, 1).' MB' : number_format($bytes / 1024).' KB', 'label' => 'total page size'],
            ],
        ];
    }
}
