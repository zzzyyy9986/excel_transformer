<?php

namespace App\Http\Controllers;

use App\Services\ExcelTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExcelTemplateController extends Controller
{
    public function __construct(
        private readonly ExcelTemplateService $templateService,
    ) {}

    public function show(): JsonResponse
    {
        return response()->json(
            $this->templateService->getFormData($this->templateService->getActive())
        );
    }

    public function upload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xls,xlsx', 'max:10240'],
        ]);

        try {
            $template = $this->templateService->upload($validated['file']);
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

    public function destroy(): JsonResponse
    {
        if (! $this->templateService->deleteActive()) {
            return response()->json(['message' => 'Активный шаблон не найден.'], 404);
        }

        return response()->json(['message' => 'Шаблон удалён.']);
    }
}
