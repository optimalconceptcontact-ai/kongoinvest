import { Deferred, router } from '@inertiajs/react';
import { RiSearchLine } from '@remixicon/react';
import { SeoHead } from '@/components/SeoHead';
import { FormEvent, useState } from 'react';
import { ProjectCard, type ProjectCardData } from '@/components/ProjectCard';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { ProjectGridSkeleton } from '@/components/ui/skeleton';
import { PublicLayout } from '@/layouts/PublicLayout';

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
};

type Props = {
    projects?: Paginated<ProjectCardData>;
    sectors: { id: number; name: string }[];
    stages: { id: number; key: string; name: string }[];
    filters: { q?: string; sector?: string; stage?: string; province?: string };
};

export default function Projects({
    projects,
    sectors,
    stages,
    filters,
}: Props) {
    const [q, setQ] = useState(filters.q ?? '');

    function applyFilter(patch: Record<string, string | undefined>) {
        router.get(
            '/projects',
            { ...filters, ...patch },
            { preserveState: true, preserveScroll: true },
        );
    }

    function submitSearch(e: FormEvent) {
        e.preventDefault();
        applyFilter({ q });
    }

    return (
        <PublicLayout>
            <SeoHead
                title="Découvrir des projets"
                description="Parcourez les projets entrepreneuriaux publiés en République Démocratique du Congo, filtrés par secteur et stade."
            />
            <div className="mx-auto max-w-6xl px-4 py-12 md:px-6">
                <p className="text-eyebrow">Découverte</p>
                <h1 className="mt-2 mb-8 text-page-title-lg">
                    Projets à financer en RDC
                </h1>

                <div className="mb-8 flex flex-wrap items-center gap-3 border border-rule-strong bg-surface-2 p-4">
                    <form
                        onSubmit={submitSearch}
                        className="flex min-w-[220px] flex-1"
                    >
                        <Input
                            icon={<RiSearchLine size={18} />}
                            value={q}
                            onChange={(e) => setQ(e.target.value)}
                            placeholder="Rechercher un projet..."
                        />
                    </form>

                    <Select
                        className="w-auto min-w-[180px] flex-1"
                        value={filters.sector ?? ''}
                        onChange={(e) =>
                            applyFilter({ sector: e.target.value || undefined })
                        }
                    >
                        <option value="">Tous secteurs</option>
                        {sectors.map((s) => (
                            <option key={s.id} value={s.id}>
                                {s.name}
                            </option>
                        ))}
                    </Select>

                    <Select
                        className="w-auto min-w-[180px] flex-1"
                        value={filters.stage ?? ''}
                        onChange={(e) =>
                            applyFilter({ stage: e.target.value || undefined })
                        }
                    >
                        <option value="">Tous stades</option>
                        {stages.map((s) => (
                            <option key={s.id} value={s.id}>
                                {s.name}
                            </option>
                        ))}
                    </Select>

                    {(filters.q ||
                        filters.sector ||
                        filters.stage ||
                        filters.province) && (
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

                <Deferred
                    data="projects"
                    fallback={
                        <ProjectGridSkeleton
                            count={12}
                            className="sm:grid-cols-2 lg:grid-cols-4"
                        />
                    }
                >
                    {projects && projects.data.length === 0 ? (
                        <div className="border border-rule-strong bg-paper p-6 text-body">
                            Aucun projet ne correspond à ces critères.
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                            {projects?.data.map((project) => (
                                <ProjectCard
                                    key={project.slug}
                                    project={project}
                                />
                            ))}
                        </div>
                    )}

                    {projects && projects.last_page > 1 && (
                        <nav
                            aria-label="Pagination des projets"
                            className="mt-10 flex flex-wrap justify-center gap-1"
                        >
                            {projects.links.map((link, i) => {
                                const label = link.label
                                    .replace(/&laquo;/g, '«')
                                    .replace(/&raquo;/g, '»')
                                    .replace(/&amp;/g, '&');

                                return (
                                    <button
                                        key={i}
                                        disabled={!link.url}
                                        onClick={() =>
                                            link.url &&
                                            router.get(
                                                link.url,
                                                {},
                                                { preserveState: true },
                                            )
                                        }
                                        aria-label={label}
                                        aria-current={
                                            link.active ? 'page' : undefined
                                        }
                                        className={`min-h-11 min-w-11 border px-3 py-2 font-mono text-lg ${
                                            link.active
                                                ? 'border-ink bg-ink text-paper'
                                                : 'border-rule-strong text-ink hover:border-ink disabled:opacity-30'
                                        }`}
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                );
                            })}
                        </nav>
                    )}
                </Deferred>
            </div>
        </PublicLayout>
    );
}
