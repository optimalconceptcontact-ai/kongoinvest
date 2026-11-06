import { Head, router } from '@inertiajs/react';
import { AdminLayout } from '@/layouts/AdminLayout';
import { Badge } from '@/components/ui/badge';

type ProjectRow = {
    id: number;
    slug: string;
    title: string;
    entrepreneurName: string;
    status: string;
    statusLabel: string;
    sectors: string[];
    fundingTargetMinor: number | null;
    fundingCurrency: string;
    createdAt: string;
};
type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
};

const statuses = ['draft', 'published', 'archived', 'closed'];

function formatTarget(minor: number | null, currency: string) {
    if (minor === null) return '—';
    return `${(minor / 100).toLocaleString('fr-FR')} ${currency}`;
}

export default function Index({
    projects,
    filters,
}: {
    projects: Paginated<ProjectRow>;
    filters: { status?: string };
}) {
    return (
        <AdminLayout title="Projets">
            <Head title="Admin — Projets" />

            <p className="mb-6 border border-rule bg-surface-2 px-4 py-3 text-caption">
                Vue en lecture seule sur tous les projets de la plateforme. La
                gestion d'un projet reste réservée à son entrepreneur.
            </p>

            <div className="mb-4 flex gap-2">
                <button
                    onClick={() => router.get('/admin/projects')}
                    className={`min-h-11 border px-3 py-1.5 text-btn ${!filters.status ? 'border-ink bg-ink text-paper' : 'border-rule-strong text-ink'}`}
                >
                    Tous
                </button>
                {statuses.map((s) => (
                    <button
                        key={s}
                        onClick={() =>
                            router.get('/admin/projects', { status: s })
                        }
                        className={`min-h-11 border px-3 py-1.5 text-btn capitalize ${filters.status === s ? 'border-ink bg-ink text-paper' : 'border-rule-strong text-ink'}`}
                    >
                        {s}
                    </button>
                ))}
            </div>

            <div className="border border-rule-strong bg-paper px-2">
                {projects.data.length === 0 && (
                    <p className="p-6 text-center text-caption">
                        Aucun projet.
                    </p>
                )}
                {projects.data.map((p) => (
                    <a
                        key={p.id}
                        href={`/projects/${p.slug}`}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="flex items-center justify-between border-b border-rule p-4 last:border-b-0 hover:bg-surface-2"
                    >
                        <div>
                            <div className="text-item-title">{p.title}</div>
                            <div className="text-caption">
                                Par {p.entrepreneurName} ·{' '}
                                {p.sectors.join(', ') || 'Aucun secteur'}
                            </div>
                        </div>
                        <div className="flex items-center gap-3">
                            <span className="font-mono text-lg text-ink-muted">
                                {formatTarget(
                                    p.fundingTargetMinor,
                                    p.fundingCurrency,
                                )}
                            </span>
                            <Badge variant="outline">{p.statusLabel}</Badge>
                            <span className="font-mono text-lg text-ink-muted">
                                {new Date(p.createdAt).toLocaleDateString(
                                    'fr-FR',
                                )}
                            </span>
                        </div>
                    </a>
                ))}
            </div>

            {projects.links.length > 3 && (
                <div className="mt-6 flex flex-wrap justify-center gap-1">
                    {projects.links.map((link, i) => (
                        <button
                            key={i}
                            disabled={!link.url}
                            onClick={() => link.url && router.get(link.url)}
                            className={`min-h-11 min-w-11 border px-3 py-2 font-mono text-lg ${link.active ? 'border-ink bg-ink text-paper' : 'border-rule-strong text-ink disabled:opacity-30'}`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>
            )}
        </AdminLayout>
    );
}
