<?php

namespace App\Services\Statistics\Exports;

use App\Services\Statistics\StatisticsReport;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class StatisticsCsvExporter
{
    public function __construct(private ReportCellFormatter $formatter) {}

    public function export(StatisticsReport $report): StreamedResponse
    {
        return response()->streamDownload(function () use ($report): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            // UTF-8 BOM so Excel opens French accents correctly.
            fwrite($handle, "\xEF\xBB\xBF");

            $this->row($handle, [$report->title]);
            $this->row($handle, ['Période', $report->period['label'] ?? '']);
            $this->row($handle, ['Comparaison', $report->period['previous_label'] ?? '']);
            $this->row($handle, ['Fuseau horaire', $report->period['timezone'] ?? '']);
            $this->row($handle, ['Généré le', $report->generatedAt->format('d/m/Y H:i')]);
            $this->row($handle, []);

            if ($report->filterLabels !== []) {
                $this->row($handle, ['Filtres actifs']);

                foreach ($report->filterLabels as $label => $value) {
                    $this->row($handle, [$label, $value]);
                }

                $this->row($handle, []);
            }

            $this->row($handle, ['Indicateurs']);
            $this->row($handle, ['Indicateur', 'Valeur', 'Variation', 'Période de comparaison', 'Note']);

            foreach ($report->kpis as $kpi) {
                $this->row($handle, [
                    $kpi['label'] ?? '',
                    $this->kpiValue($kpi),
                    $kpi['variation'] === null ? '—' : $this->percentage((float) $kpi['variation']),
                    $kpi['variation_label'] ?? '',
                    $kpi['hint'] ?? '',
                ]);
            }

            $this->row($handle, []);
            $this->row($handle, ['Détail']);
            $this->row($handle, array_map(
                fn (array $column) => $column['label'] ?? '',
                $report->tableColumns,
            ));

            foreach ($report->tableRows as $row) {
                $this->row($handle, array_map(
                    fn (array $column) => $this->formatter->format($column, $row),
                    $report->tableColumns,
                ));
            }

            if ($report->charts !== []) {
                $this->row($handle, []);
                $this->row($handle, ['Graphiques']);

                foreach ($report->charts as $chart) {
                    $this->row($handle, [$chart['title'] ?? '']);
                    $this->chart($handle, $chart);
                    $this->row($handle, []);
                }
            }

            fclose($handle);
        }, $this->filename($report), [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /** @param resource $handle */
    private function row($handle, array $values): void
    {
        $sanitized = array_map(function (mixed $value): mixed {
            if (! is_string($value)) {
                return $value;
            }

            // Prevent spreadsheet formula injection when the CSV is
            // opened in Excel/Sheets. User names and e-mails are data.
            return preg_match('/^[=+\-@]/', $value) === 1 ? "'".$value : $value;
        }, $values);

        fputcsv($handle, $sanitized, ';', '"', '');
    }

    /** @param resource $handle */
    private function chart($handle, array $chart): void
    {
        $data = $chart['data'] ?? [];
        $series = $chart['series'] ?? [];

        if ($data === []) {
            $this->row($handle, ['Aucune donnée']);

            return;
        }

        if ($series === []) {
            $this->row($handle, ['Élément', 'Valeur']);

            foreach ($data as $item) {
                $this->row($handle, [$item['name'] ?? '', $this->number((float) ($item['value'] ?? 0))]);
            }

            return;
        }

        $this->row($handle, array_merge(
            ['Période'],
            array_map(fn (array $definition) => $definition['label'] ?? $definition['key'] ?? '', $series),
        ));

        foreach ($data as $row) {
            $values = [$row['label'] ?? ''];

            foreach ($series as $definition) {
                $values[] = $this->number((float) ($row[$definition['key']] ?? 0));
            }

            $this->row($handle, $values);
        }
    }

    /** @param array<string, mixed> $kpi */
    private function kpiValue(array $kpi): string
    {
        $value = (float) ($kpi['value'] ?? 0);

        return match ($kpi['format'] ?? 'integer') {
            'currency' => number_format($value, 2, ',', ' ').' '.strtoupper((string) ($kpi['currency'] ?? '')),
            'percent' => number_format($value, 1, ',', ' ').' %',
            default => $this->number($value),
        };
    }

    private function number(float $value): string
    {
        return number_format($value, $value === floor($value) ? 0 : 2, ',', ' ');
    }

    private function percentage(float $value): string
    {
        return ($value > 0 ? '+' : '').number_format($value, 1, ',', ' ').' %';
    }

    private function filename(StatisticsReport $report): string
    {
        return 'statistiques-'.$report->key.'-'.$report->generatedAt->format('Y-m-d-Hi').'.csv';
    }
}
