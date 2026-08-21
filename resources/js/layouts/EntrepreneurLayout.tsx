import { Link, usePage } from '@inertiajs/react';
import {
    RiBriefcase4Line, RiContractLine, RiDashboardLine, RiFileTextLine, RiHandCoinLine,
    RiShakeHandsLine, RiMessage2Line, RiSettings3Line, RiShieldCheckLine, RiVipCrownLine,
} from '@remixicon/react';
import type { ReactNode } from 'react';
import { NotificationBell } from '@/components/NotificationBell';
import { SidebarLink } from '@/components/SidebarLink';
import { UserMenu } from '@/components/UserMenu';

type SharedAuth = { auth: { user: { name: string; email: string } | null } };

export function EntrepreneurLayout({ children, title }: { children: ReactNode; title: string }) {
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
                            <div className="text-eyebrow">Entrepreneur</div>
                            <div className="mt-1 truncate text-sm font-bold text-slate-900">{props.auth.user?.name}</div>
                        </div>
                    </div>
                    <NotificationBell />
                </div>
                <nav className="flex flex-col gap-0.5">
                    <SidebarLink href="/dashboard" icon={RiDashboardLine} label="Dashboard" active={url === '/dashboard'} />
                    <SidebarLink href="/entrepreneur/projects" icon={RiBriefcase4Line} label="Mon projet" active={is('/entrepreneur/projects')} />
                    <SidebarLink href="/entrepreneur/matches" icon={RiShakeHandsLine} label="Matches IA" active={is('/entrepreneur/matches')} />
                    <SidebarLink href="/entrepreneur/connections" icon={RiMessage2Line} label="Connexions & Messages" active={is('/entrepreneur/connections') || is('/deal-rooms')} />
                    <SidebarLink href="/entrepreneur/connections" icon={RiHandCoinLine} label="Deals & Escrow" badge="voir Deal Room" />
                    <SidebarLink href="/settings/kyc" icon={RiShieldCheckLine} label="KYC" active={is('/settings/kyc')} />
                    <SidebarLink href="#" icon={RiContractLine} label="Contrats" disabled badge="voir Deal Room" />
                    <SidebarLink href="/settings/entrepreneur-profile" icon={RiFileTextLine} label="Profil" active={is('/settings/entrepreneur-profile')} />
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
