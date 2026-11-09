import { Link, usePage } from '@inertiajs/react';

const PAGES = [
    { href: '/admin/statistics/users', label: 'Utilisateurs' },
    { href: '/admin/statistics/entrepreneurs', label: 'Entrepreneurs' },
    { href: '/admin/statistics/investors', label: 'Investisseurs' },
    { href: '/admin/statistics/projects', label: 'Projets' },
    { href: '/admin/statistics/investments', label: 'Investissements' },
    { href: '/admin/statistics/revenue', label: 'Revenus' },
];

export function StatisticsNav() {
    const { url } = usePage();
    const pathname = url.split('?')[0];

    return (
        <nav
            aria-label="Pages statistiques"
            className="flex flex-wrap border border-rule-strong bg-paper print:hidden"
        >
            {PAGES.map((page) => {
                const active = pathname === page.href;

                return (
                    <Link
                        key={page.href}
                        href={page.href}
                        aria-current={active ? 'page' : undefined}
                        className={`min-h-11 border-r border-rule px-4 py-3 text-btn last:border-r-0 ${
                            active
                                ? 'bg-ink text-paper'
                                : 'text-ink hover:bg-surface-2'
                        }`}
                    >
                        {page.label}
                    </Link>
                );
            })}
        </nav>
    );
}
