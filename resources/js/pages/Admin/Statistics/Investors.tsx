import { StatisticsScreen } from '@/components/statistics/StatisticsScreen';
import type { StatisticsPageProps } from '@/components/statistics/types';

export default function InvestorsStatistics(props: StatisticsPageProps) {
    return (
        <StatisticsScreen
            {...props}
            title="Statistiques — Investisseurs"
            headTitle="Admin — Statistiques investisseurs"
            breadcrumbLabel="Investisseurs"
        />
    );
}
