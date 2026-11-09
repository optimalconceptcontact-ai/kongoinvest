<?php

namespace App\Services\Statistics\Exports;

use App\Services\Statistics\StatisticsReport;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Response;

final class StatisticsPdfExporter
{
    public function __construct(
        private StatisticsChartRenderer $chartRenderer,
        private ReportCellFormatter $formatter,
    ) {}

    public function export(StatisticsReport $report): Response
    {
        $chartSvgs = [];

        foreach ($report->charts as $chart) {
            $chartSvgs[(string) ($chart['key'] ?? '')] = $this->chartRenderer->render($chart);
        }

        $html = view('statistics.report', [
            'report' => $report,
            'chartSvgs' => $chartSvgs,
            'formatter' => $this->formatter,
        ])->render();

        if (! defined('DOMPDF_ENABLE_AUTOLOAD')) {
            define('DOMPDF_ENABLE_AUTOLOAD', false);
        }

        $options = new Options;
        $options->setIsRemoteEnabled(false);
        $options->setDefaultFont('DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->filename($report).'"',
        ]);
    }

    private function filename(StatisticsReport $report): string
    {
        return 'statistiques-'.$report->key.'-'.$report->generatedAt->format('Y-m-d-Hi').'.pdf';
    }
}
