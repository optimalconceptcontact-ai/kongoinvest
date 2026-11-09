<?php

namespace App\Services\Statistics\Exports;

use App\Services\Statistics\StatisticsReport;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class StatisticsExcelExporter
{
    public function __construct(private ReportCellFormatter $formatter) {}

    public function export(StatisticsReport $report): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rapport');

        $row = 1;
        $sheet->setCellValue("A{$row}", $report->title);
        $sheet->mergeCells("A{$row}:F{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14);
        $row += 2;

        $meta = [
            'Période' => $report->period['label'] ?? '',
            'Comparaison' => $report->period['previous_label'] ?? '',
            'Fuseau horaire' => $report->period['timezone'] ?? '',
            'Généré le' => $report->generatedAt->format('d/m/Y H:i'),
        ];

        foreach ($report->filterLabels as $label => $value) {
            $meta[$label] = $value;
        }

        foreach ($meta as $label => $value) {
            $sheet->setCellValue("A{$row}", $label);
            $sheet->setCellValue("B{$row}", $value);
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $row++;
        }

        $row++;

        $sheet->setCellValue("A{$row}", 'Indicateurs');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
        $row++;

        $headers = ['Indicateur', 'Valeur', 'Variation', 'Comparaison', 'Note'];
        $this->headerRow($sheet, $row, $headers);
        $row++;

        foreach ($report->kpis as $kpi) {
            $sheet->setCellValue("A{$row}", $kpi['label'] ?? '');
            $sheet->setCellValue("B{$row}", $kpi['value'] ?? 0);
            $sheet->setCellValue("C{$row}", $kpi['variation'] === null ? '—' : $this->percentage((float) $kpi['variation']));
            $sheet->setCellValue("D{$row}", $kpi['variation_label'] ?? '');
            $sheet->setCellValue("E{$row}", $kpi['hint'] ?? '');

            if (($kpi['format'] ?? 'integer') === 'currency') {
                $currency = strtoupper((string) ($kpi['currency'] ?? ''));

                if ($currency !== '') {
                    $sheet->getStyle("B{$row}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00 "'.$currency.'"');
                }
            }

            $row++;
        }

        $row++;
        $sheet->setCellValue("A{$row}", 'Détail');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
        $row++;

        $this->headerRow($sheet, $row, array_map(
            fn (array $column) => $column['label'] ?? '',
            $report->tableColumns,
        ));
        $row++;

        foreach ($report->tableRows as $tableRow) {
            $columnIndex = 1;

            foreach ($report->tableColumns as $column) {
                $sheet->setCellValueExplicit(
                    $this->columnLetter($columnIndex).$row,
                    $this->formatter->format($column, $tableRow),
                    DataType::TYPE_STRING,
                );
                $columnIndex++;
            }

            $row++;
        }

        foreach ($report->charts as $chart) {
            $row += 2;
            $sheet->setCellValue("A{$row}", $chart['title'] ?? '');
            $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
            $row++;
            $row = $this->chartRows($sheet, $row, $chart);
        }

        $sheet->freezePane('A4');
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(24);
        $sheet->getColumnDimension('C')->setWidth(16);
        $sheet->getColumnDimension('D')->setWidth(24);
        $sheet->getColumnDimension('E')->setWidth(32);

        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);

        return response()->streamDownload(function () use ($writer, $spreadsheet): void {
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $this->filename($report), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  array<int, string>  $headers
     */
    private function headerRow($sheet, int $row, array $headers): void
    {
        foreach ($headers as $index => $label) {
            $cell = $this->columnLetter($index + 1).$row;
            $sheet->setCellValue($cell, $label);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('F1F5F9');
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        }
    }

    /** @param array<string, mixed> $chart */
    private function chartRows($sheet, int $row, array $chart): int
    {
        $data = $chart['data'] ?? [];
        $series = $chart['series'] ?? [];

        if ($data === []) {
            $sheet->setCellValue("A{$row}", 'Aucune donnée');

            return $row + 1;
        }

        if ($series === []) {
            $this->headerRow($sheet, $row, ['Élément', 'Valeur']);
            $row++;

            foreach ($data as $item) {
                $sheet->setCellValue("A{$row}", $item['name'] ?? '');
                $sheet->setCellValue("B{$row}", (float) ($item['value'] ?? 0));
                $row++;
            }

            return $row;
        }

        $this->headerRow($sheet, $row, array_merge(
            ['Période'],
            array_map(fn (array $definition) => $definition['label'] ?? $definition['key'] ?? '', $series),
        ));
        $row++;

        foreach ($data as $chartRow) {
            $sheet->setCellValue("A{$row}", $chartRow['label'] ?? '');
            $columnIndex = 2;

            foreach ($series as $definition) {
                $sheet->setCellValue(
                    $this->columnLetter($columnIndex).$row,
                    (float) ($chartRow[$definition['key']] ?? 0),
                );
                $columnIndex++;
            }

            $row++;
        }

        return $row;
    }

    private function columnLetter(int $index): string
    {
        return Coordinate::stringFromColumnIndex($index);
    }

    private function percentage(float $value): string
    {
        return ($value > 0 ? '+' : '').number_format($value, 1, ',', ' ').' %';
    }

    private function filename(StatisticsReport $report): string
    {
        return 'statistiques-'.$report->key.'-'.$report->generatedAt->format('Y-m-d-Hi').'.xlsx';
    }
}
