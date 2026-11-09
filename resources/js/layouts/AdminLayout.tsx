import { usePage } from '@inertiajs/react';
import {
    RiBarChartLine,
    RiBookOpenLine,
    RiBriefcase4Line,
    RiContractLine,
    RiDashboardLine,
    RiFileList3Line,
    RiHandCoinLine,
    RiQuestionLine,
    RiScales3Line,
    RiSettings3Line,
    RiShakeHandsLine,
    RiShieldCheckLine,
    RiStarLine,
    RiUserLine,
    RiVipCrownLine,
    RiWalletLine,
} from '@remixicon/react';
import type { ReactNode } from 'react';
import { DashboardLayout } from '@/layouts/DashboardLayout';
import type { DashboardBreadcrumb } from '@/layouts/DashboardLayout';

export function AdminLayout({
    children,
    title,
    breadcrumbs,
    description,
    actions,
}: {
    children: ReactNode;
    title: string;
    breadcrumbs?: DashboardBreadcrumb[];
    description?: string;
    actions?: ReactNode;
}) {
    const { url } = usePage();
    const is = (path: string) => url.startsWith(path);

    return (
        <DashboardLayout
            title={title}
            roleLabel="Admin"
            maxWidth="7xl"
            breadcrumbs={breadcrumbs}
            description={description}
            actions={actions}
            navItems={[
                {
                    href: '/admin',
                    icon: RiDashboardLine,
                    label: 'Dashboard',
                    active: url === '/admin',
                },
                {
                    href: '/admin/statistics/users',
                    icon: RiBarChartLine,
                    label: 'Statistiques',
                    active: is('/admin/statistics'),
                },
                {
                    href: '/admin/users',
                    icon: RiUserLine,
                    label: 'Utilisateurs',
                    active: is('/admin/users'),
                },
                {
                    href: '/admin/projects',
                    icon: RiBriefcase4Line,
                    label: 'Projets',
                    active: is('/admin/projects'),
                    badge: 'modération',
                },
                {
                    href: '/admin/matching-weights',
                    icon: RiShakeHandsLine,
                    label: 'Pondérations matching',
                    active: is('/admin/matching-weights'),
                },
                {
                    href: '/admin/subscriptions',
                    icon: RiVipCrownLine,
                    label: 'Abonnements',
                    active: is('/admin/subscriptions'),
                },
                {
                    href: '/admin/payments',
                    icon: RiWalletLine,
                    label: 'Paiements',
                    active: is('/admin/payments'),
                },
                {
                    href: '/admin/escrow',
                    icon: RiHandCoinLine,
                    label: 'Escrow',
                    active: is('/admin/escrow'),
                },
                {
                    href: '/admin/compliance',
                    icon: RiShieldCheckLine,
                    label: 'Conformité & AML',
                    active: is('/admin/compliance'),
                },
                {
                    href: '/admin/kyc',
                    icon: RiShieldCheckLine,
                    label: 'KYC',
                    active: is('/admin/kyc'),
                },
                {
                    href: '/admin/contracts',
                    icon: RiContractLine,
                    label: 'Contrats',
                    active: is('/admin/contracts'),
                },
                {
                    href: '/admin/disputes',
                    icon: RiScales3Line,
                    label: 'Litiges',
                    active: is('/admin/disputes'),
                },
                {
                    href: '/admin/sectors',
                    icon: RiFileList3Line,
                    label: 'Secteurs',
                    section: 'Contenu',
                    active: is('/admin/sectors'),
                },
                {
                    href: '/admin/stages',
                    icon: RiFileList3Line,
                    label: 'Stades',
                    active: is('/admin/stages'),
                },
                {
                    href: '/admin/partners',
                    icon: RiShakeHandsLine,
                    label: 'Partenaires',
                    active: is('/admin/partners'),
                },
                {
                    href: '/admin/testimonials',
                    icon: RiStarLine,
                    label: 'Témoignages',
                    active: is('/admin/testimonials'),
                },
                {
                    href: '/admin/faqs',
                    icon: RiQuestionLine,
                    label: 'FAQ',
                    active: is('/admin/faqs'),
                },
                {
                    href: '/admin/guides',
                    icon: RiBookOpenLine,
                    label: 'Guide',
                    active: is('/admin/guides'),
                },
                {
                    href: '/settings/profile',
                    icon: RiSettings3Line,
                    label: 'Compte',
                    active: is('/settings/profile'),
                },
            ]}
        >
            {children}
        </DashboardLayout>
    );
}
