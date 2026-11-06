import { Head, router, useForm } from '@inertiajs/react';
import {
    RiBriefcaseLine,
    RiCloseLine,
    RiDeleteBinLine,
    RiDownloadLine,
    RiFileTextLine,
    RiFileUploadLine,
    RiImageAddLine,
    RiMailLine,
    RiUserAddLine,
    RiUserLine,
} from '@remixicon/react';
import type { FormEvent } from 'react';
import { useRef, useState } from 'react';
import { CoachPanel } from '@/components/AI/CoachPanel';
import type { AiConversationData } from '@/components/AI/InvestorAdvisorPanel';
import { BackLink } from '@/components/ui/BackLink';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/Field';
import { FieldError } from '@/components/ui/FieldError';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/Label';
import { PhoneInput } from '@/components/ui/PhoneInput';
import { UrlInput } from '@/components/ui/UrlInput';
import { Select } from '@/components/ui/select';
import { Textarea } from '@/components/ui/Textarea';
import { useConfirm } from '@/hooks/useConfirm';
import { DRC_PROVINCES, DRC_PROVINCE_CITIES } from '@/lib/drcProvinces';
import { FUNDING_TYPES } from '@/lib/fundingTypes';

type Taxonomy = { id: number; name?: string; key?: string }[];

type TeamMember = {
    full_name: string;
    role: string;
    linkedin: string;
    email: string;
    phone: string;
};
const EMPTY_TEAM_MEMBER: TeamMember = {
    full_name: '',
    role: '',
    linkedin: '',
    email: '',
    phone: '',
};

type ProjectImage = { id: number; url: string; thumbUrl: string; name: string };
type ProjectDocument = {
    id: number;
    name: string;
    size: number;
    downloadUrl: string;
};
type ProjectMedia = { images: ProjectImage[]; documents: ProjectDocument[] };

function formatFileSize(bytes: number) {
    if (bytes < 1024) {
        return `${bytes} o`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(0)} Ko`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} Mo`;
}

/**
 * Limites de financement par devise (en unités majeures, ex: dollars, pas centimes).
 * Ces valeurs correspondent aux limites définies dans FundingLimitService.php
 */
const FUNDING_LIMITS: Record<string, { min: number; max: number }> = {
    USD: { min: 2_000, max: 100_000 },
    CDF: { min: 5_000_000, max: 250_000_000 },
    EUR: { min: 2_000, max: 100_000 },
};

/**
 * Retourne le texte d'aide indiquant les limites de financement pour une devise donnée.
 */
function getFundingLimitHint(currency: string): string {
    const limits = FUNDING_LIMITS[currency];
    if (!limits) {
        return '';
    }

    const formatAmount = (amount: number) => {
        return amount.toLocaleString('fr-FR');
    };

    return `Montant entre ${formatAmount(limits.min)} et ${formatAmount(limits.max)} ${currency}`;
}

/**
 * Media attaches to an existing project row (Spatie Media Library), so
 * uploads only happen on the edit page — store() already redirects
 * straight there after creation for exactly this reason.
 */
function useMediaUpload(
    projectSlug: string,
    collection: 'project-images' | 'project-documents',
) {
    const [uploading, setUploading] = useState(false);
    const inputRef = useRef<HTMLInputElement>(null);
    const { confirm, ConfirmDialog } = useConfirm();

    function upload(files: FileList | null) {
        if (!files || files.length === 0) {
            return;
        }

        const formData = new FormData();
        formData.append('collection', collection);
        Array.from(files).forEach((file) => formData.append('files[]', file));

        setUploading(true);
        router.post(`/entrepreneur/projects/${projectSlug}/media`, formData, {
            forceFormData: true,
            preserveScroll: true,
            onFinish: () => {
                setUploading(false);

                if (inputRef.current) {
                    inputRef.current.value = '';
                }
            },
        });
    }

    async function remove(mediaId: number) {
        if (
            !(await confirm({
                description: 'Supprimer ce fichier ?',
                danger: true,
                confirmLabel: 'Supprimer',
            }))
        ) {
            return;
        }

        router.delete(
            `/entrepreneur/projects/${projectSlug}/media/${mediaId}`,
            { preserveScroll: true },
        );
    }

    return { inputRef, uploading, upload, remove, ConfirmDialog };
}

