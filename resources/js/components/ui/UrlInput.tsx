import { Input, type InputSize } from '@/components/ui/input';
import type { InputHTMLAttributes, ReactNode } from 'react';

const URL_SCHEME = /^[a-z][a-z0-9+.-]*:\/\//i;

/**
 * Splits a stored URL into its scheme (defaulting to https://) and the
 * remainder, shown as the editable part after the muted prefix token.
 */
export function splitUrl(value: string): { scheme: string; path: string } {
    const v = value.trim();
    const match = URL_SCHEME.exec(v);
    if (!match || !/^https?:\/\//i.test(match[0])) {
        // Unknown schemes (ftp, mailto...) stay editable without a prefix.
        return { scheme: 'https://', path: v };
    }
    return { scheme: match[0], path: v.slice(match[0].length) };
}

type UrlInputProps = Omit<
    InputHTMLAttributes<HTMLInputElement>,
    'size' | 'type' | 'value' | 'onChange'
> & {
    size?: InputSize;
    error?: boolean;
    /** Optional decorative leading icon (e.g. RiGlobalLine). */
    icon?: ReactNode;
    /** Full stored URL, e.g. "https://exemple.com". */
    value: string;
    onChange: (value: string) => void;
};

export function UrlInput({
    value,
    onChange,
    placeholder = 'exemple.com',
    icon,
    ...props
}: UrlInputProps) {
    const { scheme, path } = splitUrl(value ?? '');

    return (
        <Input
            icon={icon}
            prefix={scheme}
            inputMode="url"
            autoCapitalize="none"
            spellCheck={false}
            placeholder={placeholder}
            // The visible part is the path only; typing a full URL is
            // accepted and normalised in whatever scheme is displayed.
            value={path}
            onChange={(e) => {
                const path = e.target.value.replace(URL_SCHEME, '');
                onChange(path ? `${scheme}${path}` : '');
            }}
            {...props}
        />
    );
}
