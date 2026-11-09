import { StatisticsScreen } from '@/components/statistics/StatisticsScreen';
import type { StatisticsPageProps } from '@/components/statistics/types';

export default function RevenueStatistics(props: StatisticsPageProps) {
    return (
        <StatisticsScreen
            {...props}
            title="Statistiques — Revenus"
            headTitle="Admin — Statistiques revenus"
            breadcrumbLabel="Revenus"
        />
    );
}
