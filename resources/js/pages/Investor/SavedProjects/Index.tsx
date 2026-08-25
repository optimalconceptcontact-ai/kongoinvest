import { Head, router } from '@inertiajs/react';
import { InvestorLayout } from '@/layouts/InvestorLayout';
import { ProjectCard, type ProjectCardData } from '@/components/ProjectCard';

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    last_page: number;
};

export default function Index({ projects }: { projects: Paginated<ProjectCardData> }) {
    return (
        <InvestorLayout title="Projets sauvegardés">
            <Head title="Projets sauvegardés" />

            {projects.data.length === 0 ? (
                <div className="border border-dashed border-slate-400 py-16 text-center text-body">
                    Aucun projet sauvegardé pour l'instant — le bouton « Sauvegarder » se trouve sur la page de
                    chaque projet.
                </div>
            ) : (
                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
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
        </InvestorLayout>
    );
}
