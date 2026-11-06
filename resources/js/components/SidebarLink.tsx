import { Link } from '@inertiajs/react';
import type { RemixiconComponentType } from '@remixicon/react';

export function SidebarLink({
    href,
    icon: Icon,
    label,
    active,
    disabled,
    badge,
    expand = false,
    onClick,
}: {
    href: string;
    icon: RemixiconComponentType;
    label: string;
    active?: boolean;
    disabled?: boolean;
    badge?: string;
    expand?: boolean;
    onClick?: () => void;
}) {
    const labelClass = expand ? 'inline' : 'hidden md:inline';
    const layoutClass = expand ? 'justify-between' : 'md:justify-between';

    if (disabled) {
        return (
            <div
                className={`flex min-h-11 cursor-not-allowed items-center gap-2.5 border-l-2 border-transparent px-3 py-2 font-mono text-[16px] text-ink-muted ${layoutClass}`}
            >
                <span className="flex items-center gap-2.5" title={label}>
                    <Icon size={18} />
                    <span className={labelClass}>{label}</span>
                </span>
                {badge && (
                    <span className={`${labelClass} font-mono text-lg`}>
                        {badge}
                    </span>
                )}
            </div>
        );
    }

    return (
        <Link
            href={href}
            title={label}
            onClick={onClick}
            aria-current={active ? 'page' : undefined}
            className={`flex min-h-11 items-center gap-2.5 border-l-2 px-3 py-2 font-mono text-[16px] tracking-[0.01em] transition-[background-color,border-color,color] duration-150 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-action ${layoutClass} ${
                active
                    ? 'border-signal bg-ink text-paper'
                    : 'border-transparent text-ink-muted hover:bg-surface-2 hover:text-ink'
            }`}
        >
            <span className="flex min-w-0 items-center gap-2.5">
                <Icon size={17} className="flex-shrink-0" />
                <span className={`truncate ${labelClass}`}>{label}</span>
            </span>
            {badge && (
                <span
                    className={`${labelClass} flex-shrink-0 font-mono text-lg ${
                        active ? 'text-paper' : 'text-ink-muted'
                    }`}
                >
                    {badge}
                </span>
            )}
        </Link>
    );
}
