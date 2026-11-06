import { render, screen, fireEvent } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { PublicLayout } from '@/layouts/PublicLayout';

const mockOpenLogin = vi.fn();
const mockOpenRegister = vi.fn();
let mockAuthUser: { id: number; name: string } | null = null;

vi.mock('@inertiajs/react', () => ({
    Link: ({ href, children, ...props }: any) => (
        <a href={href} {...props}>
            {children}
        </a>
    ),
    usePage: () => ({
        props: {
            auth: {
                user: mockAuthUser,
                roles: mockAuthUser ? ['investor'] : [],
            },
        },
        url: '/',
    }),
}));

vi.mock('@/hooks/useAuthModal', () => ({
    useAuthModal: () => ({
        openLogin: mockOpenLogin,
        openRegister: mockOpenRegister,
    }),
}));

vi.mock('@/components/NotificationBell', () => ({
    NotificationBell: () => <div data-testid="notification-bell" />,
}));

describe('PublicLayout', () => {
    it('renders the navbar, the page content, and the footer together', () => {
        render(
            <PublicLayout>
                <p>Contenu de la page</p>
            </PublicLayout>,
        );

        expect(screen.getAllByText('KongoInvest').length).toBeGreaterThan(
            0,
        ); // logo appears in both navbar and footer
        expect(screen.getByText('Contenu de la page')).toBeInTheDocument();
        expect(screen.getByText(/Tous droits réservés/)).toBeInTheDocument(); // footer
    });

    it('shows Login/Register CTAs for a guest, never a dashboard link', () => {
        mockAuthUser = null;
        render(
            <PublicLayout>
                <p>Contenu</p>
            </PublicLayout>,
        );

        expect(screen.getByText('Se connecter')).toBeInTheDocument();
        expect(screen.getByText('Créer un compte')).toBeInTheDocument();
        expect(screen.queryByText('Tableau de bord')).not.toBeInTheDocument();
    });

    it('shows the dashboard link and notification bell for an authenticated user, never auth CTAs', () => {
        mockAuthUser = { id: 1, name: 'Marie' };
        render(
            <PublicLayout>
                <p>Contenu</p>
            </PublicLayout>,
        );

        fireEvent.click(screen.getByLabelText('Menu du compte'));

        expect(screen.getAllByText('Tableau de bord').length).toBeGreaterThan(
            0,
        );
        expect(screen.getByTestId('notification-bell')).toBeInTheDocument();
        expect(screen.queryByText('Créer un compte')).not.toBeInTheDocument();
    });

    it('opens the mobile drawer when the menu button is clicked', () => {
        mockAuthUser = null;
        render(
            <PublicLayout>
                <p>Contenu</p>
            </PublicLayout>,
        );

        fireEvent.click(screen.getByLabelText('Ouvrir le menu'));

        expect(screen.getByLabelText('Fermer le menu')).toBeInTheDocument();
    });
});
