import { Head, router } from '@inertiajs/react';
import { InvestorLayout } from '@/layouts/InvestorLayout';
import { ProjectCard, type ProjectCardData } from '@/components/ProjectCard';

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    last_page: number;
};

export default function Index({
    projects,
}: {
    projects: Paginated<ProjectCardData>;
}) {
    return (
        <InvestorLayout title="Projets sauvegardés">
            <Head title="Projets sauvegardés" />

            {projects.data.length === 0 ? (
                <div className="border border-rule-strong bg-paper p-6 text-body">
                    Aucun projet sauvegardé pour l'instant — le bouton «
                    Sauvegarder » se trouve sur la page de chaque projet.
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
                            onClick={() =>
                                link.url &&
                                router.get(
                                    link.url,
                                    {},
                                    { preserveState: true },
                                )
                            }
                            className={`min-h-11 min-w-11 border px-3 py-2 font-mono text-lg ${
                                link.active
                                    ? 'border-ink bg-ink text-paper'
                                    : 'border-rule-strong text-ink hover:border-ink disabled:opacity-30'
                            }`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>
            )}
        </InvestorLayout>
    );
}
