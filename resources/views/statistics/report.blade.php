<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $report->title }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 0;
            font-family: 'DejaVu Sans', sans-serif;
            color: #0e1726;
            font-size: 10px;
            line-height: 1.45;
        }
        .report-header {
            border-bottom: 2px solid #0e1726;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .eyebrow {
            font-size: 8px;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: #0f766e;
            margin-bottom: 4px;
        }
        h1 {
            font-size: 20px;
            margin: 0 0 4px;
        }
        .description {
            color: #4a5568;
            font-size: 10px;
            margin: 0;
        }
        .meta {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0 16px;
        }
        .meta td {
            border: 1px solid #cbd5e1;
            padding: 5px 8px;
            font-size: 9px;
        }
        .meta td.label {
            background: #f1f5f9;
            font-weight: bold;
            width: 16%;
        }
        h2 {
            font-size: 13px;
            margin: 18px 0 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #cbd5e1;
        }
        .kpis {
            width: 100%;
            border-collapse: collapse;
        }
        .kpis td {
            border: 1px solid #cbd5e1;
            padding: 7px 8px;
            vertical-align: top;
            width: 20%;
        }
        .kpi-label {
            color: #4a5568;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .kpi-value {
            font-size: 16px;
            font-weight: bold;
            margin-top: 2px;
        }
        .kpi-variation {
            font-size: 8px;
            color: #4a5568;
            margin-top: 2px;
        }
        .chart-block {
            margin: 10px 0 16px;
            page-break-inside: avoid;
        }
        .chart-title {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .chart-description {
            color: #4a5568;
            font-size: 8px;
            margin-bottom: 4px;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
        }
        table.data th,
        table.data td {
            border: 1px solid #cbd5e1;
            padding: 4px 6px;
            text-align: left;
            font-size: 8px;
        }
        table.data th {
            background: #f1f5f9;
            font-weight: bold;
        }
        .footer {
            margin-top: 16px;
            padding-top: 8px;
            border-top: 1px solid #cbd5e1;
            color: #4a5568;
            font-size: 8px;
        }
    </style>
</head>
<body>
    <div class="report-header">
        <div class="eyebrow">KongoInvest — Statistiques</div>
        <h1>{{ $report->title }}</h1>
        <p class="description">{{ $report->description }}</p>
    </div>

    <table class="meta">
        <tr>
            <td class="label">Période</td>
            <td>{{ $report->period['label'] ?? '' }}</td>
            <td class="label">Comparaison</td>
            <td>{{ $report->period['previous_label'] ?? '' }}</td>
            <td class="label">Fuseau</td>
            <td>{{ $report->period['timezone'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Généré le</td>
            <td>{{ $report->generatedAt->format('d/m/Y H:i') }}</td>
            <td class="label">Lignes</td>
            <td>{{ count($report->tableRows) }}</td>
            <td class="label">Granularité</td>
            <td>{{ ($report->period['granularity'] ?? 'day') === 'month' ? 'Mois' : 'Jour' }}</td>
        </tr>
    </table>

    @if (count($report->filterLabels) > 0)
        <h2>Filtres actifs</h2>
        <table class="meta">
            @foreach ($report->filterLabels as $label => $value)
                <tr>
                    <td class="label">{{ $label }}</td>
                    <td>{{ $value }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <h2>Indicateurs clés</h2>
    <table class="kpis">
        @foreach (array_chunk($report->kpis, 4) as $chunk)
            <tr>
                @foreach ($chunk as $kpi)
                    <td>
                        <div class="kpi-label">{{ $kpi['label'] }}</div>
                        <div class="kpi-value">
                            @if (($kpi['format'] ?? 'integer') === 'currency')
                                {{ number_format((float) $kpi['value'], ((float) $kpi['value'] === floor((float) $kpi['value'])) ? 0 : 2, ',', ' ') }} {{ $kpi['currency'] ?? '' }}
                            @elseif (($kpi['format'] ?? 'integer') === 'percent')
                                {{ number_format((float) $kpi['value'], 1, ',', ' ') }} %
                            @else
                                {{ number_format((float) $kpi['value'], 0, ',', ' ') }}
                            @endif
                        </div>
                        @if (($kpi['variation'] ?? null) !== null)
                            <div class="kpi-variation">
                                {{ $kpi['variation'] > 0 ? '+' : '' }}{{ number_format((float) $kpi['variation'], 1, ',', ' ') }} %
                                @if (! empty($kpi['variation_label']))
                                    vs {{ $kpi['variation_label'] }}
                                @endif
                            </div>
                        @elseif (! empty($kpi['hint']))
                            <div class="kpi-variation">{{ $kpi['hint'] }}</div>
                        @endif
                    </td>
                @endforeach

                @for ($i = count($chunk); $i < 4; $i++)
                    <td></td>
                @endfor
            </tr>
        @endforeach
    </table>

    @if (count($report->charts) > 0)
        <h2>Graphiques</h2>

        @foreach ($report->charts as $chart)
            <div class="chart-block">
                <div class="chart-title">{{ $chart['title'] ?? '' }}</div>
                <div class="chart-description">{{ $chart['description'] ?? '' }}</div>
                {!! $chartSvgs[$chart['key'] ?? ''] ?? '' !!}
            </div>
        @endforeach
    @endif

    <h2>Détail</h2>
    <table class="data">
        <thead>
            <tr>
                @foreach ($report->tableColumns as $column)
                    <th>{{ $column['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($report->tableRows as $row)
                <tr>
                    @foreach ($report->tableColumns as $column)
                        <td>{{ $formatter->format($column, $row) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($report->tableColumns) }}">Aucune donnée sur la période sélectionnée.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Document généré automatiquement depuis la base de données KongoInvest le
        {{ $report->generatedAt->format('d/m/Y à H:i') }} ({{ $report->period['timezone'] ?? '' }}).
        Les montants multi-devises ne sont jamais additionnés.
    </div>
</body>
</html>
