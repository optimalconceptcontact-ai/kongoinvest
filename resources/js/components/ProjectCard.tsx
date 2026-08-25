import { Link } from '@inertiajs/react';
import { RiMapPinLine } from '@remixicon/react';
import { motion } from 'framer-motion';
import { easeSoft } from '@/lib/animations';
import { provinceLabel } from '@/lib/drcProvinces';

export type ProjectCardData = {
    slug: string;
    title: string;
    description: string;
    provinces: string[];
    cities: string[];
    fundingTargetMinor: number | null;
    fundingRaisedMinor: number | null;
    fundingCurrency: string;
    coverImageUrl: string | null;
    sectors: { id: number; name: string }[];
};

function formatAmount(minor: number | null, currency: string) {
    if (minor === null) {
        return null;
    }

    return `${(minor / 100).toLocaleString('fr-FR')} ${currency}`;
}

export function ProjectCard({ project }: { project: ProjectCardData }) {
    const target = formatAmount(
        project.fundingTargetMinor,
        project.fundingCurrency,
    );
    const raised = formatAmount(
        project.fundingRaisedMinor,
        project.fundingCurrency,
    );
    const progress =
        project.fundingTargetMinor && project.fundingTargetMinor > 0
            ? Math.min(
                  100,
                  Math.round(
                      ((project.fundingRaisedMinor ?? 0) /
                          project.fundingTargetMinor) *
                          100,
                  ),
              )
            : 0;

    return (
        <motion.div
            whileHover={{ y: -2 }}
            transition={{ type: 'spring', stiffness: 320, damping: 30 }}
            className="h-full"
        >
            <Link
                href={`/projects/${project.slug}`}
                className="group flex h-full flex-col border border-slate-300 bg-white transition hover:border-slate-900"
            >
                <div className="aspect-[16/10] w-full overflow-hidden bg-slate-100">
                    {project.coverImageUrl ? (
                        <img
                            src={project.coverImageUrl}
                            alt=""
                            className="h-full w-full object-cover"
                        />
                    ) : (
                        <div className="flex h-full w-full items-center justify-center font-mono text-xs text-slate-400">
                            Pas de visuel
                        </div>
                    )}
                </div>
                <div className="flex flex-1 flex-col p-5">
                    {project.sectors[0] && (
                        <div className="mb-2 font-mono text-[11px] tracking-wide text-orange-600 uppercase">
                            {project.sectors[0].name}
                        </div>
                    )}
                    <h3 className="mb-2 text-item-title-lg group-hover:text-blue-700">
                        {project.title}
                    </h3>
                    <p className="mb-4 line-clamp-2 text-body">
                        {project.description}
                    </p>

                    {target && (
                        <div className="mb-3">
                            <div className="h-1.5 w-full bg-slate-100">
                                <motion.div
                                    className="h-full origin-left bg-blue-500"
                                    style={{ width: `${progress}%` }}
                                    initial={{ scaleX: 0 }}
                                    whileInView={{ scaleX: 1 }}
                                    viewport={{ once: true }}
                                    transition={{
                                        duration: 0.3,
                                        ease: easeSoft,
                                    }}
                                />
                            </div>
                            <div className="mt-1 flex justify-between font-mono text-[11px] text-slate-500">
                                <span>{raised ?? '0'} levés</span>
                                <span>Objectif {target}</span>
                            </div>
                        </div>
                    )}

                    <div className="mt-auto flex items-center gap-1 border-t border-slate-100 pt-3 text-caption">
                        <RiMapPinLine size={14} />
                        {[
                            project.cities.join(' / '),
                            project.provinces.map(provinceLabel).join(' / '),
                        ]
                            .filter(Boolean)
                            .join(', ') || 'RDC'}
                    </div>
                </div>
            </Link>
        </motion.div>
    );
}
