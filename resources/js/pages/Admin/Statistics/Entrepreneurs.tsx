import { StatisticsScreen } from '@/components/statistics/StatisticsScreen';
import type { StatisticsPageProps } from '@/components/statistics/types';

export default function EntrepreneursStatistics(props: StatisticsPageProps) {
    return (
        <StatisticsScreen
            {...props}
            title="Statistiques — Entrepreneurs"
            headTitle="Admin — Statistiques entrepreneurs"
            breadcrumbLabel="Entrepreneurs"
        />
    );
}
