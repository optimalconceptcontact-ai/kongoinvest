import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import { Button } from '@/components/Ui/Button';
import { Checkbox } from '@/components/Ui/Checkbox';
import { FieldError } from '@/components/Ui/FieldError';
import { Input } from '@/components/Ui/Input';
import { Label } from '@/components/Ui/Label';
import { Select } from '@/components/Ui/Select';
import { Textarea } from '@/components/Ui/Textarea';

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
    ['individual', 'Investisseur individuel'], ['angel_investor', 'Business Angel'],
    ['venture_capital', 'Capital-risque (VC)'], ['private_equity', 'Private Equity'],
    ['family_office', 'Family Office'], ['corporate_investor', "Investisseur d'entreprise"],
    ['development_finance_institution', 'Institution de financement du développement'],
    ['ngo', 'ONG / Fondation'], ['other', 'Autre'],
] as const;

const RISK_LEVELS = [['conservative', 'Prudent'], ['moderate', 'Modéré'], ['aggressive', 'Agressif']] as const;

export default function InvestorProfile({ profile, sectors, stages }: Props) {
    const { data, setData, put, transform, processing, errors, recentlySuccessful } = useForm({
        country_code: profile.countryCode ?? '',
        city: profile.city ?? '',
        investor_type: profile.investorType ?? '',
        risk_appetite: profile.riskAppetite ?? '',
        ticket_min_minor: profile.ticketMinMinor ? profile.ticketMinMinor / 100 : '',
        ticket_max_minor: profile.ticketMaxMinor ? profile.ticketMaxMinor / 100 : '',
        ticket_currency: profile.ticketCurrency ?? 'USD',
        investment_thesis: profile.investmentThesis ?? '',
        impact_focused: profile.impactFocused,
        company: { name: profile.companyName ?? '' },
        sectors: profile.sectors.map((s) => s.id),
        stages: profile.stages.map((s) => s.id),
    });

    function toggle(field: 'sectors' | 'stages', id: number) {
        const current = data[field];
        setData(field, current.includes(id) ? current.filter((x) => x !== id) : [...current, id]);
    }

    function submit(e: FormEvent) {
        e.preventDefault();
        // Convert display units (whole currency) back to minor units
        // before submitting — the backend never accepts a float amount.
        // transform() is a method, not a submit option (see the same
        // fix applied in Form.tsx and MatchingWeights/Index.tsx).
        transform((d) => ({
            ...d,
            ticket_min_minor: d.ticket_min_minor ? Math.round(Number(d.ticket_min_minor) * 100) : null,
            ticket_max_minor: d.ticket_max_minor ? Math.round(Number(d.ticket_max_minor) * 100) : null,
        }));
        put('/settings/investor-profile');
    }

    return (
        <>
            <Head title="Profil investisseur" />
            <div className="mx-auto max-w-2xl px-6 py-12">
                <p className="text-eyebrow">Paramètres</p>
                <h1 className="mt-2 mb-1 text-page-title">Profil investisseur</h1>
                <p className="mb-8 text-body">
                    Ces informations alimentent le moteur de matching IA — plus elles sont précises, meilleures
                    seront vos recommandations de projets.
                </p>

                {recentlySuccessful && (
                    <div className="mb-6 border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700">
                        Profil enregistré.
                    </div>
                )}

                <form onSubmit={submit} className="flex flex-col gap-5">
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label>Pays (code ISO)</Label>
                            <Input
                                maxLength={2}
                                placeholder="US"
                                value={data.country_code}
                                onChange={(e) => setData('country_code', e.target.value.toUpperCase())}
                                className="uppercase"
                                error={!!errors.country_code}
                            />
                            <FieldError>{errors.country_code}</FieldError>
                        </div>
                        <div>
                            <Label>Ville</Label>
                            <Input value={data.city} onChange={(e) => setData('city', e.target.value)} />
                        </div>
                    </div>

                    <div>
                        <Label>Type d'investisseur</Label>
                        <Select value={data.investor_type} onChange={(e) => setData('investor_type', e.target.value)} error={!!errors.investor_type}>
                            <option value="">—</option>
                            {INVESTOR_TYPES.map(([value, label]) => (
                                <option key={value} value={value}>{label}</option>
                            ))}
                        </Select>
                        <FieldError>{errors.investor_type}</FieldError>
                    </div>

                    <div>
                        <Label>Appétence au risque</Label>
                        <div className="flex gap-2">
                            {RISK_LEVELS.map(([value, label]) => (
                                <button
                                    type="button"
                                    key={value}
                                    onClick={() => setData('risk_appetite', value)}
                                    className={`flex-1 border px-3 py-2 text-xs font-semibold ${
                                        data.risk_appetite === value
                                            ? 'border-slate-900 bg-slate-900 text-white'
                                            : 'border-slate-300 text-slate-700 hover:border-slate-900'
                                    }`}
                                >
                                    {label}
                                </button>
                            ))}
                        </div>
                    </div>

                    <div className="grid grid-cols-3 gap-3">
                        <div>
                            <Label>Ticket min.</Label>
                            <Input
                                type="number"
                                min={0}
                                value={data.ticket_min_minor}
                                onChange={(e) => setData('ticket_min_minor', e.target.value)}
                            />
                        </div>
                        <div>
                            <Label>Ticket max.</Label>
                            <Input
                                type="number"
                                min={0}
                                value={data.ticket_max_minor}
                                onChange={(e) => setData('ticket_max_minor', e.target.value)}
                                error={!!errors.ticket_max_minor}
                            />
                            <FieldError>{errors.ticket_max_minor}</FieldError>
                        </div>
                        <div>
                            <Label>Devise</Label>
                            <Select value={data.ticket_currency} onChange={(e) => setData('ticket_currency', e.target.value)}>
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                                <option value="CDF">CDF</option>
                            </Select>
                        </div>
                    </div>

                    <div>
                        <Label>Thèse d'investissement</Label>
                        <Textarea rows={4} value={data.investment_thesis} onChange={(e) => setData('investment_thesis', e.target.value)} />
                    </div>

                    <Checkbox
                        label="Je privilégie les projets à impact social/environnemental"
                        checked={data.impact_focused}
                        onChange={(e) => setData('impact_focused', e.target.checked)}
                        className="gap-2"
                    />

                    <fieldset className="border border-slate-200 p-4">
                        <legend className="px-1 font-mono text-xs uppercase text-slate-500">Structure (optionnel)</legend>
                        <Input
                            placeholder="Nom du fonds / de la structure"
                            value={data.company.name}
                            onChange={(e) => setData('company', { name: e.target.value })}
                        />
                    </fieldset>

                    <div>
                        <Label className="mb-2">Secteurs préférés</Label>
                        <div className="flex flex-wrap gap-2">
                            {sectors.map((s) => (
                                <button
                                    type="button"
                                    key={s.id}
                                    onClick={() => toggle('sectors', s.id)}
                                    className={`border px-3 py-1.5 text-xs font-medium ${
                                        data.sectors.includes(s.id)
                                            ? 'border-slate-900 bg-slate-900 text-white'
                                            : 'border-slate-300 text-slate-700 hover:border-slate-900'
                                    }`}
                                >
                                    {s.name}
                                </button>
                            ))}
                        </div>
                    </div>

                    <div>
                        <Label className="mb-2">Stades préférés</Label>
                        <div className="flex flex-wrap gap-2">
                            {stages.map((s) => (
                                <button
                                    type="button"
                                    key={s.id}
                                    onClick={() => toggle('stages', s.id)}
                                    className={`border px-3 py-1.5 text-xs font-medium ${
                                        data.stages.includes(s.id)
                                            ? 'border-slate-900 bg-slate-900 text-white'
                                            : 'border-slate-300 text-slate-700 hover:border-slate-900'
                                    }`}
                                >
                                    {s.name}
                                </button>
                            ))}
                        </div>
                    </div>

                    <Button type="submit" disabled={processing} className="self-start px-6 py-3">
                        {processing ? 'Enregistrement...' : 'Enregistrer'}
                    </Button>
                </form>
            </div>
        </>
    );
}
