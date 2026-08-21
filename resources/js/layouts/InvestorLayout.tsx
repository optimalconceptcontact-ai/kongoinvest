import { usePage } from '@inertiajs/react';
import {
    RiBriefcaseLine, RiCompass3Line, RiContractLine, RiDashboardLine, RiHandCoinLine,
    RiShakeHandsLine, RiMessage2Line, RiPieChartLine, RiSettings3Line, RiShieldCheckLine, RiVipCrownLine,
} from '@remixicon/react';
import type { ReactNode } from 'react';
import { NotificationBell } from '@/components/NotificationBell';
import { SidebarLink } from '@/components/SidebarLink';
import { UserMenu } from '@/components/UserMenu';

type SharedAuth = { auth: { user: { name: string; email: string } | null } };

export function InvestorLayout({ children, title }: { children: ReactNode; title: string }) {
    const { url } = usePage();
    const { props } = usePage<SharedAuth>();
    const is = (path: string) => url.startsWith(path);

    return (
        <div className="mx-auto flex max-w-6xl gap-8 px-6 py-10">
            <aside className="w-56 flex-shrink-0">
                <div className="mb-6 flex items-start justify-between px-3">
                    <div className="flex items-center gap-2">
                        {props.auth.user && <UserMenu user={props.auth.user} />}
                        <div>
                            <div className="text-eyebrow">Investisseur</div>
                            <div className="mt-1 truncate text-sm font-bold text-slate-900">{props.auth.user?.name}</div>
                        </div>
                    </div>
                    <NotificationBell />
                </div>
                <nav className="flex flex-col gap-0.5">
                    <SidebarLink href="/dashboard" icon={RiDashboardLine} label="Dashboard" active={url === '/dashboard'} />
                    <SidebarLink href="/projects" icon={RiCompass3Line} label="Découvrir" active={is('/projects')} />
                    <SidebarLink href="/investor/matches" icon={RiShakeHandsLine} label="Matches IA" active={is('/investor/matches')} />
                    <SidebarLink href="#" icon={RiBriefcaseLine} label="Projets sauvegardés" disabled badge="Phase 8+" />
                    <SidebarLink href="/investor/connections" icon={RiMessage2Line} label="Connexions & Messages" active={is('/investor/connections') || is('/deal-rooms')} />
                    <SidebarLink href="/investor/connections" icon={RiHandCoinLine} label="Investissements & Escrow" badge="voir Deal Room" />
                    <SidebarLink href="#" icon={RiPieChartLine} label="Portefeuille" disabled badge="Phase 11+" />
                    <SidebarLink href="/settings/kyc" icon={RiShieldCheckLine} label="KYC" active={is('/settings/kyc')} />
                    <SidebarLink href="#" icon={RiContractLine} label="Contrats" disabled badge="voir Deal Room" />
                    <SidebarLink href="/settings/investor-profile" icon={RiBriefcaseLine} label="Profil investisseur" active={is('/settings/investor-profile')} />
                    <SidebarLink href="/settings/subscription" icon={RiVipCrownLine} label="Abonnement" active={is('/settings/subscription')} />
                    <SidebarLink href="/settings/profile" icon={RiSettings3Line} label="Compte" active={is('/settings/profile')} />
                </nav>
            </aside>
            <main className="min-w-0 flex-1">
                <h1 className="mb-8 text-page-title">{title}</h1>
                {children}
            </main>
        </div>
    );
}
