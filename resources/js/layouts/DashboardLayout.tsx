import { Link, usePage } from '@inertiajs/react';
import { RiMenuLine } from '@remixicon/react';
import type { RemixiconComponentType } from '@remixicon/react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import { Modal } from '@/components/Modal';
import { NotificationBell } from '@/components/NotificationBell';
import { PageTransition } from '@/components/PageTransition';
import { SidebarLink } from '@/components/SidebarLink';
import { UserMenu } from '@/components/UserMenu';

type SharedAuth = {
    auth: { user: { name: string; email: string } | null };
};

export type DashboardNavItem = {
    href: string;
    icon: RemixiconComponentType;
    label: string;
    active?: boolean;
    disabled?: boolean;
    badge?: string;
    section?: string;
};

function Wordmark() {
    return (
        <Link
            href="/"
            className="flex items-center gap-2.5 font-mono text-xl font-bold tracking-[0.06em] text-ink uppercase"
        >
            <span className="h-2.5 w-2.5 flex-shrink-0 bg-signal" />
            <span>KongoInvest</span>
        </Link>
    );
}

export function DashboardLayout({
    children,
    title,
    roleLabel,
    navItems,
    maxWidth = '6xl',
}: {
    children: ReactNode;
    title: string;
    roleLabel: string;
    navItems: DashboardNavItem[];
    maxWidth?: '6xl' | '7xl';
}) {
    const { props } = usePage<SharedAuth>();
    const [mobileNavOpen, setMobileNavOpen] = useState(false);
    const widthClass = maxWidth === '7xl' ? 'max-w-7xl' : 'max-w-6xl';

    return (
        <div className="min-h-screen bg-page">
            <header className="sticky top-0 z-40 border-b border-rule bg-page/95 backdrop-blur md:hidden">
                <div className="flex h-14 items-center justify-between px-4">
                    <Wordmark />
                    <div className="flex items-center gap-1">
                        <NotificationBell />
                        {props.auth.user && <UserMenu user={props.auth.user} />}
                        <button
                            type="button"
                            onClick={() => setMobileNavOpen(true)}
                            aria-label="Ouvrir la navigation"
                            aria-expanded={mobileNavOpen}
                            className="flex h-11 w-11 cursor-pointer items-center justify-center text-ink-muted hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-action"
                        >
                            <RiMenuLine size={22} />
                        </button>
                    </div>
                </div>
            </header>

            <div className={`mx-auto flex ${widthClass} md:px-6`}>
                <aside className="sticky top-0 hidden h-screen w-56 flex-shrink-0 flex-col border-r border-rule py-8 pr-4 md:flex">
                    <div className="mb-8 flex items-center justify-between px-3">
                        <Wordmark />
                        <NotificationBell align="left" />
                    </div>
                    <nav className="flex flex-1 flex-col gap-0.5 overflow-y-auto">
                        {navItems.map((item) => (
                            <div
                                key={`${item.section ?? ''}-${item.href}-${item.label}`}
                            >
                                {item.section && (
                                    <div className="mt-5 mb-1 px-3 text-eyebrow">
                                        {item.section}
                                    </div>
                                )}
                                <SidebarLink {...item} />
                            </div>
                        ))}
                    </nav>
                    <div className="mt-4 flex min-w-0 flex-shrink-0 items-center gap-2 border-t border-rule px-3 pt-4">
                        {props.auth.user && (
                            <UserMenu
                                user={props.auth.user}
                                align="left"
                                direction="up"
                                showDashboardLink={false}
                            />
                        )}
                        <div className="min-w-0">
                            <div className="font-mono text-lg tracking-wide text-ink-muted uppercase">
                                {roleLabel}
                            </div>
                            {props.auth.user && (
                                <div className="mt-0.5 truncate text-lg font-semibold text-ink">
                                    {props.auth.user.name}
                                </div>
                            )}
                        </div>
                    </div>
                </aside>

                <main className="min-w-0 flex-1 px-4 py-6 md:py-10 md:pl-8">
                    <div className="mb-6 border-b border-rule pb-5 md:mb-8">
                        <div className="text-eyebrow">{roleLabel}</div>
                        <h1 className="mt-2 text-page-title">{title}</h1>
                    </div>
                    <PageTransition>{children}</PageTransition>
                </main>
            </div>

            <Modal
                open={mobileNavOpen}
                onClose={() => setMobileNavOpen(false)}
                title="Navigation"
                maxWidth="md"
            >
                <div className="mb-4 border-b border-rule pb-3">
                    <div className="text-eyebrow">{roleLabel}</div>
                    {props.auth.user && (
                        <div className="mt-1 text-xl font-semibold text-ink">
                            {props.auth.user.name}
                        </div>
                    )}
                </div>
                <nav className="flex flex-col gap-0.5">
                    {navItems.map((item) => (
                        <div
                            key={`${item.section ?? ''}-${item.href}-${item.label}`}
                        >
                            {item.section && (
                                <div className="mt-3 mb-1 px-3 font-mono text-lg font-medium tracking-[0.16em] text-ink-muted uppercase">
                                    {item.section}
                                </div>
                            )}
                            <SidebarLink
                                {...item}
                                expand
                                onClick={() => setMobileNavOpen(false)}
                            />
                        </div>
                    ))}
                </nav>
            </Modal>
        </div>
    );
}
