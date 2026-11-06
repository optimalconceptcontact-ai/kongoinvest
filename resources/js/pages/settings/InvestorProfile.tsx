import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { RiBuildingLine, RiGlobalLine, RiMapPinLine } from '@remixicon/react';
import { BackLink } from '@/components/ui/BackLink';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Field } from '@/components/ui/Field';
import { FieldError } from '@/components/ui/FieldError';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/Label';
import { Select } from '@/components/ui/select';
import { Textarea } from '@/components/ui/Textarea';

type Props = {
    profile: {
        countryCode: string | null;
        city: string | null;
        investorType: string | null;
        fundingType: string | null;
        investmentHorizon: string | null;
        riskAppetite: string | null;
        ticketMinMinor: number | null;
        ticketMaxMinor: number | null;
        ticketCurrency: string;
        preferredGeographies: string[];
        investmentThesis: string | null;
        impactFocused: boolean;
        onboardingCompleted: boolean;
        companyName: string | null;
        sectors: { id: number }[];
        stages: { id: number }[];
    };
    sectors: { id: number; name: string }[];
    stages: { id: number; name: string }[];
};

const INVESTOR_TYPES = [
    ['individual', 'Investisseur individuel'],
    ['angel_investor', 'Business Angel'],
    ['venture_capital', 'Capital-risque (VC)'],
    ['private_equity', 'Private Equity'],
    ['family_office', 'Family Office'],
    ['corporate_investor', "Investisseur d'entreprise"],
    [
        'development_finance_institution',
        'Institution de financement du développement',
    ],
    ['ngo', 'ONG / Fondation'],
    ['other', 'Autre'],
] as const;

const RISK_LEVELS = [
    ['conservative', 'Prudent'],
    ['moderate', 'Modéré'],
    ['aggressive', 'Agressif'],
] as const;

