import { Head } from '@inertiajs/react';
import { AdminLayout } from '@/layouts/AdminLayout';
import { ExportActions } from './ExportActions';
import { StatisticsPage } from './StatisticsPage';
import type { StatisticsPageProps } from './types';

export function StatisticsScreen({
    title,
    headTitle,
    breadcrumbLabel,
    ...props
}: StatisticsPageProps & {
    title: string;
    headTitle: string;
    breadcrumbLabel: string;
}) {
    return (
        <AdminLayout
            title={title}
            breadcrumbs={[
                { label: 'Admin', href: '/admin' },
                { label: 'Statistiques', href: '/admin/statistics/users' },
                { label: breadcrumbLabel },
            ]}
            description={props.report.description}
            actions={
                <ExportActions
                    path={props.export_path}
                    filters={props.filters}
                    canExport={props.options.can_export}
                />
            }
        >
            <Head title={headTitle} />
            <StatisticsPage
                report={props.report}
                table={props.table}
                filters={props.filters}
                options={props.options}
                path={props.path}
                note={props.note ?? undefined}
            />
        </AdminLayout>
    );
}
