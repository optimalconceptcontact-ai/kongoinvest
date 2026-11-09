<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StatisticsRequest;
use App\Services\AuditLogger;
use App\Services\Statistics\Exports\StatisticsCsvExporter;
use App\Services\Statistics\Exports\StatisticsExcelExporter;
use App\Services\Statistics\Exports\StatisticsPdfExporter;
use App\Services\Statistics\StatisticsService;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Shared page + export pipeline for the six analytics pages. Each concrete
 * controller only declares its component, path, filter fields and option
 * lists; the report, table, validation and export contract stay identical
 * everywhere so a file can never disagree with the screen.
 */
abstract class StatisticsPageController extends Controller
{
    public function __construct(
        protected StatisticsService $statistics,
        protected AuditLogger $audit,
    ) {}

    protected function page(StatisticsRequest $request): Response
    {
        $period = $request->period();
        $filters = $request->filters();

        $report = $this->statistics->report($period, $filters);

        return Inertia::render($this->component(), [
            'report' => $report->toArray(),
            'table' => $this->statistics->paginatedTable($filters),
            'filters' => $this->activeFilters($request, $filters),
            'options' => array_merge($this->options($request), [
                'can_export' => (bool) $request->user()?->can('statistics.export'),
            ]),
            'path' => $this->path(),
            'export_path' => $this->path().'/export',
            'note' => $this->note(),
        ]);
    }

    protected function download(
        StatisticsRequest $request,
        string $format,
        StatisticsCsvExporter $csv,
        StatisticsExcelExporter $excel,
        StatisticsPdfExporter $pdf,
    ): SymfonyResponse|StreamedResponse {
        $period = $request->period();
        $filters = $request->filters();
        $report = $this->statistics->report($period, $filters, withRows: true);

        $this->audit->log($request->user(), 'statistics.export', null, [
            'page' => $this->statistics::KEY,
            'format' => $format,
            'period' => $period->toArray(),
            'filters' => $filters,
            'rows' => count($report->tableRows),
        ]);

        return match ($format) {
            'xlsx' => $excel->export($report),
            'pdf' => $pdf->export($report),
            default => $csv->export($report),
        };
    }

    abstract protected function component(): string;

    abstract protected function path(): string;

    abstract protected function note(): string;

    /** @return array<string, mixed> */
    protected function options(StatisticsRequest $request): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function activeFilters(StatisticsRequest $request, array $filters): array
    {
        $periodInput = array_filter(
            $request->periodInput(),
            fn (mixed $value) => $value !== null && $value !== '',
        );

        return array_merge(
            $periodInput,
            array_filter($filters, fn (mixed $value) => $value !== null && $value !== ''),
        );
    }

    /**
     * @param  class-string  $enum
     * @return array<int, array{value: string, label: string}>
     */
    protected function enumOptions(string $enum): array
    {
        return array_map(
            fn ($case) => ['value' => $case->value, 'label' => $case->label()],
            $enum::cases(),
        );
    }
}
