<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\Response;

/**
 * Build the analytics success body with `total_revenue` as a JSON number with two decimals
 * (e.g. 710.00). Plain json_encode(float) would emit 710 for whole values. Quoting as a string
 * would produce '"710.00"'. This helper concatenates a safe numeric literal then json_encodes the rest.
 */
final class OrderAnalyticsSuccessResponse
{
    /**
     * @param  array{total_revenue: string, best_selling_sku: array{sku: string, total_quantity: int}, warnings?: list<array{line: int, message: string}>}  $payload
     */
    public static function make(array $payload): Response
    {
        $revenueLiteral = (string) $payload['total_revenue'];
        if (! preg_match('/^\d+\.\d{2}$/', $revenueLiteral)) {
            throw new \InvalidArgumentException(
                'total_revenue must be a two-decimal amount string (e.g. 710.00) from the analytics service.'
            );
        }

        $rest = $payload;
        unset($rest['total_revenue']);
        $restJson = json_encode(
            $rest,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
        if ($restJson === '' || ($restJson[0] ?? '') !== '{') {
            throw new \InvalidArgumentException('Analytics payload must include an object with best_selling_sku and optional warnings.');
        }

        $rawJson = '{"total_revenue":'.$revenueLiteral.','.substr($restJson, 1);

        return new Response(
            $rawJson,
            200,
            ['Content-Type' => 'application/json; charset=UTF-8'],
        );
    }
}