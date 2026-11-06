import { Link, usePage } from '@inertiajs/react';
import { RiArrowDownSLine, RiCloseLine, RiMenuLine } from '@remixicon/react';
import { AnimatePresence, motion } from 'framer-motion';
import { useEffect, useRef, useState } from 'react';
import type { ReactNode } from 'react';
import { destroy } from '@/actions/Laravel/Fortify/Http/Controllers/AuthenticatedSessionController';
import { NotificationBell } from '@/components/NotificationBell';
import { PageTransition } from '@/components/PageTransition';
import { ThemeSwitcher } from '@/components/ThemeSwitcher';
import { UserMenu } from '@/components/UserMenu';
import { useAuthModal } from '@/hooks/useAuthModal';
import { fadeIn, slideInRight } from '@/lib/animations';
import { DRC_PROVINCES } from '@/lib/drcProvinces';

type SharedAuth = {
    auth: {
        user: { id: number; name: string; email: string } | null;
        roles: string[];
    };
};

const infoLinks = [
    { href: '/faq', label: 'FAQ' },
    { href: '/about', label: 'À propos' },
    { href: '/terms', label: 'Conditions' },
    { href: '/privacy', label: 'Confidentialité' },
];

/**
 * Spec §81: desktop gets a persistent top nav, mobile gets a drawer —
 * this is the "normal" (non-dashboard) counterpart to
 * Entrepreneur/Investor/AdminLayout's sidebars. Every public page
 * (Welcome, About, Pricing, FAQ, HowItWorks, Projects, ProjectDetail)
 * wraps in this so navigation and the footer are consistent across the
 * whole marketing/discovery surface, not just present on the homepage.
 */
