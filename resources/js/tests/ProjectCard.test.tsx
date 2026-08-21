import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { ProjectCard, type ProjectCardData } from '@/components/ProjectCard';

// The real @inertiajs/react Link needs a mounted Inertia app instance
// (window.Inertia / router context) that doesn't exist in a unit test —
// a plain <a> is functionally what it renders here anyway.
vi.mock('@inertiajs/react', () => ({
    Link: ({ href, children, ...props }: any) => (
        <a href={href} {...props}>
            {children}
        </a>
    ),
}));

const baseProject: ProjectCardData = {
    slug: 'agrokin',
    title: 'AgroKin',
    description: 'Réseau de stockage réfrigéré solaire.',
    provinces: ['kongo_central'],
    cities: ['Matadi'],
    fundingTargetMinor: 10_000_00,
    fundingRaisedMinor: 2_500_00,
    fundingCurrency: 'USD',
    coverImageUrl: null,
    sectors: [{ id: 1, name: 'Agriculture' }],
};

describe('ProjectCard', () => {
    it('renders the project title and sector', () => {
        render(<ProjectCard project={baseProject} />);

        expect(screen.getByText('AgroKin')).toBeInTheDocument();
        expect(screen.getByText('Agriculture')).toBeInTheDocument();
    });

    it('renders a funding progress bar proportional to raised/target', () => {
        const { container } = render(<ProjectCard project={baseProject} />);

        // 2,500 / 10,000 = 25%
        const bar = container.querySelector('.bg-blue-500') as HTMLElement;
        expect(bar.style.width).toBe('25%');
    });

    it('caps the progress bar at 100% even if raised exceeds the target', () => {
        const overfunded = { ...baseProject, fundingRaisedMinor: 20_000_00 };
        const { container } = render(<ProjectCard project={overfunded} />);

        const bar = container.querySelector('.bg-blue-500') as HTMLElement;
        expect(bar.style.width).toBe('100%');
    });

    it('shows a placeholder when there is no cover image, never a broken image tag', () => {
        render(<ProjectCard project={baseProject} />);

        expect(screen.getByText('Pas de visuel')).toBeInTheDocument();
        expect(screen.queryByRole('img')).not.toBeInTheDocument();
    });

    it('links to the project detail page by slug', () => {
        render(<ProjectCard project={baseProject} />);

        expect(screen.getByRole('link')).toHaveAttribute('href', '/projects/agrokin');
    });
});
