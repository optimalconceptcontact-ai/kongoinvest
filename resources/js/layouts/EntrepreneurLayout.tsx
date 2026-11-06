import { usePage } from '@inertiajs/react';
import {
    RiBriefcase4Line,
    RiContractLine,
    RiDashboardLine,
    RiFileTextLine,
    RiHandCoinLine,
    RiMessage2Line,
    RiSettings3Line,
    RiShakeHandsLine,
    RiShieldCheckLine,
    RiVipCrownLine,
} from '@remixicon/react';
import type { ReactNode } from 'react';
import { DashboardLayout } from '@/layouts/DashboardLayout';

export function EntrepreneurLayout({
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
            roleLabel="Entrepreneur"
            navItems={[
                {
                    href: '/dashboard',
                    icon: RiDashboardLine,
                    label: 'Dashboard',
                    active: url === '/dashboard',
                },
                {
                    href: '/entrepreneur/projects',
                    icon: RiBriefcase4Line,
                    label: 'Mon projet',
                    active: is('/entrepreneur/projects'),
                },
                {
                    href: '/entrepreneur/matches',
                    icon: RiShakeHandsLine,
                    label: 'Matches IA',
                    active: is('/entrepreneur/matches'),
                },
                {
                    href: '/entrepreneur/connections',
                    icon: RiMessage2Line,
                    label: 'Connexions & Messages',
                    active:
                        is('/entrepreneur/connections') || is('/deal-rooms'),
                },
                {
                    href: '/entrepreneur/connections',
                    icon: RiHandCoinLine,
                    label: 'Deals & Escrow',
                    badge: 'voir Deal Room',
                },
                {
                    href: '/settings/kyc',
                    icon: RiShieldCheckLine,
                    label: 'KYC',
                    active: is('/settings/kyc'),
                },
                {
                    href: '/entrepreneur/contracts',
                    icon: RiContractLine,
                    label: 'Contrats',
                    active: is('/entrepreneur/contracts'),
                },
                {
                    href: '/settings/entrepreneur-profile',
                    icon: RiFileTextLine,
                    label: 'Profil',
                    active: is('/settings/entrepreneur-profile'),
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