type ProjectFormData = {
    id: number;
    slug: string;
    title: string;
    description: string;
    executiveSummary: string | null;
    problem: string | null;
    solution: string | null;
    businessModel: string | null;
    market: string | null;
    competitiveAdvantage: string | null;
    team: TeamMember[];
    traction: string | null;
    financialInformation: string | null;
    impact: string | null;
    risks: string | null;
    fundingTargetMinor: number | null;
    fundingMinMinor?: number | null;
    fundingMaxMinor?: number | null;
    fundingCurrency: string;
    investmentTypes: string[];
    provinces: string[];
    cities: string[];
    website?: string | null;
    sectors: { id: number }[];
    stages: { id: number }[];
} | null;

type Props = {
    project: ProjectFormData;
    sectors: Taxonomy;
    stages: Taxonomy;
    hasCoachFeature?: boolean;
    coachConversation?: AiConversationData | null;
    media?: ProjectMedia;
};

function TextArea({
    label,
    value,
    onChange,
    rows = 4,
    error,
}: {
    label: string;
    value: string;
    onChange: (v: string) => void;
    rows?: number;
    error?: string;
}) {
    return (
        <Field error={!!error}>
            <Label>{label}</Label>
            <Textarea
                rows={rows}
                value={value}
                onChange={(e) => onChange(e.target.value)}
                error={!!error}
            />
            <FieldError>{error}</FieldError>
        </Field>
    );
}

