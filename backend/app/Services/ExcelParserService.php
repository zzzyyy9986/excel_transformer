<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExcelParserService
{
    private const HEADER_MARKERS = ['модель', 'размер', 'цвет'];
    private const MAX_SCAN_COL = 20;

    /**
     * Парсит Excel-шаблон заказа в структуру для веб-формы.
     *
     * @return array{
     *     title: string,
     *     price_label: string,
     *     default_tier_index: int,
     *     tiers: list<array{name: string, multiplier: float}>,
     *     groups: list<array{
     *         model_code: string,
     *         description: string,
     *         rows: list<array{
     *             row_index: int,
     *             model: string,
     *             size: string,
     *             color: string,
     *             barcode: string,
     *             base_price: float,
     *             tier_price: float,
     *             quantity: float,
     *             description: string,
     *             is_group_header: bool
     *         }>
     *     }>
     * }
     */
    public function parse(string $filePath): array
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getSheet(0);

        $headerRow = $this->findHeaderRow($sheet);
        if ($headerRow === null) {
            throw new \InvalidArgumentException('Не найдена строка заголовков (модель, размер, цвет).');
        }

        $columns = $this->mapColumns($sheet, $headerRow);
        $title = $this->cellString($sheet, 2, 1) ?: $this->cellString($sheet, 1, 1);
        $priceLabel = $this->cellString($sheet, 1, 5) ?: 'Выберите Ваш прайс ниже:';
        $defaultTierIndex = max(0, (int) round($this->cellFloat($sheet, 2, 12)));

        $tiers = $this->extractTiers($sheet, $headerRow);
        $groups = $this->extractProductGroups($sheet, $headerRow, $columns);

        return [
            'title' => $title,
            'price_label' => $priceLabel,
            'default_tier_index' => min($defaultTierIndex, max(count($tiers) - 1, 0)),
            'tiers' => $tiers,
            'groups' => $groups,
        ];
    }

    private function findHeaderRow(Worksheet $sheet): ?int
    {
        $maxRow = min($this->safeHighestRow($sheet), 30);

        for ($row = 1; $row <= $maxRow; $row++) {
            $values = [];
            for ($col = 1; $col <= self::MAX_SCAN_COL; $col++) {
                $values[] = mb_strtolower(trim($this->cellString($sheet, $row, $col)));
            }

            $matches = 0;
            foreach (self::HEADER_MARKERS as $marker) {
                if (in_array($marker, $values, true)) {
                    $matches++;
                }
            }

            if ($matches >= 2) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @return array{
     *     model: int,
     *     size: int,
     *     color: int,
     *     barcode: int|null,
     *     price: int,
     *     tier_price: int|null,
     *     quantity: int|null,
     *     description: int|null
     * }
     */
    private function mapColumns(Worksheet $sheet, int $headerRow): array
    {
        $map = [
            'model' => null,
            'size' => null,
            'color' => null,
            'barcode' => null,
            'price' => null,
            'tier_price' => null,
            'quantity' => null,
            'description' => null,
        ];

        for ($col = 1; $col <= self::MAX_SCAN_COL; $col++) {
            $header = mb_strtolower(trim($this->cellString($sheet, $headerRow, $col)));

            match (true) {
                $header === 'модель' => $map['model'] = $col,
                $header === 'размер' => $map['size'] = $col,
                $header === 'цвет' => $map['color'] = $col,
                $header === 'штрихкод' => $map['barcode'] = $col,
                $header === 'цена' => $map['price'] = $col,
                $header === 'сумма' => $map['quantity'] = $col,
                str_contains($header, 'описание') => $map['description'] = $col,
                $map['tier_price'] === null && $header !== '' && ! in_array($header, ['модель', 'размер', 'цвет', 'штрихкод', 'цена', 'сумма'], true)
                    => $map['tier_price'] = $col,
                default => null,
            };
        }

        if (! $map['model'] || ! $map['size'] || ! $map['color'] || ! $map['price']) {
            throw new \InvalidArgumentException('В шаблоне не хватает обязательных колонок: модель, размер, цвет, цена.');
        }

        return array_map(fn ($value) => $value ?? 0, $map);
    }

    /**
     * @return list<array{name: string, multiplier: float}>
     */
    private function extractTiers(Worksheet $sheet, int $headerRow): array
    {
        $tiers = [];
        $tierNameCol = 12;
        $tierMultiplierCol = 13;
        $maxRow = min($this->safeHighestRow($sheet), $headerRow + 15);

        for ($row = $headerRow; $row <= $maxRow; $row++) {
            $name = trim($this->cellString($sheet, $row, $tierNameCol));
            $multiplier = $this->cellFloat($sheet, $row, $tierMultiplierCol);

            if ($name === '' || ! preg_match('/[\p{L}]/u', $name)) {
                continue;
            }

            $tiers[] = [
                'name' => $name,
                'multiplier' => $multiplier > 0 ? $multiplier : 1.0,
            ];
        }

        if ($tiers === []) {
            $tiers[] = ['name' => 'Первый', 'multiplier' => 1.0];
        }

        return $tiers;
    }

    /**
     * @param array<string, int> $columns
     * @return list<array{model_code: string, description: string, rows: list<array<string, mixed>>}>
     */
    private function extractProductGroups(Worksheet $sheet, int $headerRow, array $columns): array
    {
        $groups = [];
        $currentGroup = null;
        $maxRow = $this->safeHighestRow($sheet);

        for ($row = $headerRow + 1; $row <= $maxRow; $row++) {
            $modelRaw = trim($this->cellString($sheet, $row, $columns['model']));
            if ($modelRaw === '') {
                continue;
            }

            $modelCode = ltrim($modelRaw, '_');
            $description = $columns['description']
                ? trim($this->cellString($sheet, $row, $columns['description']))
                : '';

            $rowData = [
                'row_index' => $row,
                'model' => $modelRaw,
                'size' => trim($this->cellString($sheet, $row, $columns['size'])),
                'color' => trim($this->cellString($sheet, $row, $columns['color'])),
                'barcode' => $columns['barcode']
                    ? trim($this->cellString($sheet, $row, $columns['barcode']))
                    : '',
                'base_price' => round($this->cellFloat($sheet, $row, $columns['price']), 2),
                'tier_price' => $columns['tier_price']
                    ? round($this->cellFloat($sheet, $row, $columns['tier_price']), 2)
                    : round($this->cellFloat($sheet, $row, $columns['price']), 2),
                'quantity' => $columns['quantity']
                    ? $this->cellFloat($sheet, $row, $columns['quantity'])
                    : 0,
                'description' => $description,
                'is_group_header' => false,
            ];

            if ($currentGroup === null || $currentGroup['model_code'] !== $modelCode) {
                if ($currentGroup !== null) {
                    $groups[] = $currentGroup;
                }

                $rowData['is_group_header'] = true;

                $currentGroup = [
                    'model_code' => $modelCode,
                    'description' => $description,
                    'rows' => [$rowData],
                ];
            } else {
                if ($description !== '' && $currentGroup['description'] === '') {
                    $currentGroup['description'] = $description;
                }
                $currentGroup['rows'][] = $rowData;
            }
        }

        if ($currentGroup !== null) {
            $groups[] = $currentGroup;
        }

        return $groups;
    }

    private function cellString(Worksheet $sheet, int $row, int $col): string
    {
        $value = $sheet->getCell([$col, $row])->getCalculatedValue();

        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    }

    private function cellFloat(Worksheet $sheet, int $row, int $col): float
    {
        $value = $sheet->getCell([$col, $row])->getCalculatedValue();

        if (is_numeric($value)) {
            return (float) $value;
        }

        $normalized = str_replace(',', '.', trim((string) $value));

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }

    private function safeHighestRow(Worksheet $sheet): int
    {
        $highest = (int) $sheet->getHighestDataRow();

        return $highest > 0 ? min($highest, 500) : 100;
    }
}
