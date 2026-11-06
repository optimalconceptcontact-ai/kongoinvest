import { RiComputerLine, RiMoonLine, RiSunLine } from '@remixicon/react';
import { useAppearance } from '@/hooks/use-appearance';
import type { Appearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';

const options: { value: Appearance; label: string; Icon: typeof RiSunLine }[] =
    [
        { value: 'light', label: 'Clair', Icon: RiSunLine },
        { value: 'dark', label: 'Sombre', Icon: RiMoonLine },
        { value: 'system', label: 'Système', Icon: RiComputerLine },
    ];

/**
 * Client-only theme switch (localStorage + cookie, no server round-trip)
 * — the `use-appearance` hook toggles the `dark` class on <html>, and
 * every surface styles itself with `dark:` variants.
 */
export function ThemeSwitcher({
    label = null,
    variant = 'text',
}: {
    label?: string | null;
    variant?: 'text' | 'icon';
}) {
    const { appearance, updateAppearance } = useAppearance();

    return (
        <div className="flex items-center justify-between gap-2">
            {label ? <span className="text-section-label">{label}</span> : null}
            <div
                className={cn(
                    'flex items-center gap-0.5 border border-rule p-0.5',
                    variant === 'icon' && 'gap-0',
                )}
                role="group"
                aria-label="Thème d'affichage"
            >
                {options.map(({ value, label: optionLabel, Icon }) => (
                    <button
                        key={value}
                        type="button"
                        title={optionLabel}
                        aria-label={optionLabel}
                        aria-pressed={appearance === value}
                        onClick={() => updateAppearance(value)}
                        className={cn(
                            'flex min-h-11 items-center justify-center font-mono text-lg font-medium tracking-[0.04em] transition-colors',
                            variant === 'text' ? 'px-2.5' : 'w-11 px-0',
                            appearance === value
                                ? 'bg-ink text-paper'
                                : 'text-ink-muted hover:text-ink',
                        )}
                    >
                        {variant === 'icon' ? <Icon size={15} /> : optionLabel}
                    </button>
                ))}
            </div>
        </div>
    );
}
