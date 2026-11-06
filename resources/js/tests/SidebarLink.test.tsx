import { RiDashboardLine } from '@remixicon/react';
import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { SidebarLink } from '@/components/SidebarLink';

vi.mock('@inertiajs/react', () => ({
    Link: ({ href, children, ...props }: any) => (
        <a href={href} {...props}>
            {children}
        </a>
    ),
}));

describe('SidebarLink', () => {
    it('renders as a real link with the active style when active', () => {
        render(
            <SidebarLink
                href="/dashboard"
                icon={RiDashboardLine}
                label="Dashboard"
                active
            />,
        );

        const link = screen.getByRole('link', { name: /Dashboard/ });
        expect(link).toHaveAttribute('href', '/dashboard');
        expect(link.className).toContain('border-signal');
    });

    it('does not apply the active style when inactive', () => {
        render(
            <SidebarLink
                href="/dashboard"
                icon={RiDashboardLine}
                label="Dashboard"
            />,
        );

        const link = screen.getByRole('link', { name: /Dashboard/ });
        expect(link.className).not.toContain('border-signal');
    });

    it('renders a disabled item as non-interactive, never a real navigable link', () => {
        render(
            <SidebarLink
                href="/escrow"
                icon={RiDashboardLine}
                label="Escrow"
                disabled
                badge="Phase 11"
            />,
        );

        // Spec §100: frontend never fakes access — a disabled sidebar
        // item must not even be a clickable <a>, since the backend route
        // it would point to shouldn't be reachable this way either.
        expect(screen.queryByRole('link')).not.toBeInTheDocument();
        expect(screen.getByText('Escrow')).toBeInTheDocument();
        expect(screen.getByText('Phase 11')).toBeInTheDocument();
    });
});