export default function Form({
    project,
    sectors,
    stages,
    hasCoachFeature,
    coachConversation,
    media,
}: Props) {
    const isEdit = project !== null;
    const {
        inputRef: imageInputRef,
        uploading: isImageUploading,
        upload: uploadImage,
        remove: removeImage,
        ConfirmDialog: imageConfirmDialog,
    } = useMediaUpload(project?.slug ?? '', 'project-images');
    const {
        inputRef: documentInputRef,
        uploading: isDocumentUploading,
        upload: uploadDocument,
        remove: removeDocument,
        ConfirmDialog: documentConfirmDialog,
    } = useMediaUpload(project?.slug ?? '', 'project-documents');

    const {
        data,
        setData,
        post,
        put,
        transform: transformData,
        processing,
        errors,
    } = useForm({
        title: project?.title ?? '',
        description: project?.description ?? '',
        executive_summary: project?.executiveSummary ?? '',
        problem: project?.problem ?? '',
        solution: project?.solution ?? '',
        business_model: project?.businessModel ?? '',
        market: project?.market ?? '',
        competitive_advantage: project?.competitiveAdvantage ?? '',
        team: project?.team ?? ([] as TeamMember[]),
        traction: project?.traction ?? '',
        financial_information: project?.financialInformation ?? '',
        impact: project?.impact ?? '',
        risks: project?.risks ?? '',
        funding_target_minor: project?.fundingTargetMinor
            ? project.fundingTargetMinor / 100
            : '',
        funding_min_minor: project?.fundingMinMinor
            ? project.fundingMinMinor / 100
            : '',
        funding_max_minor: project?.fundingMaxMinor
            ? project.fundingMaxMinor / 100
            : '',
        funding_currency: project?.fundingCurrency ?? 'USD',
        investment_types: project?.investmentTypes ?? ([] as string[]),
        provinces: project?.provinces ?? ([] as string[]),
        cities: project?.cities ?? ([] as string[]),
        website: project?.website ?? '',
        sectors: project?.sectors.map((s) => s.id) ?? ([] as number[]),
        stages: project?.stages.map((s) => s.id) ?? ([] as number[]),
    });

    // A project is at exactly one stage of maturity — picking one always
    // replaces the previous choice, never adds to it.
    const isIdeaStage =
        stages.find((s) => s.id === data.stages[0])?.key === 'idea';

    // Every city available across whichever province(s) are currently
    // selected — city choice always spans however many provinces there
    // are, one or several.
    const availableCities = Array.from(
        new Set(data.provinces.flatMap((p) => DRC_PROVINCE_CITIES[p] ?? [])),
    );

    function selectStage(id: number) {
        setData((current) => {
            const stillIdea = stages.find((s) => s.id === id)?.key === 'idea';
            // Leaving the Idea stage with several provinces picked would
            // silently violate the single-province rule — trim to the
            // first one instead of letting the switch hide the problem.
            const provinces = stillIdea
                ? current.provinces
                : current.provinces.slice(0, 1);
            const validCities = new Set(
                provinces.flatMap((p) => DRC_PROVINCE_CITIES[p] ?? []),
            );

            return {
                ...current,
                stages: [id],
                provinces,
                cities: current.cities.filter((c) => validCities.has(c)),
            };
        });
    }

    function toggleInvestmentType(value: string) {
        if (data.investment_types.includes(value)) {
            setData(
                'investment_types',
                data.investment_types.filter((x) => x !== value),
            );
        } else if (data.investment_types.length < 3) {
            setData('investment_types', [...data.investment_types, value]);
        }
    }

    function toggleSector(id: number) {
        if (data.sectors.includes(id)) {
            setData(
                'sectors',
                data.sectors.filter((x) => x !== id),
            );
        } else if (data.sectors.length < 5) {
            setData('sectors', [...data.sectors, id]);
        }
    }

    function toggleProvince(value: string) {
        setData((current) => {
            const provinces = current.provinces.includes(value)
                ? current.provinces.filter((x) => x !== value)
                : current.provinces.length < 3
                  ? [...current.provinces, value]
                  : current.provinces;
            const validCities = new Set(
                provinces.flatMap((p) => DRC_PROVINCE_CITIES[p] ?? []),
            );

            return {
                ...current,
                provinces,
                cities: current.cities.filter((c) => validCities.has(c)),
            };
        });
    }

    function selectProvince(value: string) {
        setData((current) => ({
            ...current,
            provinces: [value],
            cities: current.cities.filter((c) =>
                (DRC_PROVINCE_CITIES[value] ?? []).includes(c),
            ),
        }));
    }

    function addTeamMember() {
        setData('team', [...data.team, { ...EMPTY_TEAM_MEMBER }]);
    }

    function updateTeamMember(
        index: number,
        field: keyof TeamMember,
        value: string,
    ) {
        setData(
            'team',
            data.team.map((m, i) =>
                i === index ? { ...m, [field]: value } : m,
            ),
        );
    }

    function removeTeamMember(index: number) {
        setData(
            'team',
            data.team.filter((_, i) => i !== index),
        );
    }

    function toggleCity(city: string) {
        if (data.cities.includes(city)) {
            setData(
                'cities',
                data.cities.filter((x) => x !== city),
            );
        } else {
            setData('cities', [...data.cities, city]);
        }
    }

    function submit(e: FormEvent) {
        e.preventDefault();
        // Inertia's transform() is a method, not a submit option — see
        // the identical fix in Admin/MatchingWeights/Index.tsx.
        transformData((d) => ({
            ...d,
            funding_target_minor: d.funding_target_minor
                ? Math.round(Number(d.funding_target_minor) * 100)
                : null,
            funding_min_minor: d.funding_min_minor
                ? Math.round(Number(d.funding_min_minor) * 100)
                : null,
            funding_max_minor: d.funding_max_minor
                ? Math.round(Number(d.funding_max_minor) * 100)
                : null,
        }));

        if (isEdit) {
            put(`/entrepreneur/projects/${project!.slug}`);
        } else {
            post('/entrepreneur/projects');
        }
    }

    return (
        <>
            <Head title={isEdit ? 'Modifier le projet' : 'Nouveau projet'} />
            <div className="mx-auto max-w-3xl px-6 py-12">
                <BackLink href="/entrepreneur/projects" />
                <p className="text-eyebrow">Entrepreneur</p>
                <h1 className="mt-2 mb-8 text-page-title">
                    {isEdit ? 'Modifier le projet' : 'Nouveau projet'}
                </h1>

                <form onSubmit={submit} className="flex flex-col gap-6">
                    <Field error={!!errors.title}>
                        <Label>Titre</Label>
                        <Input
                            icon={<RiFileTextLine size={18} />}
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            error={!!errors.title}
                        />
                        <FieldError>{errors.title}</FieldError>
                    </Field>

                    <TextArea
                        label="Description courte"
                        value={data.description}
                        onChange={(v) => setData('description', v)}
                        error={errors.description}
                    />

                    <fieldset>
                        <legend className="text-form-label mb-2">
                            Secteurs (jusqu'à 5)
                        </legend>
                        <div
                            className="flex flex-wrap gap-2"
                            role="group"
                            aria-label="Secteurs (jusqu'à 5)"
                        >
                            {sectors.map((s) => (
                                <button
                                    type="button"
                                    key={s.id}
                                    aria-pressed={data.sectors.includes(s.id)}
                                    onClick={() => toggleSector(s.id)}
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
                        <FieldError>{errors.sectors}</FieldError>
                    </fieldset>

                    <fieldset>
                        <legend className="text-form-label mb-2">Stades</legend>
                        <div
                            className="flex flex-wrap gap-2"
                            role="group"
                            aria-label="Stades"
                        >
                            {stages.map((s) => (
                                <button
                                    type="button"
                                    key={s.id}
                                    aria-pressed={data.stages.includes(s.id)}
                                    onClick={() => selectStage(s.id)}
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
                        <FieldError>{errors.stages}</FieldError>
                    </fieldset>

                    <div className="grid grid-cols-2 gap-4">
                        <Field error={!!errors.provinces}>
                            <Label>
                                {isIdeaStage
                                    ? "Provinces envisagées (jusqu'à 3)"
                                    : 'Province (RDC)'}
                            </Label>
                            {isIdeaStage ? (
                                <div
                                    className="flex flex-wrap gap-2"
                                    role="group"
                                    aria-label="Provinces envisagées"
                                >
                                    {DRC_PROVINCES.map(([value, label]) => (
                                        <button
                                            type="button"
                                            key={value}
                                            aria-pressed={data.provinces.includes(
                                                value,
                                            )}
                                            onClick={() =>
                                                toggleProvince(value)
                                            }
                                            className={`min-h-11 border px-3 py-1.5 text-btn ${
                                                data.provinces.includes(value)
                                                    ? 'border-ink bg-ink text-paper'
                                                    : 'border-rule-strong text-ink hover:border-ink'
                                            }`}
                                        >
                                            {label}
                                        </button>
                                    ))}
                                </div>
                            ) : (
                                <Select
                                    value={data.provinces[0] ?? ''}
                                    onChange={(e) =>
                                        selectProvince(e.target.value)
                                    }
                                >
                                    <option value="">
                                        — Selectionner province
                                    </option>
                                    {DRC_PROVINCES.map(([value, label]) => (
                                        <option key={value} value={value}>
                                            {label}
                                        </option>
                                    ))}
                                </Select>
                            )}
                            <FieldError>{errors.provinces}</FieldError>
                        </Field>
                        <Field error={!!errors.cities}>
                            <Label>
                                Ville
                                {data.provinces.length > 1
                                    ? 's (dans les provinces choisies)'
                                    : ''}
                            </Label>
                            {availableCities.length > 0 ? (
                                <div
                                    className="flex flex-wrap gap-2"
                                    role="group"
                                    aria-label="Villes"
                                >
                                    {availableCities.map((c) => (
                                        <button
                                            type="button"
                                            key={c}
                                            aria-pressed={data.cities.includes(
                                                c,
                                            )}
                                            onClick={() => toggleCity(c)}
                                            className={`min-h-11 border px-3 py-1.5 text-2xl font-semibold ${
                                                data.cities.includes(c)
                                                    ? 'border-ink bg-ink text-paper'
                                                    : 'border-rule-strong text-ink hover:border-ink'
                                            }`}
                                        >
                                            {c}
                                        </button>
                                    ))}
                                </div>
                            ) : (
                                <Input
                                    value=""
                                    disabled
                                    placeholder="Choisissez une province"
                                />
                            )}
                            <FieldError>{errors.cities}</FieldError>
                        </Field>
                    </div>

                    <div className="grid grid-cols-3 gap-3">
                        <Field error={!!errors.funding_target_minor}>
                            <Label>Objectif de financement</Label>
                            <Input
                                type="number"
                                min={0}
                                suffix={data.funding_currency}
                                value={data.funding_target_minor}
                                onChange={(e) =>
                                    setData(
                                        'funding_target_minor',
                                        e.target.value,
                                    )
                                }
                                error={!!errors.funding_target_minor}
                            />
                            {!errors.funding_target_minor && (
                                <p className="mt-1 text-caption text-ink-muted">
                                    {getFundingLimitHint(data.funding_currency)}
                                </p>
                            )}
                            <FieldError>
                                {errors.funding_target_minor}
                            </FieldError>
                        </Field>
                        <Field error={!!errors.funding_min_minor}>
                            <Label>Ticket min.</Label>
                            <Input
                                type="number"
                                min={0}
                                suffix={data.funding_currency}
                                value={data.funding_min_minor}
                                onChange={(e) =>
                                    setData('funding_min_minor', e.target.value)
                                }
                                error={!!errors.funding_min_minor}
                            />
                            <FieldError>{errors.funding_min_minor}</FieldError>
                        </Field>
                        <Field error={!!errors.funding_currency}>
                            <Label>Devise</Label>
                            <Select
                                value={data.funding_currency}
                                onChange={(e) =>
                                    setData('funding_currency', e.target.value)
                                }
                                error={!!errors.funding_currency}
                            >
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                                <option value="CDF">CDF</option>
                            </Select>
                            <FieldError>{errors.funding_currency}</FieldError>
                        </Field>
                    </div>

                    <div>
                        <Label>
                            Types de financement acceptés (exactement 3)
                        </Label>
                        <div
                            className="flex flex-wrap gap-2"
                            role="group"
                            aria-label="Types de financement acceptés"
                        >
                            {FUNDING_TYPES.map(([value, label]) => (
                                <button
                                    type="button"
                                    key={value}
                                    aria-pressed={data.investment_types.includes(
                                        value,
                                    )}
                                    onClick={() => toggleInvestmentType(value)}
                                    className={`min-h-11 border px-3 py-1.5 text-btn ${
                                        data.investment_types.includes(value)
                                            ? 'border-ink bg-ink text-paper'
                                            : 'border-rule-strong text-ink hover:border-ink'
                                    }`}
                                >
                                    {label}
                                </button>
                            ))}
                        </div>
                        <p className="mt-1 text-caption">
                            L'investisseur choisira l'un de ces 3 types lors de
                            la génération du contrat par IA dans le Deal Room.
                        </p>
                        <FieldError>{errors.investment_types}</FieldError>
                    </div>

                    <TextArea
                        label="Résumé exécutif"
                        value={data.executive_summary}
                        onChange={(v) => setData('executive_summary', v)}
                    />
                    <TextArea
                        label="Problème"
                        value={data.problem}
                        onChange={(v) => setData('problem', v)}
                    />
                    <TextArea
                        label="Solution"
                        value={data.solution}
                        onChange={(v) => setData('solution', v)}
                    />
                    <TextArea
                        label="Modèle économique"
                        value={data.business_model}
                        onChange={(v) => setData('business_model', v)}
                    />
                    <TextArea
                        label="Marché"
                        value={data.market}
                        onChange={(v) => setData('market', v)}
                    />
                    <TextArea
                        label="Avantage concurrentiel"
                        value={data.competitive_advantage}
                        onChange={(v) => setData('competitive_advantage', v)}
                    />
                    <div>
                        <div className="text-form-label mb-2">Équipe</div>
                        <div className="flex flex-col gap-4">
                            {data.team.map((member, i) => (
                                <div key={i} className="border border-rule p-4">
                                    <div className="mb-3 flex items-center justify-between">
                                        <span className="text-caption">
                                            Membre {i + 1}
                                        </span>
                                        <button
                                            type="button"
                                            onClick={() => removeTeamMember(i)}
                                            aria-label="Retirer ce membre"
                                            className="flex h-10 w-10 items-center justify-center text-danger hover:opacity-80"
                                        >
                                            <RiDeleteBinLine size={16} />
                                        </button>
                                    </div>
                                    <div className="grid grid-cols-2 gap-3">
                                        <Field
                                            error={
                                                !!errors[`team.${i}.full_name`]
                                            }
                                        >
                                            <Label>Nom complet</Label>
                                            <Input
                                                icon={<RiUserLine size={18} />}
                                                autoComplete="name"
                                                value={member.full_name}
                                                onChange={(e) =>
                                                    updateTeamMember(
                                                        i,
                                                        'full_name',
                                                        e.target.value,
                                                    )
                                                }
                                                error={
                                                    !!errors[
                                                        `team.${i}.full_name`
                                                    ]
                                                }
                                            />
                                            <FieldError>
                                                {errors[`team.${i}.full_name`]}
                                            </FieldError>
                                        </Field>
                                        <Field
                                            error={!!errors[`team.${i}.role`]}
                                        >
                                            <Label>Fonction</Label>
                                            <Input
                                                icon={
                                                    <RiBriefcaseLine
                                                        size={18}
                                                    />
                                                }
                                                autoComplete="organization-title"
                                                value={member.role}
                                                onChange={(e) =>
                                                    updateTeamMember(
                                                        i,
                                                        'role',
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="Ex : Co-fondatrice, CTO..."
                                                error={
                                                    !!errors[`team.${i}.role`]
                                                }
                                            />
                                            <FieldError>
                                                {errors[`team.${i}.role`]}
                                            </FieldError>
                                        </Field>
                                        <Field
                                            error={!!errors[`team.${i}.email`]}
                                        >
                                            <Label>Email</Label>
                                            <Input
                                                type="email"
                                                icon={<RiMailLine size={18} />}
                                                autoComplete="email"
                                                value={member.email}
                                                onChange={(e) =>
                                                    updateTeamMember(
                                                        i,
                                                        'email',
                                                        e.target.value,
                                                    )
                                                }
                                                error={
                                                    !!errors[`team.${i}.email`]
                                                }
                                            />
                                            <FieldError>
                                                {errors[`team.${i}.email`]}
                                            </FieldError>
                                        </Field>
                                        <Field
                                            error={!!errors[`team.${i}.phone`]}
                                        >
                                            <Label>Téléphone</Label>
                                            <PhoneInput
                                                autoComplete="tel"
                                                value={member.phone}
                                                onChange={(phone) =>
                                                    updateTeamMember(
                                                        i,
                                                        'phone',
                                                        phone,
                                                    )
                                                }
                                                placeholder="812 345 678"
                                                error={
                                                    !!errors[`team.${i}.phone`]
                                                }
                                            />
                                            <FieldError>
                                                {errors[`team.${i}.phone`]}
                                            </FieldError>
                                        </Field>
                                        <Field
                                            className="col-span-2"
                                            error={
                                                !!errors[`team.${i}.linkedin`]
                                            }
                                        >
                                            <Label>
                                                LinkedIn (si disponible)
                                            </Label>
                                            <UrlInput
                                                value={member.linkedin}
                                                onChange={(linkedin) =>
                                                    updateTeamMember(
                                                        i,
                                                        'linkedin',
                                                        linkedin,
                                                    )
                                                }
                                                placeholder="linkedin.com/in/..."
                                                error={
                                                    !!errors[
                                                        `team.${i}.linkedin`
                                                    ]
                                                }
                                            />
                                            <FieldError>
                                                {errors[`team.${i}.linkedin`]}
                                            </FieldError>
                                        </Field>
                                    </div>
                                </div>
                            ))}
                        </div>
                        {data.team.length === 0 && (
                            <p className="mb-3 text-caption">
                                Aucun membre ajouté pour l'instant.
                            </p>
                        )}
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={addTeamMember}
                            className="mt-3 flex items-center gap-1.5"
                        >
                            <RiUserAddLine size={14} /> Ajouter un membre
                        </Button>
                    </div>
                    <TextArea
                        label="Traction"
                        value={data.traction}
                        onChange={(v) => setData('traction', v)}
                    />
                    <TextArea
                        label="Informations financières"
                        value={data.financial_information}
                        onChange={(v) => setData('financial_information', v)}
                    />
                    <TextArea
                        label="Impact"
                        value={data.impact}
                        onChange={(v) => setData('impact', v)}
                    />
                    <TextArea
                        label="Risques"
                        value={data.risks}
                        onChange={(v) => setData('risks', v)}
                    />

                    {isEdit ? (
                        <>
                            <div>
                                <div className="text-form-label mb-2">
                                    Photos du projet
                                </div>
                                <div className="flex flex-wrap gap-3">
                                    {media?.images.map((img) => (
                                        <div
                                            key={img.id}
                                            className="group relative h-24 w-24 border border-rule"
                                        >
                                            <img
                                                src={img.thumbUrl}
                                                alt={img.name}
                                                className="h-full w-full object-cover"
                                            />
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    removeImage(img.id)
                                                }
                                                aria-label="Supprimer la photo"
                                                className="absolute top-1 right-1 hidden h-5 w-5 items-center justify-center bg-ink/80 text-paper group-hover:flex"
                                            >
                                                <RiCloseLine size={14} />
                                            </button>
                                        </div>
                                    ))}
                                    <button
                                        type="button"
                                        onClick={() =>
                                            imageInputRef.current?.click()
                                        }
                                        disabled={isImageUploading}
                                        className="flex h-24 w-24 flex-col items-center justify-center gap-1 border border-dashed border-rule-strong text-ink-muted hover:border-ink hover:text-ink disabled:opacity-50"
                                    >
                                        <RiImageAddLine size={20} />
                                        <span className="text-caption">
                                            {isImageUploading
                                                ? 'Envoi...'
                                                : 'Ajouter'}
                                        </span>
                                    </button>
                                    <input
                                        ref={imageInputRef}
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp"
                                        multiple
                                        className="hidden"
                                        onChange={(e) =>
                                            uploadImage(e.target.files)
                                        }
                                    />
                                </div>
                                <p className="mt-1 text-caption">
                                    JPG, PNG ou WebP, 5 Mo max par image.
                                </p>
                            </div>

                            <div>
                                <div className="text-form-label mb-2">
                                    Documents (pitch deck, brevets, états
                                    financiers...)
                                </div>
                                {media && media.documents.length > 0 && (
                                    <div className="mb-2 flex flex-col gap-2">
                                        {media.documents.map((doc) => (
                                            <div
                                                key={doc.id}
                                                className="flex items-center justify-between border border-rule px-3 py-2"
                                            >
                                                <div className="flex items-center gap-2 overflow-hidden">
                                                    <RiFileTextLine
                                                        size={16}
                                                        className="flex-shrink-0 text-ink-muted"
                                                    />
                                                    <span className="truncate text-lg text-ink">
                                                        {doc.name}
                                                    </span>
                                                    <span className="flex-shrink-0 text-caption">
                                                        {formatFileSize(
                                                            doc.size,
                                                        )}
                                                    </span>
                                                </div>
                                                <div className="flex flex-shrink-0 items-center gap-3">
                                                    <a
                                                        href={doc.downloadUrl}
                                                        aria-label="Télécharger"
                                                        className="text-ink-muted hover:text-ink"
                                                    >
                                                        <RiDownloadLine
                                                            size={16}
                                                        />
                                                    </a>
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            removeDocument(
                                                                doc.id,
                                                            )
                                                        }
                                                        aria-label="Supprimer le document"
                                                        className="text-ink-muted hover:text-danger"
                                                    >
                                                        <RiDeleteBinLine
                                                            size={16}
                                                        />
                                                    </button>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() =>
                                        documentInputRef.current?.click()
                                    }
                                    disabled={isDocumentUploading}
                                    className="flex items-center gap-1.5"
                                >
                                    <RiFileUploadLine size={14} />{' '}
                                    {isDocumentUploading
                                        ? 'Envoi...'
                                        : 'Ajouter un document'}
                                </Button>
                                <input
                                    ref={documentInputRef}
                                    type="file"
                                    accept=".pdf,.doc,.docx"
                                    multiple
                                    className="hidden"
                                    onChange={(e) =>
                                        uploadDocument(e.target.files)
                                    }
                                />
                                <p className="mt-1 text-caption">
                                    PDF ou Word, 20 Mo max par fichier.
                                </p>
                            </div>
                        </>
                    ) : (
                        <p className="text-caption">
                            Les photos et documents (pitch deck, brevets...) se
                            gèrent depuis cette même page une fois le projet
                            créé.
                        </p>
                    )}

                    <Button
                        type="submit"
                        disabled={processing}
                        className="self-start px-6 py-3"
                    >
                        {processing
                            ? 'Enregistrement...'
                            : isEdit
                              ? 'Enregistrer'
                              : 'Créer le projet'}
                    </Button>
                </form>

                {isEdit && hasCoachFeature && (
                    <div className="mt-8">
                        <CoachPanel
                            projectId={project!.id}
                            conversation={coachConversation ?? null}
                        />
                    </div>
                )}
                {isEdit && !hasCoachFeature && (
                    <div className="mt-8 border border-rule bg-surface-2 p-5 text-caption">
                        Le Coach IA d'amélioration est disponible avec le plan
                        Entrepreneur.{' '}
                        <a
                            href="/settings/subscription"
                            className="text-action underline"
                        >
                            Voir les plans
                        </a>
                    </div>
                )}
            </div>
            {imageConfirmDialog}
            {documentConfirmDialog}
        </>
    );
}
