import { usePage } from '@inertiajs/react';
import {
    RiBriefcaseLine,
    RiCompass3Line,
    RiContractLine,
    RiDashboardLine,
    RiHandCoinLine,
    RiMessage2Line,
    RiPieChartLine,
    RiSettings3Line,
    RiShakeHandsLine,
    RiShieldCheckLine,
    RiVipCrownLine,
} from '@remixicon/react';
import type { ReactNode } from 'react';
import { DashboardLayout } from '@/layouts/DashboardLayout';

export function InvestorLayout({
    children,
    title,
}: {
    children: ReactNode;
    title: string;
}) {
    const { url } = usePage();
    const is = (path: string) => url.startsWith(path);

    return (
        <DashboardLayout
            title={title}
            roleLabel="Investisseur"
            navItems={[
                {
                    href: '/dashboard',
                    icon: RiDashboardLine,
                    label: 'Dashboard',
                    active: url === '/dashboard',
                },
                {
                    href: '/projects',
                    icon: RiCompass3Line,
                    label: 'Découvrir',
                    active: is('/projects'),
                },
                {
                    href: '/investor/matches',
                    icon: RiShakeHandsLine,
                    label: 'Matches IA',
                    active: is('/investor/matches'),
                },
                {
                    href: '/investor/saved-projects',
                    icon: RiBriefcaseLine,
                    label: 'Projets sauvegardés',
                    active: is('/investor/saved-projects'),
                },
                {
                    href: '/investor/connections',
                    icon: RiMessage2Line,
                    label: 'Connexions & Messages',
                    active: is('/investor/connections') || is('/deal-rooms'),
                },
                {
                    href: '/investor/connections',
                    icon: RiHandCoinLine,
                    label: 'Investissements & Escrow',
                    badge: 'voir Deal Room',
                },
                {
                    href: '/investor/portfolio',
                    icon: RiPieChartLine,
                    label: 'Portefeuille',
                    active: is('/investor/portfolio'),
                },
                {
                    href: '/settings/kyc',
                    icon: RiShieldCheckLine,
                    label: 'KYC',
                    active: is('/settings/kyc'),
                },
                {
                    href: '/investor/contracts',
                    icon: RiContractLine,
                    label: 'Contrats',
                    active: is('/investor/contracts'),
                },
                {
                    href: '/settings/investor-profile',
                    icon: RiBriefcaseLine,
                    label: 'Profil investisseur',
                    active: is('/settings/investor-profile'),
                },
                {
                    href: '/settings/subscription',
                    icon: RiVipCrownLine,
                    label: 'Abonnement',
                    active: is('/settings/subscription'),
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
