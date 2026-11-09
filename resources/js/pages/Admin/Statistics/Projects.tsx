import { StatisticsScreen } from '@/components/statistics/StatisticsScreen';
import type { StatisticsPageProps } from '@/components/statistics/types';

export default function ProjectsStatistics(props: StatisticsPageProps) {
    return (
        <StatisticsScreen
            {...props}
            title="Statistiques — Projets"
            headTitle="Admin — Statistiques projets"
            breadcrumbLabel="Projets"
        />
    );
}
