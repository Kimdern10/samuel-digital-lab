<?php

namespace App\Support;

use App\Models\QuoteRequest;
use App\Models\Setting;

/**
 * Works out a website price estimate from the prices the owner sets in the admin.
 */
class CostEstimator
{
    /**
     * The website types visitors can pick.
     *
     * @return list<array{name: string, price: int, pages: int}>
     */
    public static function siteTypes(): array
    {
        return array_map(fn (array $type): array => [
            'name' => (string) ($type['name'] ?? ''),
            'price' => max(0, (int) ($type['price'] ?? 0)),
            'pages' => max(1, (int) ($type['pages'] ?? 1)),
        ], Setting::list('calc_site_types'));
    }

    /**
     * The extras visitors can add.
     *
     * @return list<array{name: string, price: int}>
     */
    public static function extras(): array
    {
        return array_map(fn (array $extra): array => [
            'name' => (string) ($extra['name'] ?? ''),
            'price' => max(0, (int) ($extra['price'] ?? 0)),
        ], Setting::list('calc_extras'));
    }

    /**
     * The price of each page beyond those included.
     */
    public static function extraPagePrice(): int
    {
        return max(0, (int) Setting::get('calc_extra_page_price'));
    }

    /**
     * Calculate the estimate.
     *
     * @param  list<int>  $extraIndexes
     * @return array{type: string, pages: int, extra_pages: int, extras: list<string>, total: int}
     */
    public static function estimate(int $typeIndex, int $pages, array $extraIndexes): array
    {
        $type = self::siteTypes()[$typeIndex] ?? null;
        $extras = self::extras();

        if ($type === null) {
            return ['type' => '', 'pages' => 0, 'extra_pages' => 0, 'extras' => [], 'total' => 0];
        }

        $pages = max($type['pages'], $pages);
        $extraPages = $pages - $type['pages'];
        $chosen = array_values(array_filter(array_map(fn (int $index): ?array => $extras[$index] ?? null, array_unique($extraIndexes))));

        return [
            'type' => $type['name'],
            'pages' => $pages,
            'extra_pages' => $extraPages,
            'extras' => array_column($chosen, 'name'),
            'total' => $type['price'] + $extraPages * self::extraPagePrice() + array_sum(array_column($chosen, 'price')),
        ];
    }

    /**
     * Pick the budget range on the quote form that fits an estimate.
     */
    public static function budgetFor(int $total): string
    {
        return match (true) {
            $total < 100000 => QuoteRequest::BUDGETS[0],
            $total < 200000 => QuoteRequest::BUDGETS[1],
            $total < 400000 => QuoteRequest::BUDGETS[2],
            default => QuoteRequest::BUDGETS[3],
        };
    }

    /**
     * Format an amount in naira, e.g. ₦150,000.
     */
    public static function naira(int $amount): string
    {
        return '₦'.number_format($amount);
    }
}
