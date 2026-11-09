<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DrcProvince;
use App\Http\Requests\Admin\StatisticsRequest;
use App\Http\Requests\Admin\UsersStatisticsRequest;
use App\Services\AuditLogger;
use App\Services\Statistics\Exports\StatisticsCsvExporter;
use App\Services\Statistics\Exports\StatisticsExcelExporter;
use App\Services\Statistics\Exports\StatisticsPdfExporter;
use App\Services\Statistics\UsersStatisticsService;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserStatisticsController extends StatisticsPageController
{
    public function __construct(
        UsersStatisticsService $statistics,
        AuditLogger $audit,
    ) {
        parent::__construct($statistics, $audit);
    }

    public function index(UsersStatisticsRequest $request): Response
    {
        return $this->page($request);
    }

    public function export(
        UsersStatisticsRequest $request,
        string $format,
        StatisticsCsvExporter $csv,
        StatisticsExcelExporter $excel,
        StatisticsPdfExporter $pdf,
    ): SymfonyResponse|StreamedResponse {
        return $this->download($request, $format, $csv, $excel, $pdf);
    }

    protected function component(): string
    {
        return 'Admin/Statistics/Users';
    }

    protected function path(): string
    {
        return '/admin/statistics/users';
    }

    protected function note(): string
    {
        return 'Les indicateurs de stock (utilisateurs, vérifications, profils) décrivent la situation actuelle ; les indicateurs de flux (nouveaux inscrits, actifs) couvrent la période sélectionnée.';
    }

    /** @return array<string, mixed> */
    protected function options(StatisticsRequest $request): array
    {
        return [
            'search_placeholder' => 'Nom ou e-mail',
            'filters' => [
                [
                    'key' => 'account_type',
                    'label' => 'Type de compte',
                    'all_label' => 'Tous',
                    'options' => [
                        ['value' => 'entrepreneur', 'label' => 'Entrepreneurs'],
                        ['value' => 'investor', 'label' => 'Investisseurs'],
                        ['value' => 'admin', 'label' => 'Administrateurs'],
                    ],
                ],
                [
                    'key' => 'account_status',
                    'label' => 'Statut du compte',
                    'all_label' => 'Tous',
                    'options' => [
                        ['value' => 'active', 'label' => 'Actifs'],
                        ['value' => 'inactive', 'label' => 'Inactifs'],
                    ],
                ],
                [
                    'key' => 'profile_status',
                    'label' => 'Profil',
                    'all_label' => 'Tous',
                    'options' => [
                        ['value' => 'complete', 'label' => 'Complets'],
                        ['value' => 'incomplete', 'label' => 'Incomplets'],
                    ],
                ],
                [
                    'key' => 'province',
                    'label' => 'Province',
                    'all_label' => 'Toutes',
                    'options' => array_map(
                        fn (DrcProvince $province) => ['value' => $province->value, 'label' => $province->label()],
                        DrcProvince::cases(),
                    ),
                ],
            ],
        ];
    }
}
