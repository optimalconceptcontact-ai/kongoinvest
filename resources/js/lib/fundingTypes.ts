// Mirrors App\Enums\FundingType — single source of truth for the
// investment-type options, shared by the project form (entrepreneur picks
// exactly 3) and the Deal Room contract trigger (investor picks 1 of those 3).
export const FUNDING_TYPES: [string, string][] = [
    ['equity', 'Prise de participation'],
    ['debt', 'Dette'],
    ['convertible_note', 'Obligation convertible'],
    ['grant', 'Subvention'],
    ['revenue_share', 'Partage de revenus'],
    ['other', 'Autre'],
];

const LABELS = Object.fromEntries(FUNDING_TYPES);

export function fundingTypeLabel(value: string): string {
    return LABELS[value] ?? value;
}
