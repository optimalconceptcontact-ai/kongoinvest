import { useLayoutEffect, useRef, useState, type TextareaHTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

type TextareaSize = 'default' | 'sm';

const sizeClasses: Record<TextareaSize, string> = {
    default: 'px-3 py-2 text-sm',
    sm: 'px-2 py-1.5 text-sm',
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
export function Textarea({ size = 'default', error, className, minLength, maxLength, onInput, ...props }: TextareaProps) {
    const ref = useRef<HTMLTextAreaElement>(null);
    const [length, setLength] = useState(() => String(props.value ?? props.defaultValue ?? '').length);

    useLayoutEffect(() => {
        if (!ref.current) return;
        resize(ref.current);
        setLength(ref.current.value.length);
    }, [props.value]);

    const belowMin = typeof minLength === 'number' && length < minLength;
    const remaining = typeof maxLength === 'number' ? maxLength - length : null;

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
                minLength={minLength}
                maxLength={maxLength}
                className={cn(
                    'w-full resize-none overflow-hidden border focus:outline-none',
                    sizeClasses[size],
                    error ? 'border-orange-600 focus:border-orange-600' : 'border-slate-300 focus:border-blue-600',
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
                <p className={cn('mt-1 text-right text-caption', (belowMin || (remaining !== null && remaining < 0)) && 'text-orange-600')}>
                    {message}
                </p>
            )}
        </div>
    );
}
