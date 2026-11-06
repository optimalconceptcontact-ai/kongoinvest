import type { InputHTMLAttributes, ReactNode } from 'react';
import { useField } from '@/components/ui/Field';
import {
    inputShellClasses,
    sizeClasses,
    type InputSize,
} from '@/components/ui/input';
import { cn } from '@/lib/utils';

/** Common dial codes for the DRC plus the main sources of diaspora investors. */
export const PHONE_DIAL_CODES = [
    { code: 'CD', dial: '+243', label: 'RDC' },
    { code: 'CG', dial: '+242', label: 'Congo' },
    { code: 'GA', dial: '+241', label: 'Gabon' },
    { code: 'AO', dial: '+244', label: 'Angola' },
    { code: 'RW', dial: '+250', label: 'Rwanda' },
    { code: 'UG', dial: '+256', label: 'Ouganda' },
    { code: 'TZ', dial: '+255', label: 'Tanzanie' },
    { code: 'BI', dial: '+257', label: 'Burundi' },
    { code: 'ZA', dial: '+27', label: 'Afrique du Sud' },
    { code: 'BE', dial: '+32', label: 'Belgique' },
    { code: 'FR', dial: '+33', label: 'France' },
    { code: 'MA', dial: '+212', label: 'Maroc' },
    { code: 'US', dial: '+1', label: 'USA / Canada' },
    { code: 'GB', dial: '+44', label: 'Royaume-Uni' },
    { code: 'CN', dial: '+86', label: 'Chine' },
] as const;

const DEFAULT_DIAL = '+243';

/** Dial codes sorted longest first so +243 wins over +24/. */
const DIALS = [...PHONE_DIAL_CODES]
    .sort((a, b) => b.dial.length - a.dial.length)
    .map((c) => c.dial);

/**
 * Splits a stored phone value into a dial code and the national part.
 * Unknown dial codes (e.g. "+995") are surfaced as-is so the country
 * select can show an "Autre" option instead of inventing a default.
 */
export function splitPhone(value: string): {
    dial: string;
    national: string;
    known: boolean;
} {
    const v = value.trim();
    if (!v) {
        return { dial: DEFAULT_DIAL, national: '', known: true };
    }
    for (const dial of DIALS) {
        if (v.startsWith(dial)) {
            return {
                dial,
                national: v.slice(dial.length).trim(),
                known: true,
            };
        }
    }
    const match = /^\+\d{1,3}/.exec(v);
    if (match) {
        return {
            dial: match[0],
            national: v.slice(match[0].length).trim(),
            known: false,
        };
    }
    return { dial: DEFAULT_DIAL, national: v, known: false };
}

type PhoneInputProps = Omit<
    InputHTMLAttributes<HTMLInputElement>,
    'size' | 'value' | 'onChange'
> & {
    size?: InputSize;
    error?: boolean;
    /** Full stored value, e.g. "+243 812 345 678". */
    value: string;
    /** Receives the recomposed full value (dial + national part). */
    onChange: (value: string) => void;
};

export function PhoneInput({
    size = 'default',
    error,
    value,
    onChange,
    className,
    id,
    disabled,
    placeholder = '812 345 678',
    'aria-describedby': ariaDescribedBy,
    'aria-invalid': ariaInvalid,
    ...props
}: PhoneInputProps) {
    const field = useField();
    const showError = error || field?.hasError;
    const describedBy =
        [showError ? field?.errorId : null, ariaDescribedBy]
            .filter(Boolean)
            .join(' ') || undefined;

    const { dial, national } = splitPhone(value ?? '');
    const dialIsListed = PHONE_DIAL_CODES.some((c) => c.dial === dial);

    return (
        <span
            className={cn(
                inputShellClasses(showError, size),
                'gap-1.5 pl-2 pr-3',
                className,
            )}
        >
            <select
                aria-label="Indicatif du pays"
                value={dial}
                disabled={disabled}
                onChange={(e) => onChange(e.target.value + national)}
                className="shrink-0 cursor-pointer appearance-none bg-transparent py-0 font-mono text-xl text-ink-muted/70 focus:outline-none disabled:cursor-not-allowed"
            >
                {!dialIsListed && (
                    <option value={dial}>{dial} (autre)</option>
                )}
                {PHONE_DIAL_CODES.map((c) => (
                    <option key={c.code} value={c.dial}>
                        {c.code} {c.dial} · {c.label}
                    </option>
                ))}
            </select>
            <span aria-hidden className="h-6 w-px shrink-0 bg-rule-strong/50" />
            <input
                id={id ?? field?.controlId}
                aria-describedby={describedBy}
                aria-invalid={ariaInvalid ?? (showError || undefined)}
                disabled={disabled}
                placeholder={placeholder}
                value={national}
                onChange={(e) => {
                    const national = e.target.value.replace(/^\+/, '');
                    onChange(dial + national);
                }}
                className={cn(
                    'w-full min-w-0 flex-1 self-stretch py-2 pl-1 text-inherit placeholder:text-ink-muted/70 focus:outline-none disabled:cursor-not-allowed disabled:text-ink-muted',
                    sizeClasses[size],
                )}
                {...props}
            />
        </span>
    );
}
