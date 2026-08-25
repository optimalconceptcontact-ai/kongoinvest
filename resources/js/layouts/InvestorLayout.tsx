import { Link, usePage } from '@inertiajs/react';
import {
    RiBriefcaseLine,
    RiCompass3Line,
    RiContractLine,
    RiDashboardLine,
    RiHandCoinLine,
    RiShakeHandsLine,
    RiMessage2Line,
    RiPieChartLine,
    RiSettings3Line,
    RiShieldCheckLine,
    RiVipCrownLine,
} from '@remixicon/react';
import type { ReactNode } from 'react';
import { NotificationBell } from '@/components/NotificationBell';
import { PageTransition } from '@/components/PageTransition';
import { SidebarLink } from '@/components/SidebarLink';
import { UserMenu } from '@/components/UserMenu';

type SharedAuth = { auth: { user: { name: string; email: string } | null } };

export function InvestorLayout({
    children,
    title,
}: {
    children: ReactNode;
    title: string;
}) {
    const { url } = usePage();
    const { props } = usePage<SharedAuth>();
    const is = (path: string) => url.startsWith(path);

    return (
        <div className="mx-auto flex max-w-6xl gap-8 px-6 py-10">
            <aside className="sticky top-10 flex h-[calc(100vh-5rem)] w-56 flex-shrink-0 flex-col">
                <div className="mb-6 flex items-center justify-between px-3">
                    <Link
                        href="/"
                        className="flex items-center gap-2 text-sm font-extrabold text-slate-900"
                    >
                        <span className="relative h-[20px] w-[20px] flex-shrink-0 bg-slate-900 after:absolute after:inset-[4px] after:bg-orange-500" />
                        KongoInvestment
                    </Link>
                    <NotificationBell align="left" />
                </div>
                <nav className="flex flex-1 flex-col gap-0.5 overflow-y-auto">
                    <SidebarLink
                        href="/dashboard"
                        icon={RiDashboardLine}
                        label="Dashboard"
                        active={url === '/dashboard'}
                    />
                    <SidebarLink
                        href="/projects"
                        icon={RiCompass3Line}
                        label="Découvrir"
                        active={is('/projects')}
                    />
                    <SidebarLink
                        href="/investor/matches"
                        icon={RiShakeHandsLine}
                        label="Matches IA"
                        active={is('/investor/matches')}
                    />
                    <SidebarLink
                        href="/investor/saved-projects"
                        icon={RiBriefcaseLine}
                        label="Projets sauvegardés"
                        active={is('/investor/saved-projects')}
                    />
                    <SidebarLink
                        href="/investor/connections"
                        icon={RiMessage2Line}
                        label="Connexions & Messages"
                        active={
                            is('/investor/connections') || is('/deal-rooms')
                        }
                    />
                    <SidebarLink
                        href="/investor/connections"
                        icon={RiHandCoinLine}
                        label="Investissements & Escrow"
                        badge="voir Deal Room"
                    />
                    <SidebarLink
                        href="/investor/portfolio"
                        icon={RiPieChartLine}
                        label="Portefeuille"
                        active={is('/investor/portfolio')}
                    />
                    <SidebarLink
                        href="/settings/kyc"
                        icon={RiShieldCheckLine}
                        label="KYC"
                        active={is('/settings/kyc')}
                    />
                    <SidebarLink
                        href="/investor/contracts"
                        icon={RiContractLine}
                        label="Contrats"
                        active={is('/investor/contracts')}
                    />
                    <SidebarLink
                        href="/settings/investor-profile"
                        icon={RiBriefcaseLine}
                        label="Profil investisseur"
                        active={is('/settings/investor-profile')}
                    />
                    <SidebarLink
                        href="/settings/subscription"
                        icon={RiVipCrownLine}
                        label="Abonnement"
                        active={is('/settings/subscription')}
                    />
                    <SidebarLink
                        href="/settings/profile"
                        icon={RiSettings3Line}
                        label="Compte"
                        active={is('/settings/profile')}
                    />
                </nav>
                <div className="mt-4 flex min-w-0 flex-shrink-0 items-center gap-2 border-t border-slate-300 px-3 pt-4">
                    {props.auth.user && (
                        <UserMenu
                            user={props.auth.user}
                            align="left"
                            direction="up"
                        />
                    )}
                    <div className="min-w-0">
                        <div className="text-eyebrow">Investisseur</div>
                        <div className="mt-1 truncate text-sm font-bold text-slate-900">
                            {props.auth.user?.name}
                        </div>
                    </div>
                </div>
            </aside>
            <main className="min-w-0 flex-1">
                <h1 className="mb-8 text-page-title">{title}</h1>
                <PageTransition>{children}</PageTransition>
            </main>
        </div>
    );
}