export default function InvestorProfile({ profile, sectors, stages }: Props) {
    const {
        data,
        setData,
        put,
        transform,
        processing,
        errors,
        recentlySuccessful,
    } = useForm({
        country_code: profile.countryCode ?? '',
        city: profile.city ?? '',
        investor_type: profile.investorType ?? '',
        risk_appetite: profile.riskAppetite ?? '',
        ticket_min_minor: profile.ticketMinMinor
            ? profile.ticketMinMinor / 100
            : '',
        ticket_max_minor: profile.ticketMaxMinor
            ? profile.ticketMaxMinor / 100
            : '',
        ticket_currency: profile.ticketCurrency ?? 'USD',
        investment_thesis: profile.investmentThesis ?? '',
        impact_focused: profile.impactFocused,
        company: { name: profile.companyName ?? '' },
        sectors: profile.sectors.map((s) => s.id),
        stages: profile.stages.map((s) => s.id),
    });

    function toggle(field: 'sectors' | 'stages', id: number) {
        const current = data[field];
        setData(
            field,
            current.includes(id)
                ? current.filter((x) => x !== id)
                : [...current, id],
        );
    }

    function submit(e: FormEvent) {
        e.preventDefault();
        // Convert display units (whole currency) back to minor units
        // before submitting — the backend never accepts a float amount.
        // transform() is a method, not a submit option (see the same
        // fix applied in Form.tsx and MatchingWeights/Index.tsx).
        transform((d) => ({
            ...d,
            ticket_min_minor: d.ticket_min_minor
                ? Math.round(Number(d.ticket_min_minor) * 100)
                : null,
            ticket_max_minor: d.ticket_max_minor
                ? Math.round(Number(d.ticket_max_minor) * 100)
                : null,
        }));
        put('/settings/investor-profile');
    }

    return (
        <>
            <Head title="Profil investisseur" />
            <div className="mx-auto max-w-2xl px-6 py-12">
                <BackLink href="/dashboard" />
                <p className="text-eyebrow">Paramètres</p>
                <h1 className="mt-2 mb-1 text-page-title">
                    Profil investisseur
                </h1>
                <p className="mb-8 text-body">
                    Ces informations alimentent le moteur de matching IA — plus
                    elles sont précises, meilleures seront vos recommandations
                    de projets.
                </p>

                {recentlySuccessful && (
                    <div className="mb-6 border border-rule-strong bg-surface-2 px-4 py-3 text-lg leading-5 text-ink-muted">
                        Profil enregistré.
                    </div>
                )}

                <form onSubmit={submit} className="flex flex-col gap-5">
                    <div className="grid grid-cols-2 gap-4">
                        <Field error={!!errors.country_code}>
                            <Label>Pays (code ISO)</Label>
                            <Input
                                icon={<RiGlobalLine size={18} />}
                                autoComplete="country"
                                maxLength={2}
                                placeholder="US"
                                value={data.country_code}
                                onChange={(e) =>
                                    setData(
                                        'country_code',
                                        e.target.value.toUpperCase(),
                                    )
                                }
                                className="uppercase"
                                error={!!errors.country_code}
                            />
                            <FieldError>{errors.country_code}</FieldError>
                        </Field>
                        <Field error={!!errors.city}>
                            <Label>Ville</Label>
                            <Input
                                icon={<RiMapPinLine size={18} />}
                                autoComplete="address-level2"
                                value={data.city}
                                onChange={(e) =>
                                    setData('city', e.target.value)
                                }
                                error={!!errors.city}
                            />
                            <FieldError>{errors.city}</FieldError>
                        </Field>
                    </div>

                    <Field error={!!errors.investor_type}>
                        <Label>Type d'investisseur</Label>
                        <Select
                            value={data.investor_type}
                            onChange={(e) =>
                                setData('investor_type', e.target.value)
                            }
                            error={!!errors.investor_type}
                        >
                            <option value="">—</option>
                            {INVESTOR_TYPES.map(([value, label]) => (
                                <option key={value} value={value}>
                                    {label}
                                </option>
                            ))}
                        </Select>
                        <FieldError>{errors.investor_type}</FieldError>
                    </Field>

                    <fieldset
                        aria-describedby={
                            errors.risk_appetite
                                ? 'risk-appetite-error'
                                : undefined
                        }
                    >
                        <legend className="text-form-label mb-2">
                            Appétence au risque
                        </legend>
                        <div className="flex gap-2">
                            {RISK_LEVELS.map(([value, label]) => (
                                <button
                                    type="button"
                                    key={value}
                                    aria-pressed={data.risk_appetite === value}
                                    onClick={() =>
                                        setData('risk_appetite', value)
                                    }
                                    className={`min-h-11 flex-1 border px-3 py-2 text-btn ${
                                        data.risk_appetite === value
                                            ? 'border-ink bg-ink text-paper'
                                            : 'border-rule-strong text-ink hover:border-ink'
                                    }`}
                                >
                                    {label}
                                </button>
                            ))}
                        </div>
                        {errors.risk_appetite && (
                            <FieldError id="risk-appetite-error">
                                {errors.risk_appetite}
                            </FieldError>
                        )}
                    </fieldset>

                    <div className="grid grid-cols-3 gap-3">
                        <Field error={!!errors.ticket_min_minor}>
                            <Label>Ticket min.</Label>
                            <Input
                                type="number"
                                min={0}
                                value={data.ticket_min_minor}
                                onChange={(e) =>
                                    setData('ticket_min_minor', e.target.value)
                                }
                                suffix={data.ticket_currency}
                                error={!!errors.ticket_min_minor}
                            />
                            <FieldError>{errors.ticket_min_minor}</FieldError>
                        </Field>
                        <Field error={!!errors.ticket_max_minor}>
                            <Label>Ticket max.</Label>
                            <Input
                                type="number"
                                min={0}
                                value={data.ticket_max_minor}
                                onChange={(e) =>
                                    setData('ticket_max_minor', e.target.value)
                                }
                                suffix={data.ticket_currency}
                                error={!!errors.ticket_max_minor}
                            />
                            <FieldError>{errors.ticket_max_minor}</FieldError>
                        </Field>
                        <Field error={!!errors.ticket_currency}>
                            <Label>Devise</Label>
                            <Select
                                value={data.ticket_currency}
                                onChange={(e) =>
                                    setData('ticket_currency', e.target.value)
                                }
                                error={!!errors.ticket_currency}
                            >
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                                <option value="CDF">CDF</option>
                            </Select>
                            <FieldError>{errors.ticket_currency}</FieldError>
                        </Field>
                    </div>

                    <Field error={!!errors.investment_thesis}>
                        <Label>Thèse d'investissement</Label>
                        <Textarea
                            rows={4}
                            value={data.investment_thesis}
                            onChange={(e) =>
                                setData('investment_thesis', e.target.value)
                            }
                            error={!!errors.investment_thesis}
                        />
                        <FieldError>{errors.investment_thesis}</FieldError>
                    </Field>

                    <Checkbox
                        label="Je privilégie les projets à impact social/environnemental"
                        checked={data.impact_focused}
                        onChange={(e) =>
                            setData('impact_focused', e.target.checked)
                        }
                        className="gap-2"
                    />

                    <fieldset className="border border-rule p-4">
                        <legend className="px-1 font-mono text-lg text-ink-muted uppercase">
                            Structure (optionnel)
                        </legend>
                        <Input
                            icon={<RiBuildingLine size={18} />}
                            aria-label="Nom du fonds ou de la structure"
                            autoComplete="organization"
                            placeholder="Nom du fonds / de la structure"
                            value={data.company.name}
                            onChange={(e) =>
                                setData('company', { name: e.target.value })
                            }
                        />
                    </fieldset>

                    <fieldset
                        aria-describedby={
                            errors.sectors ? 'sectors-error' : undefined
                        }
                    >
                        <legend className="text-form-label mb-2">
                            Secteurs préférés
                        </legend>
                        <div className="flex flex-wrap gap-2">
                            {sectors.map((s) => (
                                <button
                                    type="button"
                                    key={s.id}
                                    aria-pressed={data.sectors.includes(s.id)}
                                    onClick={() => toggle('sectors', s.id)}
                                    className={`min-h-11 border px-3 py-1.5 text-btn ${
                                        data.sectors.includes(s.id)
                                            ? 'border-ink bg-ink text-paper'
                                            : 'border-rule-strong text-ink hover:border-ink'
                                    }`}
                                >
                                    {s.name}
                                </button>
                            ))}
                        </div>
                        {errors.sectors && (
                            <FieldError id="sectors-error">
                                {errors.sectors}
                            </FieldError>
                        )}
                    </fieldset>

                    <fieldset
                        aria-describedby={
                            errors.stages ? 'stages-error' : undefined
                        }
                    >
                        <legend className="text-form-label mb-2">
                            Stades préférés
                        </legend>
                        <div className="flex flex-wrap gap-2">
                            {stages.map((s) => (
                                <button
                                    type="button"
                                    key={s.id}
                                    aria-pressed={data.stages.includes(s.id)}
                                    onClick={() => toggle('stages', s.id)}
                                    className={`min-h-11 border px-3 py-1.5 text-btn ${
                                        data.stages.includes(s.id)
                                            ? 'border-ink bg-ink text-paper'
                                            : 'border-rule-strong text-ink hover:border-ink'
                                    }`}
                                >
                                    {s.name}
                                </button>
                            ))}
                        </div>
                        {errors.stages && (
                            <FieldError id="stages-error">
                                {errors.stages}
                            </FieldError>
                        )}
                    </fieldset>

                    <Button
                        type="submit"
                        disabled={processing}
                        className="self-start px-6 py-3"
                    >
                        {processing ? 'Enregistrement...' : 'Enregistrer'}
                    </Button>
                </form>
            </div>
        </>
    );
}
