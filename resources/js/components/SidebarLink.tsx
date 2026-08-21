import { Link } from '@inertiajs/react';
import type { RemixiconComponentType } from '@remixicon/react';

export function SidebarLink({
    href,
    icon: Icon,
    label,
    active,
    disabled,
    badge,
}: {
    href: string;
    icon: RemixiconComponentType;
    label: string;
    active?: boolean;
    disabled?: boolean;
    badge?: string;
}) {
    if (disabled) {
        return (
            <div className="flex items-center justify-between px-3 py-2 text-sm text-slate-300">
                <span className="flex items-center gap-2.5">
                    <Icon size={17} /> {label}
                </span>
                {badge && <span className="font-mono text-[10px] text-slate-300">{badge}</span>}
            </div>
        );
    }

    return (
        <Link
            href={href}
            className={`flex items-center justify-between px-3 py-2 text-sm transition ${
                active ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100'
            }`}
        >
            <span className="flex items-center gap-2.5">
                <Icon size={17} /> {label}
            </span>
            {badge && (
                <span className={`font-mono text-[10px] ${active ? 'text-slate-300' : 'text-slate-400'}`}>{badge}</span>
            )}
        </Link>
    );
}
