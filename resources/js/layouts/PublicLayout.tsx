import { Link, usePage } from '@inertiajs/react';
import { RiCloseLine, RiMenuLine } from '@remixicon/react';
import { useState, type ReactNode } from 'react';
import { destroy } from '@/actions/Laravel/Fortify/Http/Controllers/AuthenticatedSessionController';
import { NotificationBell } from '@/components/NotificationBell';
import { UserMenu } from '@/components/UserMenu';
import { useAuthModal } from '@/hooks/useAuthModal';

type SharedAuth = { auth: { user: { id: number; name: string; email: string } | null } };

const navLinks = [
    { href: '/projects', label: 'Découvrir' },
    { href: '/how-it-works', label: 'Comment ça marche' },
    { href: '/pricing', label: 'Tarifs' },
    { href: '/faq', label: 'FAQ' },
    { href: '/about', label: 'À propos' },
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
    const { openLogin, openRegister } = useAuthModal();
    const [drawerOpen, setDrawerOpen] = useState(false);

    const isActive = (href: string) => url === href || (href !== '/' && url.startsWith(href));

    return (
        <div className="flex min-h-screen flex-col">
            <header className="sticky top-0 z-40 border-b border-slate-200 bg-white">
                <nav className="mx-auto flex h-[72px] max-w-6xl items-center justify-between px-6">
                    <Link href="/" className="flex items-center gap-2 text-lg font-extrabold text-slate-900">
                        <span className="relative h-[26px] w-[26px] flex-shrink-0 bg-slate-900 after:absolute after:inset-[6px] after:bg-orange-500" />
                        Kanda
                    </Link>

                    <div className="hidden items-center gap-7 text-nav-link text-slate-600 lg:flex">
                        {navLinks.map((link) => (
                            <Link
                                key={link.href}
                                href={link.href}
                                className={isActive(link.href) ? 'text-slate-900' : 'hover:text-slate-900'}
                            >
                                {link.label}
                            </Link>
                        ))}
                    </div>

                    <div className="hidden items-center gap-3 lg:flex">
                        {props.auth.user ? (
                            <>
                                <NotificationBell />
                                <UserMenu user={props.auth.user} />
                            </>
                        ) : (
                            <>
                                <button onClick={() => openLogin()} className="border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-900 hover:border-slate-900">
                                    Se connecter
                                </button>
                                <button onClick={() => openRegister()} className="bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                                    Créer un compte
                                </button>
                            </>
                        )}
                    </div>

                    <button
                        onClick={() => setDrawerOpen(true)}
                        aria-label="Ouvrir le menu"
                        className="p-2 text-slate-700 lg:hidden"
                    >
                        <RiMenuLine size={22} />
                    </button>
                </nav>
            </header>

            {/* Mobile drawer (spec §81) */}
            {drawerOpen && (
                <div className="fixed inset-0 z-50 lg:hidden">
                    <div className="absolute inset-0 bg-slate-900/40" onClick={() => setDrawerOpen(false)} />
                    <div className="absolute right-0 top-0 flex h-full w-72 flex-col bg-white">
                        <div className="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                            <span className="text-section-label">Menu</span>
                            <button onClick={() => setDrawerOpen(false)} aria-label="Fermer le menu" className="text-slate-500 hover:text-slate-900">
                                <RiCloseLine size={22} />
                            </button>
                        </div>
                        <nav className="flex flex-col gap-1 px-3 py-4">
                            {navLinks.map((link) => (
                                <Link
                                    key={link.href}
                                    href={link.href}
                                    onClick={() => setDrawerOpen(false)}
                                    className={`px-3 py-2.5 text-nav-link ${isActive(link.href) ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50'}`}
                                >
                                    {link.label}
                                </Link>
                            ))}
                        </nav>
                        <div className="mt-auto flex flex-col gap-2 border-t border-slate-200 p-4">
                            {props.auth.user ? (
                                <>
                                    <Link href="/dashboard" className="bg-slate-900 px-4 py-2.5 text-center text-sm font-semibold text-white">
                                        Tableau de bord
                                    </Link>
                                    <Link
                                        href={destroy()}
                                        as="button"
                                        onClick={() => setDrawerOpen(false)}
                                        className="border border-slate-300 px-4 py-2.5 text-center text-sm font-semibold text-slate-900"
                                    >
                                        Déconnexion
                                    </Link>
                                </>
                            ) : (
                                <>
                                    <button onClick={() => { setDrawerOpen(false); openLogin(); }} className="border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-900">
                                        Se connecter
                                    </button>
                                    <button onClick={() => { setDrawerOpen(false); openRegister(); }} className="bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">
                                        Créer un compte
                                    </button>
                                </>
                            )}
                        </div>
                    </div>
                </div>
            )}

            <main className="flex-1">{children}</main>

            <Footer />
        </div>
    );
}

function Footer() {
    return (
        <footer className="border-t border-slate-200">
            <div className="mx-auto max-w-6xl px-6 py-14">
                <div className="mb-10 grid grid-cols-2 gap-8 sm:grid-cols-3 lg:grid-cols-5">
                    <div className="col-span-2 lg:col-span-1">
                        <div className="mb-3 flex items-center gap-2 text-base font-extrabold text-slate-900">
                            <span className="relative h-[22px] w-[22px] flex-shrink-0 bg-slate-900 after:absolute after:inset-[5px] after:bg-orange-500" />
                            Kanda
                        </div>
                        <p className="max-w-[200px] text-caption">
                            La plateforme d'investissement propulsée par l'IA, entre la RDC et le monde.
                        </p>
                    </div>
                    <FooterColumn
                        title="Plateforme"
                        links={[
                            ['/how-it-works', 'Comment ça marche'],
                            ['/projects', 'Découvrir'],
                            ['/pricing', 'Tarifs'],
                        ]}
                    />
                    <FooterColumn
                        title="Entreprise"
                        links={[
                            ['/about', 'À propos'],
                            ['/faq', 'FAQ'],
                        ]}
                    />
                    <FooterColumn
                        title="Légal"
                        links={[
                            ['/terms', 'Conditions'],
                            ['/privacy', 'Confidentialité'],
                        ]}
                    />
                    <FooterColumn title="RDC" links={[['#', 'Kinshasa'], ['#', 'Lubumbashi'], ['#', 'Goma']]} />
                </div>
                <div className="flex flex-col gap-2 border-t border-slate-200 pt-6 text-caption sm:flex-row sm:items-center sm:justify-between">
                    <div>© {new Date().getFullYear()} Kanda. Tous droits réservés.</div>
                    <div>Fait pour les entrepreneurs de la République Démocratique du Congo.</div>
                </div>
            </div>
        </footer>
    );
}

function FooterColumn({ title, links }: { title: string; links: [string, string][] }) {
    return (
        <div>
            <div className="mb-3 font-mono text-[11px] uppercase tracking-widest text-slate-400">{title}</div>
            <div className="flex flex-col gap-2">
                {links.map(([href, label]) => (
                    <Link key={label} href={href} className="text-xs text-slate-600 hover:text-slate-900">
                        {label}
                    </Link>
                ))}
            </div>
        </div>
    );
}