export function PublicLayout({ children }: { children: ReactNode }) {
    const { props, url } = usePage<SharedAuth>();
    const { openLogin } = useAuthModal();
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [openMenu, setOpenMenu] = useState<string | null>(null);
    const menuRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!openMenu) {
            return;
        }

        function onKeyDown(e: KeyboardEvent) {
            if (e.key === 'Escape') {
                setOpenMenu(null);
            }
        }

        function onClickOutside(e: MouseEvent) {
            if (!menuRef.current?.contains(e.target as Node)) {
                setOpenMenu(null);
            }
        }

        document.addEventListener('keydown', onKeyDown);
        document.addEventListener('mousedown', onClickOutside);

        return () => {
            document.removeEventListener('keydown', onKeyDown);
            document.removeEventListener('mousedown', onClickOutside);
        };
    }, [openMenu]);

    const isActive = (href: string) =>
        url === href || (href !== '/' && url.startsWith(href));

    // Discovery is investor/admin-only — guests, entrepreneurs and other
    // connected roles navigate from marketing or their dashboard instead.
    const canDiscover =
        props.auth.user !== null &&
        (props.auth.roles.includes('investor') ||
            props.auth.roles.includes('admin'));

    const isMenuOpen = (menu: string) => openMenu === menu;
    const toggleMenu = (menu: string) =>
        setOpenMenu((value) => (value === menu ? null : menu));
    const closeMenu = () => setOpenMenu(null);

    return (
        <div className="flex min-h-screen flex-col bg-page">
            <header className="sticky top-0 z-40 border-b border-rule bg-page/95 backdrop-blur">
                <nav className="mx-auto flex h-[72px] max-w-[1160px] items-center justify-between px-5 md:px-8">
                    <Link
                        href="/"
                        className="flex items-center gap-2.5 font-mono text-lg font-bold tracking-[0.04em] whitespace-nowrap text-ink uppercase"
                    >
                        <span className="h-2.5 w-2.5 flex-shrink-0 bg-signal" />
                        KongoInvest
                        
                    </Link>

                    <div className="hidden items-center gap-4 lg:flex xl:gap-6">
                        <div className="relative">
                            <button
                                type="button"
                                onClick={() => toggleMenu('plateforme')}
                                aria-label="Menu plateforme"
                                aria-expanded={isMenuOpen('plateforme')}
                                className={`flex items-center gap-1 border-b-4 py-6 font-mono text-base font-medium tracking-[0.12em] whitespace-nowrap transition-colors ${
                                    isMenuOpen('plateforme') ||
                                    isActive('/how-it-works') ||
                                    isActive('/projects') ||
                                    isActive('/pricing') 
                                        ? 'font-bold border-signal text-ink'
                                        : 'border-transparent text-ink-muted hover:text-ink'
                                }`}
                            >
                                Plateforme
                                <RiArrowDownSLine
                                    size={16}
                                    className={`transition-transform duration-150 ${
                                        isMenuOpen('plateforme')
                                            ? 'rotate-180'
                                            : ''
                                    }`}
                                />
                            </button>
                            {isMenuOpen('plateforme') && (
                                <div className="absolute top-full left-0 z-50 mt-2 w-56 max-w-[calc(100vw-2rem)] border border-rule-strong bg-paper shadow-[4px_4px_0_0_rgba(14,23,38,0.08)] dark:shadow-[4px_4px_0_0_rgba(0,0,0,0.35)]">
                                    <div className="flex flex-col py-1">
                                        <Link
                                            href="/how-it-works"
                                            onClick={closeMenu}
                                            className="flex min-h-11 items-center px-4 py-2 text-lg text-ink-muted hover:bg-surface-2 hover:text-ink"
                                        >
                                            Comment ça marche
                                        </Link>
                                        {canDiscover && (
                                            <Link
                                                href="/projects"
                                                onClick={closeMenu}
                                                className="flex min-h-11 items-center px-4 py-2 text-lg text-ink-muted hover:bg-surface-2 hover:text-ink"
                                            >
                                                Découvrir
                                            </Link>
                                        )}
                                        <Link
                                            href="/pricing"
                                            onClick={closeMenu}
                                            className="flex min-h-11 items-center px-4 py-2 text-lg text-ink-muted hover:bg-surface-2 hover:text-ink"
                                        >
                                            Tarifs
                                        </Link>
                                    </div>
                                </div>
                            )}
                        </div>

                        {canDiscover && (
                            <div className="relative">
                                <button
                                    type="button"
                                    onClick={() => toggleMenu('provinces')}
                                    aria-label="Menu provinces"
                                    aria-expanded={isMenuOpen('provinces')}
                                    className={`flex items-center gap-1 border-b-4 py-6 font-mono text-base font-medium tracking-[0.12em] whitespace-nowrap transition-colors ${
                                        isMenuOpen('provinces') ||
                                        isActive('/projects')
                                            ? 'font-bold border-signal text-ink'
                                            : 'border-transparent text-ink-muted hover:text-ink'
                                    }`}
                                >
                                    Provinces
                                    <RiArrowDownSLine
                                        size={16}
                                        className={`transition-transform duration-150 ${
                                            isMenuOpen('provinces')
                                                ? 'rotate-180'
                                                : ''
                                        }`}
                                    />
                                </button>
                                {isMenuOpen('provinces') && (
                                    <div className="absolute top-full left-1/2 z-50 mt-2 w-[560px] max-w-[calc(100vw-2rem)] -translate-x-1/2 border border-rule-strong bg-paper shadow-[4px_4px_0_0_rgba(14,23,38,0.08)] dark:shadow-[4px_4px_0_0_rgba(0,0,0,0.35)]">
                                        <div className="grid grid-cols-3 gap-x-6 gap-y-1 p-3">
                                            {DRC_PROVINCES.map(
                                                ([value, label]) => (
                                                    <Link
                                                        key={value}
                                                        href={`/projects?province=${value}`}
                                                        onClick={closeMenu}
                                                        className="px-2 py-2 text-base text-ink-muted hover:bg-surface-2 hover:text-ink"
                                                    >
                                                        {label}
                                                    </Link>
                                                ),
                                            )}
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}

                        <div className="relative" ref={menuRef}>
                            <button
                                type="button"
                                onClick={() => toggleMenu('info')}
                                aria-label="Menu info"
                                aria-expanded={isMenuOpen('info')}
                                className={`flex items-center gap-1 border-b-4 py-6 font-mono text-basa font-medium tracking-[0.12em] whitespace-nowrap transition-colors ${
                                    isMenuOpen('info') || isActive('/faq') || isActive('/about') || isActive('/terms') || isActive('/privacy')
                                        ? 'font-bold border-signal text-ink'
                                        : 'border-transparent text-ink-muted hover:text-ink'
                                }`}
                            >
                                Info
                                <RiArrowDownSLine
                                    size={16}
                                    className={`transition-transform duration-150 ${
                                        isMenuOpen('info') ? 'rotate-180' : ''
                                    }`}
                                />
                            </button>
                            {isMenuOpen('info') && (
                                <div className="absolute top-full right-0 z-50 mt-2 w-56 max-w-[calc(100vw-2rem)] border border-rule-strong bg-paper shadow-[4px_4px_0_0_rgba(14,23,38,0.08)] dark:shadow-[4px_4px_0_0_rgba(0,0,0,0.35)]">
                                    <div className="flex flex-col py-1">
                                        {infoLinks.map((link) => (
                                            <Link
                                                key={link.href}
                                                href={link.href}
                                                onClick={closeMenu}
                                                className="flex min-h-11 items-center px-4 py-2 text-lg text-ink-muted hover:bg-surface-2 hover:text-ink"
                                            >
                                                {link.label}
                                            </Link>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>

                    </div>

                    <div className="hidden items-center gap-2 lg:flex">
                        {props.auth.user ? (
                            <>
                                <NotificationBell />
                                <UserMenu user={props.auth.user} />
                            </>
                        ) : (
                            <>
                                <div className="hidden xl:block">
                                    <ThemeSwitcher variant="icon" />
                                </div>
                                <button
                                    onClick={() => openLogin()}
                                    className="min-h-11 border border-rule-strong px-3.5 py-2 font-mono text-lg font-bold tracking-[0.04em] whitespace-nowrap text-ink capitalize transition-colors hover:border-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-action"
                                >
                                    Se connecter
                                </button>
                                {/* <button
                                    onClick={() => openRegister()}
                                    className="min-h-11 border border-ink bg-ink px-3.5 py-2 font-mono text-lg font-bold tracking-[0.04em] whitespace-nowrap text-paper capitalize transition-opacity hover:opacity-85 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-action"
                                >
                                    Créer un compte
                                </button> */}
                            </>
                        )}
                    </div>

                    <button
                        onClick={() => setDrawerOpen(true)}
                        aria-label="Ouvrir le menu"
                        aria-expanded={drawerOpen}
                        className="flex h-11 w-11 items-center justify-center text-ink-muted hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-action lg:hidden"
                    >
                        <RiMenuLine size={22} />
                    </button>
                </nav>
            </header>

            {/* Mobile drawer (spec §81) */}
            <AnimatePresence>
                {drawerOpen && (
                    <div className="fixed inset-0 z-50 lg:hidden">
                        <motion.div
                            variants={fadeIn}
                            initial="initial"
                            animate="animate"
                            exit="exit"
                            className="absolute inset-0 bg-black/70 backdrop-blur-[2px]"
                            onClick={() => {
                                closeMenu();
                                setDrawerOpen(false);
                            }}
                        />
                        <motion.div
                            variants={slideInRight}
                            initial="initial"
                            animate="animate"
                            exit="exit"
                            className="absolute top-0 right-0 flex h-full w-80 max-w-[calc(100vw-2rem)] flex-col border-l border-rule bg-page"
                        >
                            <div className="flex items-center justify-between border-b border-rule px-5 py-4">
                                <span className="text-section-label">Menu</span>
                                <button
                                    onClick={() => {
                                        closeMenu();
                                        setDrawerOpen(false);
                                    }}
                                    aria-label="Fermer le menu"
                                    className="flex h-11 w-11 items-center justify-center text-ink-muted hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-action"
                                >
                                    <RiCloseLine size={22} />
                                </button>
                            </div>
                            <nav className="flex flex-col px-3 py-4">
                                <div>
                                    <button
                                        type="button"
                                        onClick={() => toggleMenu('plateforme')}
                                        aria-expanded={isMenuOpen('plateforme')}
                                        className={`flex min-h-11 w-full items-center border-l-2 px-3 py-2.5 text-nav-link ${
                                            isMenuOpen('plateforme')
                                                ? 'border-ink bg-surface-2 text-ink'
                                                : 'border-transparent text-ink-muted'
                                        }`}
                                    >
                                        Plateforme
                                        <RiArrowDownSLine
                                            size={18}
                                            className={`ml-auto transition-transform duration-150 ${
                                                isMenuOpen('plateforme')
                                                    ? 'rotate-180'
                                                    : ''
                                            }`}
                                        />
                                    </button>
                                    {isMenuOpen('plateforme') && (
                                        <div className="flex flex-col">
                                            <Link
                                                href="/how-it-works"
                                                onClick={() => {
                                                    closeMenu();
                                                    setDrawerOpen(false);
                                                }}
                                                className="flex min-h-11 items-center border-l-2 px-3 py-2.5 text-nav-link"
                                            >
                                                Comment ça marche
                                            </Link>
                                            {canDiscover && (
                                                <Link
                                                    href="/projects"
                                                    onClick={() => {
                                                        closeMenu();
                                                        setDrawerOpen(false);
                                                    }}
                                                    className="flex min-h-11 items-center border-l-2 px-3 py-2.5 text-nav-link"
                                                >
                                                    Découvrir
                                                </Link>
                                            )}
                                            <Link
                                                href="/pricing"
                                                onClick={() => {
                                                    closeMenu();
                                                    setDrawerOpen(false);
                                                }}
                                                className="flex min-h-11 items-center border-l-2 px-3 py-2.5 text-nav-link"
                                            >
                                                Tarifs
                                            </Link>
                                        </div>
                                    )}
                                </div>

                                {canDiscover && (
                                    <div>
                                        <button
                                            type="button"
                                            onClick={() =>
                                                toggleMenu('provinces')
                                            }
                                            aria-expanded={isMenuOpen(
                                                'provinces',
                                            )}
                                            className={`flex min-h-11 w-full items-center border-l-2 px-3 py-2.5 text-nav-link ${
                                                isMenuOpen('provinces')
                                                    ? 'border-ink bg-surface-2 text-ink'
                                                    : 'border-transparent text-ink-muted'
                                            }`}
                                        >
                                            Provinces
                                            <RiArrowDownSLine
                                                size={18}
                                                className={`ml-auto transition-transform duration-150 ${
                                                    isMenuOpen('provinces')
                                                        ? 'rotate-180'
                                                        : ''
                                                }`}
                                            />
                                        </button>
                                        {isMenuOpen('provinces') && (
                                            <div className="flex max-h-[50vh] flex-col overflow-y-auto">
                                                {DRC_PROVINCES.map(
                                                    ([value, label]) => (
                                                        <Link
                                                            key={value}
                                                            href={`/projects?province=${value}`}
                                                            onClick={() => {
                                                                closeMenu();
                                                                setDrawerOpen(
                                                                    false,
                                                                );
                                                            }}
                                                            className="flex min-h-11 items-center border-l-2 px-3 py-2.5 text-nav-link"
                                                        >
                                                            {label}
                                                        </Link>
                                                    ),
                                                )}
                                            </div>
                                        )}
                                    </div>
                                )}

                                <div>
                                    <button
                                        type="button"
                                        onClick={() => toggleMenu('info')}
                                        aria-expanded={isMenuOpen('info')}
                                        className={`flex min-h-11 w-full items-center border-l-2 px-3 py-2.5 text-nav-link ${
                                            isMenuOpen('info')
                                                ? 'border-ink bg-surface-2 text-ink'
                                                : 'border-transparent text-ink-muted'
                                        }`}
                                    >
                                        Info
                                        <RiArrowDownSLine
                                            size={18}
                                            className={`ml-auto transition-transform duration-150 ${
                                                isMenuOpen('info')
                                                    ? 'rotate-180'
                                                    : ''
                                            }`}
                                        />
                                    </button>
                                    {isMenuOpen('info') && (
                                        <div className="flex flex-col">
                                            {infoLinks.map((link) => (
                                                <Link
                                                    key={link.href}
                                                    href={link.href}
                                                    onClick={() => {
                                                        closeMenu();
                                                        setDrawerOpen(false);
                                                    }}
                                                    className="flex min-h-11 items-center border-l-2 px-3 py-2.5 text-nav-link"
                                                >
                                                    {link.label}
                                                </Link>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            </nav>
                            <div className="mt-auto flex flex-col gap-2 border-t border-rule p-4">
                                <div className="px-1 py-1">
                                    <ThemeSwitcher />
                                </div>
                                {props.auth.user ? (
                                    <>
                                        <Link
                                            href="/dashboard"
                                            className="flex min-h-11 items-center justify-center border border-ink bg-ink px-4 py-2.5 text-center font-mono text-lg font-bold tracking-[0.06em] text-paper capitalize"
                                        >
                                            Tableau de bord
                                        </Link>
                                        <Link
                                            href={destroy()}
                                            as="button"
                                            onClick={() => {
                                                closeMenu();
                                                setDrawerOpen(false);
                                            }}
                                            className="flex min-h-11 items-center justify-center border border-rule-strong px-4 py-2.5 text-center font-mono text-lg font-bold tracking-[0.06em] text-ink capitalize"
                                        >
                                            Déconnexion
                                        </Link>
                                    </>
                                ) : (
                                    <>
                                        <button
                                            onClick={() => {
                                                closeMenu();
                                                setDrawerOpen(false);
                                                openLogin();
                                            }}
                                            className="min-h-11 border border-rule-strong px-4 py-2.5 font-mono text-lg font-bold tracking-[0.06em] text-ink capitalize"
                                        >
                                            Se connecter
                                        </button>
                                        {/* <button
                                            onClick={() => {
                                                closeMenu();
                                                setDrawerOpen(false);
                                                openRegister();
                                            }}
                                            className="min-h-11 border border-ink bg-ink px-4 py-2.5 font-mono text-lg font-bold tracking-[0.06em] text-paper capitalize"
                                        >
                                            Créer un compte
                                        </button> */}
                                    </>
                                )}
                            </div>
                        </motion.div>
                    </div>
                )}
            </AnimatePresence>

            <main className="flex-1">
                <PageTransition>{children}</PageTransition>
            </main>

            <Footer />
        </div>
    );
}

function Footer() {
    const { props } = usePage<SharedAuth>();
    const canDiscover =
        props.auth.user !== null &&
        (props.auth.roles.includes('investor') ||
            props.auth.roles.includes('admin'));

    return (
        <footer className="border-t border-rule bg-page">
            <div className="mx-auto max-w-[1160px] px-5 py-14 md:px-8">
                <div className="mb-10 grid grid-cols-2 gap-8 border-b border-rule pb-10 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="col-span-2 lg:col-span-1">
                        <div className="mb-4 flex items-center gap-2.5 font-mono text-xl font-bold tracking-[0.06em] text-ink uppercase">
                            <span className="h-2.5 w-2.5 flex-shrink-0 bg-signal" />
                            KongoInvest
                        </div>
                        <p className="max-w-[220px] text-caption font-normal text-ink-muted">
                            La plateforme d'investissement propulsée par l'IA,
                            entre la RDC et le monde.
                        </p>
                        <div className="mt-4 font-mono text-lg text-ink-muted">
                            Kinshasa · RDC
                        </div>
                    </div>
                    <FooterColumn
                        title="Plateforme"
                        links={[
                            ['/how-it-works', 'Comment ça marche'],
                            ...(canDiscover
                                ? [
                                      ['/projects', 'Découvrir'] as [
                                          string,
                                          string,
                                      ],
                                  ]
                                : []),
                            ['/pricing', 'Tarifs'],
                        ]}
                    />
                    
                    <FooterColumn
                        title="RDC"
                        links={[
                            ['#', 'Kinshasa'],
                            ['#', 'Lubumbashi'],
                            ['#', 'Goma'],
                        ]}
                    />

                    <FooterColumn
                        title="Infos"
                        links={[
                            ['/about', 'À propos'],
                            ['/faq', 'FAQ'],
                            ['/terms', 'Conditions'],
                            ['/privacy', 'Confidentialité'],
                        ]}
                    />
                    
                </div>
                <div className="flex flex-col gap-2 font-mono text-sm text-ink-muted sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        © {new Date().getFullYear()} KongoInvest. Tous
                        droits réservés.
                    </div>
                    <div>
                        Fait pour les entrepreneurs de la RD du Congo.
                    </div>
                </div>
            </div>
        </footer>
    );
}

function FooterColumn({
    title,
    links,
}: {
    title: string;
    links: [string, string][];
}) {
    return (
        <div>
            <div className="mb-3 font-mono text-lg font-semibold tracking-[0.16em] text-ink-muted">
                {title}
            </div>
            <div className="flex flex-col gap-2.5">
                {links.map(([href, label]) => (
                    <Link
                        key={label}
                        href={href}
                        className="text-base text-ink-muted underline-offset-4 hover:text-ink hover:underline"
                    >
                        {label}
                    </Link>
                ))}
            </div>
        </div>
    );
}
