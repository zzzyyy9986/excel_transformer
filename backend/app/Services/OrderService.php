<?php

namespace App\Services;

use App\Models\ExcelTemplate;
use App\Models\Order;

class OrderService
{
    /**
     * @param array{
     *     tier_index: int,
     *     tier_name: string,
     *     client_email?: string,
     *     comment?: string,
     *     items: list<array{
     *         row_index: int,
     *         model: string,
     *         size: string,
     *         color: string,
     *         quantity: int|float,
     *         unit_price: float
     *     }>
     * } $payload
     */
    public function create(ExcelTemplate $template, array $payload): Order
    {
        $items = collect($payload['items'])
            ->filter(fn (array $item) => ($item['quantity'] ?? 0) > 0)
            ->values()
            ->all();

        $total = collect($items)->sum(fn (array $item) => $item['quantity'] * $item['unit_price']);

        return Order::query()->create([
            'excel_template_id' => $template->id,
            'tier_index' => $payload['tier_index'],
            'tier_name' => $payload['tier_name'],
            'client_email' => $payload['client_email'] ?? null,
            'comment' => $payload['comment'] ?? null,
            'items' => $items,
            'total_amount' => round($total, 2),
        ]);
    }
}
