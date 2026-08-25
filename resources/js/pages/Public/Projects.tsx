import { router } from '@inertiajs/react';
import { SeoHead } from '@/components/SeoHead';
import { FormEvent, useState } from 'react';
import { ProjectCard, type ProjectCardData } from '@/components/ProjectCard';
import { Button } from '@/components/Ui/Button';
import { Input } from '@/components/Ui/Input';
import { Select } from '@/components/Ui/Select';
import { PublicLayout } from '@/layouts/PublicLayout';

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
};

type Props = {
    projects: Paginated<ProjectCardData>;
    sectors: { id: number; name: string }[];
    stages: { id: number; key: string; name: string }[];
    filters: { q?: string; sector?: string; stage?: string; province?: string };
};

export default function Projects({ projects, sectors, stages, filters }: Props) {
    const [q, setQ] = useState(filters.q ?? '');

    function applyFilter(patch: Record<string, string | undefined>) {
        router.get('/projects', { ...filters, ...patch }, { preserveState: true, preserveScroll: true });
    }

    function submitSearch(e: FormEvent) {
        e.preventDefault();
        applyFilter({ q });
    }

    return (
        <PublicLayout>
            <SeoHead title="Découvrir des projets" description="Parcourez les projets entrepreneuriaux publiés en République Démocratique du Congo, filtrés par secteur et stade." />
            <div className="mx-auto max-w-6xl px-6 py-12">
                <p className="text-eyebrow">Découverte</p>
                <h1 className="mt-2 mb-8 text-page-title-lg">Projets à financer en RDC</h1>

                <div className="mb-8 flex flex-wrap items-center gap-3 border border-slate-300 p-4">
                    <form onSubmit={submitSearch} className="flex flex-1 min-w-[220px]">
                        <Input
                            value={q}
                            onChange={(e) => setQ(e.target.value)}
                            placeholder="Rechercher un projet..."
                        />
                    </form>

                    <Select
                        value={filters.sector ?? ''}
                        onChange={(e) => applyFilter({ sector: e.target.value || undefined })}
                    >
                        <option value="">Tous secteurs</option>
                        {sectors.map((s) => (
                            <option key={s.id} value={s.id}>{s.name}</option>
                        ))}
                    </Select>

                    <Select
                        value={filters.stage ?? ''}
                        onChange={(e) => applyFilter({ stage: e.target.value || undefined })}
                    >
                        <option value="">Tous stades</option>
                        {stages.map((s) => (
                            <option key={s.id} value={s.id}>{s.name}</option>
                        ))}
                    </Select>

                    {(filters.q || filters.sector || filters.stage || filters.province) && (
                        <Button
                            type="button"
                            onClick={() => {
                                setQ('');
                                router.get('/projects');
                            }}
                            variant="ghost"
                            size="inline"
                            className="font-mono font-normal underline"
                        >
                            Réinitialiser
                        </Button>
                    )}
                </div>

                {projects.data.length === 0 ? (
                    <div className="border border-dashed border-slate-400 py-16 text-center text-body">
                        Aucun projet ne correspond à ces critères.
                    </div>
                ) : (
                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        {projects.data.map((project) => (
                            <ProjectCard key={project.slug} project={project} />
                        ))}
                    </div>
                )}

                {projects.last_page > 1 && (
                    <div className="mt-10 flex flex-wrap justify-center gap-1">
                        {projects.links.map((link, i) => (
                            <button
                                key={i}
                                disabled={!link.url}
                                onClick={() => link.url && router.get(link.url, {}, { preserveState: true })}
                                className={`min-w-[36px] border px-3 py-2 font-mono text-xs ${
                                    link.active
                                        ? 'border-slate-900 bg-slate-900 text-white'
                                        : 'border-slate-400 text-slate-700 hover:border-slate-900 disabled:opacity-30'
                                }`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </div>
        </PublicLayout>
    );
}
