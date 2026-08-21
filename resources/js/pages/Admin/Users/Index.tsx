import { Head, router } from '@inertiajs/react';
import { AdminLayout } from '@/layouts/AdminLayout';
import { Badge } from '@/components/Ui/Badge';

type UserRow = { id: number; name: string; email: string; roles: string[]; createdAt: string };
type Paginated<T> = { data: T[]; links: { url: string | null; label: string; active: boolean }[] };

export default function Index({ users, filters }: { users: Paginated<UserRow>; filters: { role?: string } }) {
    const roles = ['entrepreneur', 'investor', 'admin']; // exactly three roles — "professional" is a plan, not a role

    return (
        <AdminLayout title="Utilisateurs">
            <Head title="Admin — Utilisateurs" />

            <p className="mb-6 border border-slate-200 bg-slate-50 px-4 py-3 text-caption">
                Vue en lecture seule. La modification des rôles est une opération sensible (spec §89) qui sera
                construite avec confirmation de mot de passe et journal d'audit dédiés.
            </p>

            <div className="mb-4 flex gap-2">
                <button
                    onClick={() => router.get('/admin/users')}
                    className={`border px-3 py-1.5 text-xs font-medium ${!filters.role ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-300 text-slate-600'}`}
                >
                    Tous
                </button>
                {roles.map((r) => (
                    <button
                        key={r}
                        onClick={() => router.get('/admin/users', { role: r })}
                        className={`border px-3 py-1.5 text-xs font-medium capitalize ${filters.role === r ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-300 text-slate-600'}`}
                    >
                        {r}
                    </button>
                ))}
            </div>

            <div className="border border-slate-200">
                {users.data.map((u) => (
                    <div key={u.id} className="flex items-center justify-between border-b border-slate-200 p-4 last:border-b-0">
                        <div>
                            <div className="text-item-title">{u.name}</div>
                            <div className="text-caption">{u.email}</div>
                        </div>
                        <div className="flex items-center gap-3">
                            {u.roles.map((r) => (
                                <Badge key={r} variant="outline">{r}</Badge>
                            ))}
                            <span className="font-mono text-[11px] text-slate-400">{new Date(u.createdAt).toLocaleDateString('fr-FR')}</span>
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
                            className={`min-w-[36px] border px-3 py-2 font-mono text-xs ${link.active ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-300 text-slate-600 disabled:opacity-30'}`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>
            )}
        </AdminLayout>
    );
}
