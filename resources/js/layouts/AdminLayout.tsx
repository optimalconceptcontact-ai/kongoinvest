import { usePage } from '@inertiajs/react';
import {
    RiBriefcase4Line, RiContractLine, RiDashboardLine, RiFileList3Line, RiHandCoinLine,
    RiShakeHandsLine, RiQuestionLine, RiScales3Line, RiSettings3Line, RiShieldCheckLine,
    RiStarLine, RiUserLine, RiVipCrownLine, RiWalletLine,
} from '@remixicon/react';
import type { ReactNode } from 'react';
import { NotificationBell } from '@/components/NotificationBell';
import { SidebarLink } from '@/components/SidebarLink';
import { UserMenu } from '@/components/UserMenu';

type SharedAuth = { auth: { user: { name: string; email: string } | null } };

export function AdminLayout({ children, title }: { children: ReactNode; title: string }) {
    const { url } = usePage();
    const { props } = usePage<SharedAuth>();
    const is = (path: string) => url.startsWith(path);

    return (
        <div className="mx-auto flex max-w-7xl gap-8 px-6 py-10">
            <aside className="w-56 flex-shrink-0">
                <div className="mb-6 flex items-start justify-between px-3">
                    <div className="flex items-center gap-2">
                        {props.auth.user && <UserMenu user={props.auth.user} />}
                        <div>
                            <div className="text-eyebrow">Admin</div>
                            <div className="mt-1 truncate text-sm font-bold text-slate-900">{props.auth.user?.name}</div>
                        </div>
                    </div>
                    <NotificationBell />
                </div>
                <nav className="flex flex-col gap-0.5">
                    <SidebarLink href="/admin" icon={RiDashboardLine} label="Dashboard" active={url === '/admin'} />
                    <SidebarLink href="/admin/users" icon={RiUserLine} label="Utilisateurs" active={is('/admin/users')} />
                    <SidebarLink href="#" icon={RiBriefcase4Line} label="Projets" disabled badge="lecture seule" />
                    <SidebarLink href="/admin/matching-weights" icon={RiShakeHandsLine} label="Pondérations matching" active={is('/admin/matching-weights')} />
                    <SidebarLink href="#" icon={RiVipCrownLine} label="Abonnements" disabled badge="voir Dashboard" />
                    <SidebarLink href="#" icon={RiWalletLine} label="Paiements" disabled badge="vue admin à venir" />
                    <SidebarLink href="#" icon={RiHandCoinLine} label="Escrow" disabled badge="voir Deal Room" />
                    <SidebarLink href="/admin/kyc" icon={RiShieldCheckLine} label="KYC" active={is('/admin/kyc')} />
                    <SidebarLink href="#" icon={RiContractLine} label="Contrats" disabled badge="voir Deal Room" />
                    <SidebarLink href="/admin/disputes" icon={RiScales3Line} label="Litiges" active={is('/admin/disputes')} />
                    <div className="mt-4 px-3 font-mono text-[10px] uppercase tracking-widest text-slate-400">Contenu</div>
                    <SidebarLink href="/admin/sectors" icon={RiFileList3Line} label="Secteurs" active={is('/admin/sectors')} />
                    <SidebarLink href="/admin/stages" icon={RiFileList3Line} label="Stades" active={is('/admin/stages')} />
                    <SidebarLink href="/admin/partners" icon={RiShakeHandsLine} label="Partenaires" active={is('/admin/partners')} />
                    <SidebarLink href="/admin/testimonials" icon={RiStarLine} label="Témoignages" active={is('/admin/testimonials')} />
                    <SidebarLink href="/admin/faqs" icon={RiQuestionLine} label="FAQ" active={is('/admin/faqs')} />
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
