import { RiAlertLine, RiEditLine } from '@remixicon/react';
import { Link } from '@inertiajs/react';

type FundingLimitWarningProps = {
    projectSlug: string;
    projectTitle: string;
    currentAmount: string;
    maxLimit: string;
    currency: string;
    deadline: string;
    deadlinePassed: boolean;
    daysRemaining?: number;
};

/**
 * Composant d'avertissement pour les projets dont le montant de financement
 * dépasse les limites autorisées par la plateforme.
 *
 * Affiche une bannière ambre avec un message clair et un bouton d'action
 * permettant à l'entrepreneur de modifier son projet.
 */
export function FundingLimitWarning({
    projectSlug,
    projectTitle,
    currentAmount,
    maxLimit,
    currency,
    deadline,
    deadlinePassed,
    daysRemaining,
}: FundingLimitWarningProps) {
    return (
        <div
            className={`mb-4 rounded-lg border p-4 ${
                deadlinePassed
                    ? 'border-danger-muted bg-danger-subtle'
                    : 'border-warning-muted bg-warning-subtle'
            }`}
            role="alert"
            aria-live="polite"
        >
            <div className="flex items-start gap-3">
                <div
                    className={`mt-0.5 flex-shrink-0 ${
                        deadlinePassed ? 'text-danger' : 'text-warning'
                    }`}
                >
                    <RiAlertLine size={20} />
                </div>
                <div className="flex-1">
                    <h3 className="mb-1 font-semibold text-ink">
                        Montant de financement hors limites
                    </h3>
                    <p className="mb-2 text-xl text-ink-muted">
                        Le montant de financement de votre projet{' '}
                        <strong>"{projectTitle}"</strong> ({currentAmount}{' '}
                        {currency}) dépasse la limite maximale autorisée (
                        {maxLimit} {currency}).
                    </p>
                    {!deadlinePassed && daysRemaining !== undefined && (
                        <p className="mb-3 text-xl font-medium text-ink">
                            {daysRemaining === 0 ? (
                                <span className="text-danger">
                                    ⏰ Date limite : aujourd'hui
                                </span>
                            ) : daysRemaining === 1 ? (
                                <span className="text-warning">
                                    ⏰ Il vous reste 1 jour pour corriger ce
                                    montant (date limite : {deadline})
                                </span>
                            ) : (
                                <span>
                                    ⏰ Il vous reste {daysRemaining} jours pour
                                    corriger ce montant (date limite :{' '}
                                    {deadline})
                                </span>
                            )}
                        </p>
                    )}
                    {deadlinePassed && (
                        <p className="mb-3 text-xl font-medium text-danger">
                            ⚠️ La date limite ({deadline}) est dépassée.
                            Veuillez corriger le montant dès que possible pour
                            maintenir la conformité de votre projet.
                        </p>
                    )}
                    <Link
                        href={`/entrepreneur/projects/${projectSlug}/edit`}
                        className="inline-flex items-center gap-2 rounded-md bg-ink px-4 py-2 text-xl font-semibold text-paper hover:opacity-90"
                    >
                        <RiEditLine size={16} />
                        Modifier mon projet
                    </Link>
                </div>
            </div>
        </div>
    );
}
