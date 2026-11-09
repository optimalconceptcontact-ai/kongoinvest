import { StatisticsScreen } from '@/components/statistics/StatisticsScreen';
import type { StatisticsPageProps } from '@/components/statistics/types';

export default function UsersStatistics(props: StatisticsPageProps) {
    return (
        <StatisticsScreen
            {...props}
            title="Statistiques — Utilisateurs"
            headTitle="Admin — Statistiques utilisateurs"
            breadcrumbLabel="Utilisateurs"
        />
    );
}
