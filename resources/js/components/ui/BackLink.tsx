import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

/** Same styling DealRoom/Show.tsx already used for its own back link — now the shared pattern for every bare page (no role layout) above its eyebrow. */
export function BackLink({
    href,
    children = '← Retour',
}: {
    href: string;
    children?: ReactNode;
}) {
    return (
        <Link
            href={href}
            className="mb-4 inline-block font-mono text-lg text-ink-muted underline-offset-4 hover:text-ink hover:underline"
        >
            {children}
        </Link>
    );
}
