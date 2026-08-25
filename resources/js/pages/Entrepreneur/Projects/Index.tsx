import { Head, router } from '@inertiajs/react';
import { RiAddLine, RiArchiveLine, RiEditLine, RiRocketLine } from '@remixicon/react';
import { Button } from '@/components/Ui/Button';
import { Link } from '@/components/Ui/Link';
import { useConfirm } from '@/hooks/useConfirm';

type ProjectRow = {
    id: number;
    title: string;
    slug: string;
    status: string;
    fundingTargetMinor: number | null;
    fundingCurrency: string;
    sectors: { id: number; name: string }[];
};

const statusStyles: Record<string, string> = {
    draft: 'bg-slate-100 text-slate-700',
    published: 'bg-blue-50 text-blue-700',
    archived: 'bg-slate-100 text-slate-400',
    closed: 'bg-orange-50 text-orange-700',
};

const statusLabels: Record<string, string> = {
    draft: 'Brouillon',
    published: 'Publié',
    archived: 'Archivé',
    closed: 'Clôturé',
};

export default function Index({ projects, canCreateProject }: { projects: ProjectRow[]; canCreateProject: boolean }) {
    const { confirm, ConfirmDialog } = useConfirm();

    function publish(slug: string) {
        router.post(`/entrepreneur/projects/${slug}/publish`);
    }

    async function archive(slug: string) {
        if (await confirm('Archiver ce projet ? Vous pourrez alors en créer un nouveau.')) {
            router.post(`/entrepreneur/projects/${slug}/archive`);
        }
    }

    return (
        <>
            <Head title="Mes projets" />
            <div className="mx-auto max-w-4xl px-6 py-12">
                <div className="mb-8 flex items-center justify-between">
                    <div>
                        <p className="text-eyebrow">Entrepreneur</p>
                        <h1 className="mt-2 text-page-title">Mes projets</h1>
                    </div>
                    {canCreateProject ? (
                        <Link href="/entrepreneur/projects/create" variant="cta" className="flex items-center gap-2 py-2.5">
                            <RiAddLine size={16} /> Nouveau projet
                        </Link>
                    ) : (
                        <span className="border border-slate-200 bg-slate-50 px-4 py-2.5 text-caption">
                            Limite du plan Free atteinte — archivez un projet pour en créer un autre.
                        </span>
                    )}
                </div>

                {projects.length === 0 ? (
                    <div className="border border-dashed border-slate-400 py-16 text-center text-body">
                        Vous n'avez encore aucun projet.
                    </div>
                ) : (
                    <div className="border border-slate-200">
                        {projects.map((project) => (
                            <div key={project.id} className="flex items-center justify-between border-b border-slate-200 p-5 last:border-b-0">
                                <div>
                                    <div className="mb-1 flex items-center gap-2">
                                        <span className={`px-2 py-0.5 font-mono text-[11px] uppercase ${statusStyles[project.status]}`}>
                                            {statusLabels[project.status]}
                                        </span>
                                        {project.sectors[0] && (
                                            <span className="font-mono text-[11px] uppercase text-slate-400">{project.sectors[0].name}</span>
                                        )}
                                    </div>
                                    <h3 className="text-item-title">{project.title}</h3>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Link
                                        href={`/entrepreneur/projects/${project.slug}/edit`}
                                        variant="outline"
                                        size="sm"
                                        className="flex items-center gap-1.5 py-2"
                                    >
                                        <RiEditLine size={14} /> Modifier
                                    </Link>
                                    {project.status === 'draft' && (
                                        <Button
                                            variant="secondary"
                                            size="sm"
                                            onClick={() => publish(project.slug)}
                                            className="flex items-center gap-1.5 py-2"
                                        >
                                            <RiRocketLine size={14} /> Publier
                                        </Button>
                                    )}
                                    {project.status !== 'archived' && (
                                        <button
                                            onClick={() => archive(project.slug)}
                                            className="flex items-center gap-1.5 border border-slate-400 px-3 py-2 text-xs font-semibold text-slate-500 hover:border-orange-600 hover:text-orange-600"
                                        >
                                            <RiArchiveLine size={14} /> Archiver
                                        </button>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
            {ConfirmDialog}
        </>
    );
}
