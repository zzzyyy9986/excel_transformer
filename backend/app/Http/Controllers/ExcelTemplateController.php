<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesClientId;
use App\Services\ExcelTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExcelTemplateController extends Controller
{
    use ResolvesClientId;

    public function __construct(
        private readonly ExcelTemplateService $templateService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $clientId = $this->clientId($request);

        $template = $clientId !== null
            ? $this->templateService->getForClient($clientId)
            : null;

        return response()->json(
            $this->templateService->getFormData($template)
        );
    }

    public function upload(Request $request): JsonResponse
    {
        $clientId = $this->clientId($request);

        if ($clientId === null) {
            return response()->json(['message' => 'Не удалось определить браузер клиента.'], 400);
        }

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xls,xlsx', 'max:10240'],
        ]);

        try {
            $template = $this->templateService->upload($validated['file'], $clientId);
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (\Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(
            $this->templateService->getFormData($template),
            201
        );
    }
}
