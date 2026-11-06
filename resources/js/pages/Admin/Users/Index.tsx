import { Head, router } from '@inertiajs/react';
import { AdminLayout } from '@/layouts/AdminLayout';
import { Badge } from '@/components/ui/badge';

type UserRow = {
    id: number;
    name: string;
    email: string;
    roles: string[];
    createdAt: string;
};
type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
};

export default function Index({
    users,
    filters,
}: {
    users: Paginated<UserRow>;
    filters: { role?: string };
}) {
    const roles = ['entrepreneur', 'investor', 'admin']; // exactly three roles — "professional" is a plan, not a role

    return (
        <AdminLayout title="Utilisateurs">
            <Head title="Admin — Utilisateurs" />

            <p className="mb-6 border border-rule bg-surface-2 px-4 py-3 text-caption">
                Vue en lecture seule. La modification des rôles est une
                opération sensible (spec §89) qui sera construite avec
                confirmation de mot de passe et journal d'audit dédiés.
            </p>

            <div className="mb-4 flex gap-2">
                <button
                    onClick={() => router.get('/admin/users')}
                    className={`min-h-11 border px-3 py-1.5 text-btn ${!filters.role ? 'border-ink bg-ink text-paper' : 'border-rule-strong text-ink'}`}
                >
                    Tous
                </button>
                {roles.map((r) => (
                    <button
                        key={r}
                        onClick={() => router.get('/admin/users', { role: r })}
                        className={`min-h-11 border px-3 py-1.5 text-btn capitalize ${filters.role === r ? 'border-ink bg-ink text-paper' : 'border-rule-strong text-ink'}`}
                    >
                        {r}
                    </button>
                ))}
            </div>

            <div className="border border-rule">
                {users.data.map((u) => (
                    <div
                        key={u.id}
                        className="flex items-center justify-between border-b border-rule p-4 last:border-b-0"
                    >
                        <div>
                            <div className="text-item-title">{u.name}</div>
                            <div className="text-caption">{u.email}</div>
                        </div>
                        <div className="flex items-center gap-3">
                            {u.roles.map((r) => (
                                <Badge key={r} variant="outline">
                                    {r}
                                </Badge>
                            ))}
                            <span className="font-mono text-lg text-ink-muted">
                                {new Date(u.createdAt).toLocaleDateString(
                                    'fr-FR',
                                )}
                            </span>
                        </div>
                    </div>
                ))}
            </div>

            {users.links.length > 3 && (
                <div className="mt-6 flex flex-wrap justify-center gap-1">
                    {users.links.map((link, i) => (
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
