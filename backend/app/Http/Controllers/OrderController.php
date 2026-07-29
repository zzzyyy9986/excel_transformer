<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesClientId;
use App\Models\ExcelTemplate;
use App\Services\ExcelTemplateService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use ResolvesClientId;

    public function __construct(
        private readonly OrderService $orderService,
        private readonly ExcelTemplateService $templateService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $clientId = $this->clientId($request);

        if ($clientId === null) {
            return response()->json(['message' => 'Не удалось определить браузер клиента.'], 400);
        }

        $template = $this->templateService->getForClient($clientId);

        if ($template === null) {
            return response()->json(['message' => 'Сначала загрузите Excel-шаблон.'], 422);
        }

        $validated = $request->validate([
            'tier_index' => ['required', 'integer', 'min:0'],
            'tier_name' => ['required', 'string', 'max:255'],
            'client_email' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array'],
            'items.*.row_index' => ['required', 'integer'],
            'items.*.model' => ['required', 'string'],
            'items.*.size' => ['required', 'string'],
            'items.*.color' => ['required', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $order = $this->orderService->create($template, $validated);

        return response()->json([
            'message' => 'Заказ успешно отправлен.',
            'order' => [
                'id' => $order->id,
                'total_amount' => $order->total_amount,
                'items_count' => count($order->items),
            ],
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $clientId = $this->clientId($request);

        if ($clientId === null) {
            return response()->json(['orders' => []]);
        }

        $orders = ExcelTemplate::query()
            ->where('client_id', $clientId)
            ->latest()
            ->first()?->orders()
            ->latest()
            ->limit(20)
            ->get(['id', 'tier_name', 'client_email', 'comment', 'items', 'total_amount', 'created_at']);

        return response()->json(['orders' => $orders ?? collect()]);
    }
}
