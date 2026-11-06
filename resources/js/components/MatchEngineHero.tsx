import {
    animate,
    motion,
    useMotionValue,
    useReducedMotion,
    useTransform,
} from 'framer-motion';
import { useEffect } from 'react';
import { easeSoft } from '@/lib/animations';

/**
 * Illustration du moteur de matching pour le hero de la page d'accueil.
 * Elle reprend le vocabulaire des pages produit — filets, typographie mono,
 * score expliqué critère par critère — au lieu d'un objet 3D décoratif.
 * Purement illustrative : aucune donnée réelle n'est chargée ici.
 */

const MATCH_SCORE = 84;

const CRITERIA = [
    { label: 'Secteur', score: 92, source: 'Règle' },
    { label: 'Modèle économique', score: 81, source: 'IA' },
    { label: 'Financement', score: 78, source: 'Règle' },
    { label: 'Stade', score: 85, source: 'Règle' },
    { label: 'Géographie', score: 90, source: 'Règle' },
] as const;

function Entity({
    label,
    name,
    location,
    align = 'left',
}: {
    label: string;
    name: string;
    location: string;
    align?: 'left' | 'right';
}) {
    return (
        <div className={align === 'right' ? 'text-right' : ''}>
            <div className="text-eyebrow">{label}</div>
            <div className="mt-1.5 font-mono text-[16px] leading-5 font-medium text-ink sm:text-[20px]">
                {name}
            </div>
            <div className="text-caption">{location}</div>
        </div>
    );
}

export default function MatchEngineHero() {
    const reducedMotion = useReducedMotion();
    const score = useMotionValue(0);
    const displayScore = useTransform(score, (value) =>
        Math.round(value).toString(),
    );

    useEffect(() => {
        if (reducedMotion) {
            score.set(MATCH_SCORE);

            return;
        }

        const controls = animate(score, MATCH_SCORE, {
            duration: 1.1,
            ease: easeSoft,
        });

        return () => controls.stop();
    }, [reducedMotion, score]);

    return (
        <div aria-hidden="true" className="flex h-full w-full flex-col">
            <div className="flex items-center justify-between border-b border-rule px-4 py-2 sm:px-5">
                <span className="text-eyebrow">Moteur de matching</span>
                <span className="border border-rule px-1.5 py-0.5 font-mono text-[14px] tracking-[0.12em] text-ink-muted uppercase">
                    Exemple
                </span>
            </div>

            <div className="flex flex-1 flex-col justify-center gap-3 p-4 sm:gap-4 sm:p-5 lg:gap-5 lg:p-6">
                {/* Les deux parties mises en relation */}
                <div>
                    <div className="grid grid-cols-2 gap-4">
                        <Entity
                            label="Projet"
                            name="Agro-transformation"
                            location="Kinshasa · RDC"
                        />
                        <Entity
                            label="Investisseur"
                            name="Impact Fund"
                            location="Bruxelles · BE"
                            align="right"
                        />
                    </div>
                    <div className="mt-3 flex items-center">
                        <span className="h-1.5 w-1.5 shrink-0 bg-ink" />
                        <span className="h-px flex-1 bg-rule-strong" />
                        <span className="mx-1 h-2 w-2 shrink-0 bg-signal" />
                        <span className="h-px flex-1 bg-rule-strong" />
                        <span className="h-1.5 w-1.5 shrink-0 bg-ink" />
                    </div>
                </div>

                {/* Score global */}
                <div className="border-t border-rule pt-3 sm:pt-4">
                    <div className="flex items-baseline gap-2">
                        <motion.span className="font-mono text-[3.75rem] leading-none font-bold tracking-[-0.05em] text-ink tabular-nums sm:text-[4.5rem] lg:text-[8rem]">
                            {displayScore}
                        </motion.span>
                        <span className="font-mono text-lg text-ink-muted">
                            / 100
                        </span>
                    </div>
                    <div className="mt-2 flex items-center gap-2">
                        <span className="h-[3px] w-5 shrink-0 bg-signal" />
                        <span className="text-eyebrow">Compatibilité</span>
                    </div>
                    <div className="mt-3 h-1 w-full bg-rule">
                        <motion.div
                            className="h-full origin-left bg-signal"
                            initial={reducedMotion ? false : { scaleX: 0 }}
                            animate={{ scaleX: MATCH_SCORE / 100 }}
                            transition={{
                                duration: 0.9,
                                ease: easeSoft,
                                delay: 0.25,
                            }}
                        />
                    </div>
                </div>

                {/* Décomposition critère par critère */}
                <div>
                    {CRITERIA.map((criterion, index) => (
                        <div
                            key={criterion.label}
                            className={`${
                                index > 2 ? 'hidden sm:flex' : 'flex'
                            } items-center gap-3 border-t border-rule py-1.5 lg:py-2`}
                        >
                            <span className="w-[124px] shrink-0 truncate font-mono text-[12px] text-ink sm:w-[136px]">
                                {criterion.label}
                            </span>
                            <div className="h-1 flex-1 bg-rule">
                                <motion.div
                                    className="h-full origin-left bg-action"
                                    initial={
                                        reducedMotion ? false : { scaleX: 0 }
                                    }
                                    animate={{
                                        scaleX: criterion.score / 100,
                                    }}
                                    transition={{
                                        duration: 0.7,
                                        ease: easeSoft,
                                        delay: 0.35 + index * 0.08,
                                    }}
                                />
                            </div>
                            <span className="w-6 shrink-0 text-right font-mono text-[12px] text-ink tabular-nums">
                                {criterion.score}
                            </span>
                            <span className="hidden w-11 shrink-0 text-right font-mono text-[14px] text-ink-muted sm:block">
                                {criterion.source}
                            </span>
                        </div>
                    ))}
                </div>
            </div>

            <div className="flex items-center justify-between border-t border-rule px-4 py-2 font-mono text-[14px] text-ink-muted sm:px-5">
                <span>Score 0–100</span>
                <span>RDC · Monde</span>
            </div>
        </div>
    );
}
