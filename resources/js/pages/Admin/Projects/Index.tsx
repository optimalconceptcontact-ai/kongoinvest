import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
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
    submittedAt: string | null;
    moderatedAt: string | null;
    moderationNotes: string | null;
    moderatorName: string | null;
};
type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
};
type StatusOption = { value: string; label: string };

function formatTarget(minor: number | null, currency: string) {
    if (minor === null) return '—';
    return `${(minor / 100).toLocaleString('fr-FR')} ${currency}`;
}

function formatDate(value: string | null) {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('fr-FR');
}

function RejectPanel({
    project,
    onClose,
}: {
    project: ProjectRow;
    onClose: () => void;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        reason: '',
    });

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                post(`/admin/projects/${project.slug}/reject`, {
                    preserveScroll: true,
                    onSuccess: () => {
                        reset();
                        onClose();
                    },
                });
            }}
            className="border-t border-rule bg-surface-2 p-4"
        >
            <label
                htmlFor={`reject-reason-${project.id}`}
                className="text-eyebrow mb-1 block"
            >
                Motif du rejet — visible par l'entrepreneur
            </label>
            <textarea
                id={`reject-reason-${project.id}`}
                value={data.reason}
                onChange={(event) => setData('reason', event.target.value)}
                rows={3}
                className="w-full border border-rule-strong bg-paper px-3 py-2 text-base text-ink"
                placeholder="Expliquez ce qui doit être corrigé avant une nouvelle soumission."
                required
            />
            {errors.reason && (
                <p className="mt-1 text-caption text-danger">{errors.reason}</p>
            )}
            <div className="mt-3 flex flex-wrap gap-2">
                <button
                    type="submit"
                    disabled={processing}
                    className="min-h-11 border border-ink bg-ink px-4 text-btn text-paper disabled:opacity-50"
                >
                    Confirmer le rejet
                </button>
                <button
                    type="button"
                    onClick={() => {
                        reset();
                        onClose();
                    }}
                    className="min-h-11 border border-rule-strong px-4 text-btn text-ink hover:bg-paper"
                >
                    Annuler
                </button>
            </div>
        </form>
    );
}

export default function Index({
    projects,
    filters,
    statuses,
    counts,
    canModerate,
}: {
    projects: Paginated<ProjectRow>;
    filters: { status?: string };
    statuses: StatusOption[];
    counts: Record<string, number>;
    canModerate: boolean;
}) {
    const [rejecting, setRejecting] = useState<ProjectRow | null>(null);
    const total = Object.values(counts).reduce((sum, value) => sum + value, 0);

    return (
        <AdminLayout title="Projets">
            <Head title="Admin — Projets" />

            <p className="mb-6 border border-rule bg-surface-2 px-4 py-3 text-caption">
                Les entrepreneurs soumettent leurs projets à validation. Un
                administrateur disposant de <code>projects.moderate</code> peut
                approuver ou rejeter un projet en attente ; un rejet notifie
                l'entrepreneur avec le motif.
            </p>

            <div className="mb-4 flex flex-wrap gap-2">
                <button
                    onClick={() => router.get('/admin/projects')}
                    className={`min-h-11 border px-3 py-1.5 text-btn ${!filters.status ? 'border-ink bg-ink text-paper' : 'border-rule-strong text-ink'}`}
                >
                    Tous ({total})
                </button>
                {statuses.map((status) => (
                    <button
                        key={status.value}
                        onClick={() =>
                            router.get('/admin/projects', {
                                status: status.value,
                            })
                        }
                        className={`min-h-11 border px-3 py-1.5 text-btn ${filters.status === status.value ? 'border-ink bg-ink text-paper' : 'border-rule-strong text-ink'}`}
                    >
                        {status.label} ({counts[status.value] ?? 0})
                    </button>
                ))}
            </div>

            <div className="border border-rule-strong bg-paper">
                {projects.data.length === 0 && (
                    <p className="p-6 text-center text-caption">
                        Aucun projet.
                    </p>
                )}
                {projects.data.map((p) => (
                    <div
                        key={p.id}
                        className="border-b border-rule last:border-b-0"
                    >
                        <div className="flex flex-wrap items-center justify-between gap-3 p-4 hover:bg-surface-2">
                            <div>
                                <a
                                    href={`/projects/${p.slug}`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="text-item-title hover:underline"
                                >
                                    {p.title}
                                </a>
                                <div className="text-caption">
                                    Par {p.entrepreneurName} ·{' '}
                                    {p.sectors.join(', ') || 'Aucun secteur'}
                                </div>
                                {p.status === 'pending_review' && (
                                    <div className="mt-1 text-caption">
                                        Soumis le {formatDate(p.submittedAt)}
                                    </div>
                                )}
                                {p.status === 'rejected' && (
                                    <div className="mt-1 text-caption">
                                        Rejeté le {formatDate(p.moderatedAt)}
                                        {p.moderatorName
                                            ? ` par ${p.moderatorName}`
                                            : ''}
                                    </div>
                                )}
                            </div>
                            <div className="flex flex-wrap items-center gap-3">
                                <span className="font-mono text-lg text-ink-muted">
                                    {formatTarget(
                                        p.fundingTargetMinor,
                                        p.fundingCurrency,
                                    )}
                                </span>
                                <Badge variant="outline">{p.statusLabel}</Badge>
                                <span className="font-mono text-lg text-ink-muted">
                                    {formatDate(p.createdAt)}
                                </span>
                                {canModerate && p.status === 'pending_review' && (
                                    <>
                                        <button
                                            onClick={() =>
                                                router.post(
                                                    `/admin/projects/${p.slug}/approve`,
                                                    {},
                                                    { preserveScroll: true },
                                                )
                                            }
                                            className="min-h-11 border border-ink bg-ink px-3 text-btn text-paper hover:opacity-90"
                                        >
                                            Approuver
                                        </button>
                                        <button
                                            onClick={() => setRejecting(p)}
                                            className="min-h-11 border border-rule-strong px-3 text-btn text-ink hover:border-ink"
                                        >
                                            Rejeter
                                        </button>
                                    </>
                                )}
                            </div>
                        </div>
                        {p.status === 'rejected' && p.moderationNotes && (
                            <div className="border-t border-rule bg-surface-2 px-4 py-3 text-caption">
                                <strong className="text-ink">
                                    Motif du rejet :
                                </strong>{' '}
                                {p.moderationNotes}
                            </div>
                        )}
                        {rejecting?.id === p.id && (
                            <RejectPanel
                                project={p}
                                onClose={() => setRejecting(null)}
                            />
                        )}
                    </div>
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
