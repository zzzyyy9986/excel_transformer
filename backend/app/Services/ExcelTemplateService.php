<?php

namespace App\Services;

use App\Models\ExcelTemplate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ExcelTemplateService
{
    public function __construct(
        private readonly ExcelParserService $parser,
        private readonly ExcelImageExtractorService $imageExtractor,
    ) {}

    public function getActive(): ?ExcelTemplate
    {
        return ExcelTemplate::query()
            ->where('is_active', true)
            ->latest()
            ->first();
    }

    public function upload(UploadedFile $file): ExcelTemplate
    {
        $path = $file->store('templates', 'local');
        $fullPath = Storage::disk('local')->path($path);

        ExcelTemplate::query()->where('is_active', true)->update(['is_active' => false]);

        $template = ExcelTemplate::query()->create([
            'original_name' => $file->getClientOriginalName(),
            'storage_path' => $path,
            'parsed_data' => [],
            'is_active' => true,
        ]);

        try {
            $parsed = $this->parser->parse($fullPath);
            $imagesByRow = $this->imageExtractor->extract($fullPath, $template->id);
            $parsed['groups'] = $this->attachImagesToGroups($parsed['groups'], $imagesByRow);
            $template->update(['parsed_data' => $parsed]);
        } catch (\Throwable $exception) {
            $template->delete();
            Storage::disk('local')->delete($path);

            throw $exception;
        }

        return $template->fresh();
    }

    /**
     * @param list<array<string, mixed>> $groups
     * @param array<int, array{left: string|null, right: string|null}> $imagesByRow
     * @return list<array<string, mixed>>
     */
    private function attachImagesToGroups(array $groups, array $imagesByRow): array
    {
        foreach ($groups as &$group) {
            $headerRow = $group['rows'][0]['row_index'] ?? null;

            $images = $headerRow !== null ? ($imagesByRow[$headerRow] ?? null) : null;

            $group['header_row_index'] = $headerRow;
            $group['image_left_url'] = $images['left'] ?? null;
            $group['image_right_url'] = $images['right'] ?? null;
        }

        return $groups;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFormData(?ExcelTemplate $template): array
    {
        if ($template === null) {
            return [
                'template' => null,
                'form' => null,
            ];
        }

        return [
            'template' => [
                'id' => $template->id,
                'original_name' => $template->original_name,
                'uploaded_at' => $template->created_at?->toIso8601String(),
            ],
            'form' => $this->normalizeFormUrls($template->parsed_data),
        ];
    }

    /**
     * @param array<string, mixed> $form
     * @return array<string, mixed>
     */
    private function normalizeFormUrls(array $form): array
    {
        $baseUrl = rtrim(config('app.url'), '/');

        if (! isset($form['groups']) || ! is_array($form['groups'])) {
            return $form;
        }

        $form['groups'] = array_map(function (array $group) use ($baseUrl) {
            $group['image_left_url'] = $this->absoluteUrl($group['image_left_url'] ?? null, $baseUrl);
            $group['image_right_url'] = $this->absoluteUrl($group['image_right_url'] ?? null, $baseUrl);

            return $group;
        }, $form['groups']);

        return $form;
    }

    private function absoluteUrl(?string $url, string $baseUrl): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return $baseUrl.'/'.ltrim($url, '/');
    }
}
