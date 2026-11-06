import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import {
    RiBriefcaseLine,
    RiBuildingLine,
    RiMapPinLine,
} from '@remixicon/react';
import { BackLink } from '@/components/ui/BackLink';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/Field';
import { FieldError } from '@/components/ui/FieldError';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/Label';
import { UrlInput } from '@/components/ui/UrlInput';
import { Select } from '@/components/ui/select';
import { Textarea } from '@/components/ui/Textarea';

type Props = {
    profile: {
        jobTitle: string | null;
        bio: string | null;
        experience: string | null;
        province: string | null;
        city: string | null;
        yearsExperience: number | null;
        onboardingCompleted: boolean;
        companyName: string | null;
        companyWebsite: string | null;
    };
};

// Kept in sync manually with App\Enums\DrcProvince — a future pass can
// generate this from the backend enum instead of duplicating it.
const PROVINCES = [
    ['kinshasa', 'Kinshasa'],
    ['kongo_central', 'Kongo-Central'],
    ['kwango', 'Kwango'],
    ['kwilu', 'Kwilu'],
    ['mai_ndombe', 'Mai-Ndombe'],
    ['kasai', 'Kasaï'],
    ['kasai_central', 'Kasaï-Central'],
    ['kasai_oriental', 'Kasaï-Oriental'],
    ['lomami', 'Lomami'],
    ['sankuru', 'Sankuru'],
    ['maniema', 'Maniema'],
    ['sud_kivu', 'Sud-Kivu'],
    ['nord_kivu', 'Nord-Kivu'],
    ['ituri', 'Ituri'],
    ['haut_uele', 'Haut-Uélé'],
    ['tshopo', 'Tshopo'],
    ['bas_uele', 'Bas-Uélé'],
    ['nord_ubangi', 'Nord-Ubangi'],
    ['mongala', 'Mongala'],
    ['sud_ubangi', 'Sud-Ubangi'],
    ['equateur', 'Équateur'],
    ['tshuapa', 'Tshuapa'],
    ['haut_katanga', 'Haut-Katanga'],
    ['haut_lomami', 'Haut-Lomami'],
    ['lualaba', 'Lualaba'],
    ['tanganyika', 'Tanganyika'],
] as const;

export default function EntrepreneurProfile({ profile }: Props) {
    const { data, setData, put, processing, errors, recentlySuccessful } =
        useForm({
            job_title: profile.jobTitle ?? '',
            bio: profile.bio ?? '',
            experience: profile.experience ?? '',
            province: profile.province ?? '',
            city: profile.city ?? '',
            years_experience: profile.yearsExperience ?? '',
            company: {
                name: profile.companyName ?? '',
                website: profile.companyWebsite ?? '',
            },
        });

    function submit(e: FormEvent) {
        e.preventDefault();
        // PUT /settings/entrepreneur-profile
        put('/settings/entrepreneur-profile');
    }

    return (
        <>
            <Head title="Profil entrepreneur" />
            <div className="mx-auto max-w-2xl px-6 py-12">
                <BackLink href="/dashboard" />
                <p className="text-eyebrow">Paramètres</p>
                <h1 className="mt-2 mb-1 text-page-title">
                    Profil entrepreneur
                </h1>
                <p className="mb-8 text-body">
                    Votre projet est implanté en République Démocratique du
                    Congo — ce champ n'est pas modifiable ici. Seules la
                    province et la ville le sont.
                </p>

                {recentlySuccessful && (
                    <div className="mb-6 border border-rule-strong bg-surface-2 px-4 py-3 text-lg leading-5 text-ink-muted">
                        Profil enregistré.
                    </div>
                )}

                <form onSubmit={submit} className="flex flex-col gap-5">
                    <Field error={!!errors.job_title}>
                        <Label>Fonction</Label>
                        <Input
                            icon={<RiBriefcaseLine size={18} />}
                            autoComplete="organization-title"
                            value={data.job_title}
                            onChange={(e) =>
                                setData('job_title', e.target.value)
                            }
                            error={!!errors.job_title}
                        />
                        <FieldError>{errors.job_title}</FieldError>
                    </Field>

                    <div className="grid grid-cols-2 gap-4">
                        <Field>
                            <Label>Pays</Label>
                            <Input
                                disabled
                                value="République Démocratique du Congo"
                                className="border-rule bg-surface-2 text-ink-muted"
                            />
                        </Field>
                        <Field error={!!errors.province}>
                            <Label>Province</Label>
                            <Select
                                value={data.province}
                                onChange={(e) =>
                                    setData('province', e.target.value)
                                }
                                error={!!errors.province}
                            >
                                <option value="">—</option>
                                {PROVINCES.map(([value, label]) => (
                                    <option key={value} value={value}>
                                        {label}
                                    </option>
                                ))}
                            </Select>
                            <FieldError>{errors.province}</FieldError>
                        </Field>
                    </div>

                    <Field error={!!errors.city}>
                        <Label>Ville</Label>
                        <Input
                            icon={<RiMapPinLine size={18} />}
                            autoComplete="address-level2"
                            value={data.city}
                            onChange={(e) => setData('city', e.target.value)}
                            error={!!errors.city}
                        />
                        <FieldError>{errors.city}</FieldError>
                    </Field>

                    <Field error={!!errors.years_experience}>
                        <Label>Années d'expérience</Label>
                        <Input
                            type="number"
                            min={0}
                            suffix="ans"
                            value={data.years_experience}
                            onChange={(e) =>
                                setData('years_experience', e.target.value)
                            }
                            error={!!errors.years_experience}
                        />
                        <FieldError>{errors.years_experience}</FieldError>
                    </Field>

                    <Field error={!!errors.bio}>
                        <Label>Bio</Label>
                        <Textarea
                            rows={4}
                            value={data.bio}
                            onChange={(e) => setData('bio', e.target.value)}
                            error={!!errors.bio}
                        />
                        <FieldError>{errors.bio}</FieldError>
                    </Field>

                    <fieldset className="border border-rule p-4">
                        <legend className="px-1 font-mono text-lg text-ink-muted uppercase">
                            Société (optionnel)
                        </legend>
                        <div className="flex flex-col gap-3">
                            <Input
                                icon={<RiBuildingLine size={18} />}
                                aria-label="Nom de la société"
                                autoComplete="organization"
                                placeholder="Nom de la société"
                                value={data.company.name}
                                onChange={(e) =>
                                    setData('company', {
                                        ...data.company,
                                        name: e.target.value,
                                    })
                                }
                            />
                            <UrlInput
                                aria-label="Site web de la société"
                                autoComplete="organization url"
                                value={data.company.website}
                                onChange={(website) =>
                                    setData('company', {
                                        ...data.company,
                                        website,
                                    })
                                }
                            />
                        </div>
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
