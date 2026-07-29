<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;

class ExcelImageExtractorService
{
    /**
     * @return array<int, array{left: string|null, right: string|null}>
     */
    public function extract(string $filePath, int $templateId): array
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getSheet(0);

        $imagesByRow = [];
        $directory = "template-images/{$templateId}";

        Storage::disk('public')->makeDirectory($directory);

        foreach ($sheet->getDrawingCollection() as $index => $drawing) {
            if (! $drawing instanceof MemoryDrawing) {
                continue;
            }

            [$columnLetters, $row] = Coordinate::coordinateFromString($drawing->getCoordinates());
            $columnIndex = Coordinate::columnIndexFromString($columnLetters);

            if (! in_array($columnIndex, [1, 11], true)) {
                continue;
            }

            $side = $columnIndex === 1 ? 'left' : 'right';
            $imageResource = $drawing->getImageResource();

            if ($imageResource === null) {
                continue;
            }

            $filename = sprintf('%s_row_%d_%d.jpg', $side, $row, $index);
            $relativePath = "{$directory}/{$filename}";
            $absolutePath = Storage::disk('public')->path($relativePath);

            imagejpeg($imageResource, $absolutePath, 90);

            $imagesByRow[$row] ??= ['left' => null, 'right' => null];
            $imagesByRow[$row][$side] = Storage::disk('public')->url($relativePath);
        }

        return $imagesByRow;
    }
}
