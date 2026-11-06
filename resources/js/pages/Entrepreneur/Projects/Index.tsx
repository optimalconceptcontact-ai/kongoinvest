import { Head, router } from '@inertiajs/react';
import {
    RiAddLine,
    RiArchiveLine,
    RiEditLine,
    RiRocketLine,
} from '@remixicon/react';
import { FundingLimitWarning } from '@/components/FundingLimitWarning';
import { BackLink } from '@/components/ui/BackLink';
import { Button } from '@/components/ui/button';
import { Link } from '@/components/ui/Link';
import { useConfirm } from '@/hooks/useConfirm';

type ProjectRow = {
    id: number;
    title: string;
    slug: string;
    status: string;
    fundingTargetMinor: number | null;
    fundingCurrency: string;
    sectors: { id: number; name: string }[];
    fundingLimitWarning?: {
        shouldShow: boolean;
        currentAmount: string;
        maxLimit: string;
        deadline: string;
        deadlinePassed: boolean;
        daysRemaining: number;
    };
};

const statusStyles: Record<string, string> = {
    draft: 'bg-surface-2  text-ink ',
    published: 'bg-surface-2 text-ink',
    archived: 'bg-surface-2  text-ink-muted ',
    closed: 'bg-surface-2 text-ink',
};

const statusLabels: Record<string, string> = {
    draft: 'Brouillon',
    published: 'Publié',
    archived: 'Archivé',
    closed: 'Clôturé',
};

export default function Index({
    projects,
    canCreateProject,
    currentPlanName,
    maxActiveProjects,
}: {
    projects: ProjectRow[];
    canCreateProject: boolean;
    currentPlanName: string;
    maxActiveProjects: number;
}) {
    const { confirm, ConfirmDialog } = useConfirm();

    function publish(slug: string) {
        router.post(`/entrepreneur/projects/${slug}/publish`);
    }

    async function archive(slug: string) {
        if (
            await confirm(
                'Archiver ce projet ? Vous pourrez alors en créer un nouveau.',
            )
        ) {
            router.post(`/entrepreneur/projects/${slug}/archive`);
        }
    }

    return (
        <>
            <Head title="Mes projets" />
            <div className="mx-auto max-w-4xl px-6 py-12">
                <BackLink href="/dashboard" />
                <div className="mb-8 flex items-center justify-between">
                    <div>
                        <p className="text-eyebrow">Entrepreneur</p>
                        <h1 className="mt-2 text-page-title">Mes projets</h1>
                    </div>
                    {canCreateProject ? (
                        <Link
                            href="/entrepreneur/projects/create"
                            variant="cta"
                            className="flex items-center gap-2 py-2.5"
                        >
                            <RiAddLine size={16} /> Nouveau projet
                        </Link>
                    ) : (
                        <span className="border border-rule bg-surface-2 px-4 py-2.5 text-caption">
                            Limite du plan {currentPlanName} atteinte (
                            {maxActiveProjects} projet
                            {maxActiveProjects > 1 ? 's' : ''} actif
                            {maxActiveProjects > 1 ? 's' : ''} maximum) —
                            archivez un projet pour en créer un autre.
                        </span>
                    )}
                </div>

                {projects.length === 0 ? (
                    <div className="border border-rule-strong bg-paper p-6 text-body">
                        Vous n'avez encore aucun projet.
                    </div>
                ) : (
                    <div className="border border-rule">
                        {projects.map((project) => (
                            <div key={project.id}>
                                {project.fundingLimitWarning?.shouldShow && (
                                    <div className="border-b border-rule p-5">
                                        <FundingLimitWarning
                                            projectSlug={project.slug}
                                            projectTitle={project.title}
                                            currentAmount={
                                                project.fundingLimitWarning
                                                    .currentAmount
                                            }
                                            maxLimit={
                                                project.fundingLimitWarning
                                                    .maxLimit
                                            }
                                            currency={project.fundingCurrency}
                                            deadline={
                                                project.fundingLimitWarning
                                                    .deadline
                                            }
                                            deadlinePassed={
                                                project.fundingLimitWarning
                                                    .deadlinePassed
                                            }
                                            daysRemaining={
                                                project.fundingLimitWarning
                                                    .daysRemaining
                                            }
                                        />
                                    </div>
                                )}
                                <div className="flex items-center justify-between border-b border-rule p-5 last:border-b-0">
                                    <div>
                                        <div className="mb-1 flex items-center gap-2">
                                            <span
                                                className={`px-2 py-0.5 font-mono text-lg uppercase ${statusStyles[project.status]}`}
                                            >
                                                {statusLabels[project.status]}
                                            </span>
                                            {project.sectors[0] && (
                                                <span className="font-mono text-lg text-ink-muted uppercase">
                                                    {project.sectors[0].name}
                                                </span>
                                            )}
                                        </div>
                                        <h3 className="text-item-title">
                                            {project.title}
                                        </h3>
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
                                                onClick={() =>
                                                    publish(project.slug)
                                                }
                                                className="flex items-center gap-1.5 py-2"
                                            >
                                                <RiRocketLine size={14} />{' '}
                                                Publier
                                            </Button>
                                        )}
                                        {project.status !== 'archived' && (
                                            <button
                                                onClick={() =>
                                                    archive(project.slug)
                                                }
                                                className="flex items-center gap-1.5 border border-rule-strong px-3 py-2 text-btn text-ink-muted hover:border-ink hover:text-ink"
                                            >
                                                <RiArchiveLine size={14} />{' '}
                                                Archiver
                                            </button>
                                        )}
                                    </div>
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
