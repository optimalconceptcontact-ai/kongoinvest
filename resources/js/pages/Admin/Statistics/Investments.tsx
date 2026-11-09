import { StatisticsScreen } from '@/components/statistics/StatisticsScreen';
import type { StatisticsPageProps } from '@/components/statistics/types';

export default function InvestmentsStatistics(props: StatisticsPageProps) {
    return (
        <StatisticsScreen
            {...props}
            title="Statistiques — Investissements"
            headTitle="Admin — Statistiques investissements"
            breadcrumbLabel="Investissements"
        />
    );
}
