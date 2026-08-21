import { Link } from '@inertiajs/react';
import { RiLogoutBoxRLine, RiSettings3Line } from '@remixicon/react';
import { useEffect, useRef, useState } from 'react';
import { destroy } from '@/actions/Laravel/Fortify/Http/Controllers/AuthenticatedSessionController';
import { useInitials } from '@/hooks/use-initials';

type User = { name: string; email: string };

export function UserMenu({ user }: { user: User }) {
    const [open, setOpen] = useState(false);
    const menuRef = useRef<HTMLDivElement>(null);
    const getInitials = useInitials();

    useEffect(() => {
        if (!open) return;

        function onKeyDown(e: KeyboardEvent) {
            if (e.key === 'Escape') setOpen(false);
        }

        function onClickOutside(e: MouseEvent) {
            if (!menuRef.current?.contains(e.target as Node)) setOpen(false);
        }

        document.addEventListener('keydown', onKeyDown);
        document.addEventListener('mousedown', onClickOutside);

        return () => {
            document.removeEventListener('keydown', onKeyDown);
            document.removeEventListener('mousedown', onClickOutside);
        };
    }, [open]);

    return (
        <div className="relative" ref={menuRef}>
            <button
                onClick={() => setOpen((v) => !v)}
                aria-label="Menu du compte"
                aria-expanded={open}
                className="flex h-9 w-9 items-center justify-center rounded-full bg-slate-900 text-xs font-bold text-white hover:bg-blue-700"
            >
                {getInitials(user.name)}
            </button>

            {open && (
                <div className="absolute right-0 z-50 mt-2 w-56 border border-slate-200 bg-white shadow-sm">
                    <div className="border-b border-slate-200 px-4 py-3">
                        <div className="text-menu-title">{user.name}</div>
                        <div className="text-menu-subtitle">{user.email}</div>
                    </div>
                    <div className="flex flex-col py-1">

                        <Link href="/dashboard"
                            onClick={() => setOpen(false)}
                            className="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                            <RiLogoutBoxRLine size={16} />
                            Tableau de bord
                        </Link>

                        <Link
                            href="/settings/profile"
                            onClick={() => setOpen(false)}
                            className="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
                        >
                            <RiSettings3Line size={16} />
                            Mon compte
                        </Link>

                        <Link
                            href={destroy()}
                            as="button"
                            onClick={() => setOpen(false)}
                            className="flex items-center gap-2 px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-50"
                        >
                            <RiLogoutBoxRLine size={16} />
                            Déconnexion
                        </Link>
                    </div>
                </div>
            )}
        </div>
    );
}
