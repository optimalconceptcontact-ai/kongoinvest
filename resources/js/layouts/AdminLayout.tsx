import { Link, usePage } from '@inertiajs/react';
import {
    RiBriefcase4Line,
    RiContractLine,
    RiDashboardLine,
    RiFileList3Line,
    RiHandCoinLine,
    RiShakeHandsLine,
    RiQuestionLine,
    RiScales3Line,
    RiSettings3Line,
    RiShieldCheckLine,
    RiStarLine,
    RiUserLine,
    RiVipCrownLine,
    RiWalletLine,
} from '@remixicon/react';
import type { ReactNode } from 'react';
import { NotificationBell } from '@/components/NotificationBell';
import { PageTransition } from '@/components/PageTransition';
import { SidebarLink } from '@/components/SidebarLink';
import { UserMenu } from '@/components/UserMenu';

type SharedAuth = { auth: { user: { name: string; email: string } | null } };

export function AdminLayout({
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
        <div className="mx-auto flex max-w-7xl gap-8 px-6 py-10">
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
                        href="/admin"
                        icon={RiDashboardLine}
                        label="Dashboard"
                        active={url === '/admin'}
                    />
                    <SidebarLink
                        href="/admin/users"
                        icon={RiUserLine}
                        label="Utilisateurs"
                        active={is('/admin/users')}
                    />
                    <SidebarLink
                        href="/admin/projects"
                        icon={RiBriefcase4Line}
                        label="Projets"
                        active={is('/admin/projects')}
                        badge="lecture seule"
                    />
                    <SidebarLink
                        href="/admin/matching-weights"
                        icon={RiShakeHandsLine}
                        label="Pondérations matching"
                        active={is('/admin/matching-weights')}
                    />
                    <SidebarLink
                        href="/admin/subscriptions"
                        icon={RiVipCrownLine}
                        label="Abonnements"
                        active={is('/admin/subscriptions')}
                    />
                    <SidebarLink
                        href="/admin/payments"
                        icon={RiWalletLine}
                        label="Paiements"
                        active={is('/admin/payments')}
                    />
                    <SidebarLink
                        href="/admin/escrow"
                        icon={RiHandCoinLine}
                        label="Escrow"
                        active={is('/admin/escrow')}
                    />
                    <SidebarLink
                        href="/admin/kyc"
                        icon={RiShieldCheckLine}
                        label="KYC"
                        active={is('/admin/kyc')}
                    />
                    <SidebarLink
                        href="/admin/contracts"
                        icon={RiContractLine}
                        label="Contrats"
                        active={is('/admin/contracts')}
                    />
                    <SidebarLink
                        href="/admin/disputes"
                        icon={RiScales3Line}
                        label="Litiges"
                        active={is('/admin/disputes')}
                    />
                    <div className="mt-4 px-3 font-mono text-[10px] tracking-widest text-slate-400 uppercase">
                        Contenu
                    </div>
                    <SidebarLink
                        href="/admin/sectors"
                        icon={RiFileList3Line}
                        label="Secteurs"
                        active={is('/admin/sectors')}
                    />
                    <SidebarLink
                        href="/admin/stages"
                        icon={RiFileList3Line}
                        label="Stades"
                        active={is('/admin/stages')}
                    />
                    <SidebarLink
                        href="/admin/partners"
                        icon={RiShakeHandsLine}
                        label="Partenaires"
                        active={is('/admin/partners')}
                    />
                    <SidebarLink
                        href="/admin/testimonials"
                        icon={RiStarLine}
                        label="Témoignages"
                        active={is('/admin/testimonials')}
                    />
                    <SidebarLink
                        href="/admin/faqs"
                        icon={RiQuestionLine}
                        label="FAQ"
                        active={is('/admin/faqs')}
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
                        <div className="text-eyebrow">Admin</div>
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
