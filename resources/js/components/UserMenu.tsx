import { Link } from '@inertiajs/react';
import {
    RiArrowDownSLine,
    RiArrowUpSLine,
    RiDashboardLine,
    RiLogoutBoxRLine,
    RiSettings3Line,
} from '@remixicon/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { destroy } from '@/actions/Laravel/Fortify/Http/Controllers/AuthenticatedSessionController';
import { ThemeSwitcher } from '@/components/ThemeSwitcher';
import { useInitials } from '@/hooks/use-initials';

type User = { name: string; email: string };

export function UserMenu({
    user,
    align = 'right',
    direction = 'down',
    open: controlledOpen,
    onOpenChange,
    showDashboardLink = true,
}: {
    user: User;
    align?: 'left' | 'right';
    direction?: 'up' | 'down';
    open?: boolean;
    onOpenChange?: (open: boolean) => void;
    showDashboardLink?: boolean;
}) {
    const [internalOpen, setInternalOpen] = useState(false);
    const menuRef = useRef<HTMLDivElement>(null);
    const getInitials = useInitials();

    // The chevron mirrors the dropdown direction: sidebars open upward,
    // the public header opens downward.
    const ChevronIcon = direction === 'up' ? RiArrowUpSLine : RiArrowDownSLine;

    // Controlled when `open` is passed (sidebar name trigger), otherwise
    // self-managed (header avatar trigger).
    const open = controlledOpen ?? internalOpen;

    const setOpen = useCallback(
        (value: boolean | ((prev: boolean) => boolean)) => {
            const next = typeof value === 'function' ? value(open) : value;

            if (controlledOpen === undefined) {
                setInternalOpen(next);
            }

            onOpenChange?.(next);
        },
        [open, controlledOpen, onOpenChange],
    );

    useEffect(() => {
        if (!open) {
            return;
        }

        function onKeyDown(e: KeyboardEvent) {
            if (e.key === 'Escape') {
                setOpen(false);
            }
        }

        function onClickOutside(e: MouseEvent) {
            if (!menuRef.current?.contains(e.target as Node)) {
                setOpen(false);
            }
        }

        document.addEventListener('keydown', onKeyDown);
        document.addEventListener('mousedown', onClickOutside);

        return () => {
            document.removeEventListener('keydown', onKeyDown);
            document.removeEventListener('mousedown', onClickOutside);
        };
    }, [open, setOpen]);

    return (
        <div className="relative" ref={menuRef}>
            <button
                onClick={() => setOpen((v) => !v)}
                aria-label="Menu du compte"
                aria-expanded={open}
                className="flex h-11 min-w-11 items-center justify-center gap-1 bg-ink px-2 text-lg font-bold text-paper hover:opacity-90"
            >
                {getInitials(user.name)}
                <ChevronIcon size={14} className="hidden md:block" />
            </button>

            {open && (
                <div
                    className={`absolute z-50 w-56 max-w-[calc(100vw-2rem)] border border-rule-strong bg-paper shadow-[4px_4px_0_0_rgba(14,23,38,0.08)] dark:shadow-[4px_4px_0_0_rgba(0,0,0,0.35)] ${
                        direction === 'up' ? 'bottom-full mb-2' : 'mt-2'
                    } ${align === 'left' ? 'left-0' : 'right-0'}`}
                >
                    <div className="border-b border-rule px-4 py-3">
                        <div className="text-menu-title">{user.name}</div>
                        <div className="text-menu-subtitle">{user.email}</div>
                    </div>
                    <div className="flex flex-col py-1">
                        {showDashboardLink && (
                            <Link
                                href="/dashboard"
                                onClick={() => setOpen(false)}
                                className="flex min-h-11 items-center gap-2 px-4 py-2 text-lg text-ink-muted hover:bg-surface-2 hover:text-ink"
                            >
                                <RiDashboardLine size={16} />
                                Tableau de bord
                            </Link>
                        )}

                        <Link
                            href="/settings/profile"
                            onClick={() => setOpen(false)}
                            className="flex min-h-11 items-center gap-2 px-4 py-2 text-lg text-ink-muted hover:bg-surface-2 hover:text-ink"
                        >
                            <RiSettings3Line size={16} />
                            Mon compte
                        </Link>

                        <Link
                            href={destroy()}
                            as="button"
                            onClick={() => setOpen(false)}
                            className="flex min-h-11 items-center gap-2 px-4 py-2 text-left text-lg text-ink-muted hover:bg-surface-2 hover:text-ink"
                        >
                            <RiLogoutBoxRLine size={16} />
                            Déconnexion
                        </Link>
                    </div>
                    <div className="flex justify-end border-t border-rule px-4 py-3">
                        <ThemeSwitcher variant="icon" />
                    </div>
                </div>
            )}
        </div>
    );
}
