import {
    useLayoutEffect,
    useRef,
    useState,
    type TextareaHTMLAttributes,
} from 'react';
import { useField } from '@/components/ui/Field';
import { cn } from '@/lib/utils';

type TextareaSize = 'default' | 'sm';

const sizeClasses: Record<TextareaSize, string> = {
    default: 'min-h-24 px-3 py-2 text-2xl sm:text-xl',
    sm: 'min-h-20 px-2 py-1.5 text-2xl sm:text-xl',
};

type TextareaProps = TextareaHTMLAttributes<HTMLTextAreaElement> & {
    size?: TextareaSize;
    error?: boolean;
};

function resize(el: HTMLTextAreaElement) {
    el.style.height = 'auto';
    el.style.height = `${el.scrollHeight}px`;
}

/**
 * Grows with content instead of scrolling (rows is just the initial
 * fallback size before the first measurement). When minLength/maxLength
 * are set, shows a remaining-character count below the field — "Encore
 * N caractères minimum" while under minLength, then "N caractères
 * restants" counting down to maxLength.
 */
export function Textarea({
    size = 'default',
    error,
    className,
    minLength,
    maxLength,
    onInput,
    id,
    'aria-describedby': ariaDescribedBy,
    'aria-invalid': ariaInvalid,
    ...props
}: TextareaProps) {
    const field = useField();
    const ref = useRef<HTMLTextAreaElement>(null);
    const [length, setLength] = useState(
        () => String(props.value ?? props.defaultValue ?? '').length,
    );

    useLayoutEffect(() => {
        if (!ref.current) return;
        resize(ref.current);
        setLength(ref.current.value.length);
    }, [props.value]);

    const belowMin = typeof minLength === 'number' && length < minLength;
    const remaining = typeof maxLength === 'number' ? maxLength - length : null;
    const showError = error || field?.hasError;
    const describedBy =
        [showError ? field?.errorId : null, ariaDescribedBy]
            .filter(Boolean)
            .join(' ') || undefined;

    const message = belowMin
        ? (() => {
              const missing = minLength - length;
              return `Encore ${missing} caractère${missing > 1 ? 's' : ''} minimum`;
          })()
        : remaining !== null
          ? `${remaining} caractère${Math.abs(remaining) !== 1 ? 's' : ''} restant${Math.abs(remaining) !== 1 ? 's' : ''}`
          : null;

    return (
        <div className="w-full">
            <textarea
                ref={ref}
                id={id ?? field?.controlId}
                aria-describedby={describedBy}
                aria-invalid={ariaInvalid ?? (showError || undefined)}
                minLength={minLength}
                maxLength={maxLength}
                className={cn(
                    'w-full resize-none overflow-hidden rounded-none border bg-paper text-ink placeholder:text-ink-muted/70 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-0 focus-visible:outline-action disabled:cursor-not-allowed disabled:bg-surface-2 disabled:text-ink-muted',
                    sizeClasses[size],
                    showError
                        ? 'border-danger focus:border-danger focus-visible:outline-danger'
                        : 'border-rule-strong focus:border-action',
                    className,
                )}
                onInput={(e) => {
                    resize(e.currentTarget);
                    setLength(e.currentTarget.value.length);
                    onInput?.(e);
                }}
                {...props}
            />
            {message && (
                <p
                    className={cn(
                        'mt-1 text-right text-caption',
                        (belowMin || (remaining !== null && remaining < 0)) &&
                            'text-danger',
                    )}
                >
                    {message}
                </p>
            )}
        </div>
    );
}
