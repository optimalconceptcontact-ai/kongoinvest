import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import {
    AdvisorPanelSkeleton,
    DashboardPageSkeleton,
    FeaturedProjectsSkeleton,
    HeroImageSkeleton,
    HomePageSkeleton,
    PricingGridSkeleton,
    PricingPageSkeleton,
    ProjectDetailPageSkeleton,
    ProjectGridPageSkeleton,
    StatsRowSkeleton,
    TablePageSkeleton,
    TextPageSkeleton,
} from '@/components/ui/page-skeletons';

describe('Page skeleton components', () => {
    it('renders TextPageSkeleton', () => {
        render(<TextPageSkeleton />);
        expect(screen.getByTestId('text-page-skeleton')).toBeInTheDocument();
    });

    it('renders ProjectGridPageSkeleton with configured card count', () => {
        render(<ProjectGridPageSkeleton count={6} />);
        expect(
            screen.getByTestId('project-grid-page-skeleton'),
        ).toBeInTheDocument();
        expect(screen.getAllByTestId('project-card-skeleton')).toHaveLength(6);
    });

    it('renders ProjectDetailPageSkeleton', () => {
        render(<ProjectDetailPageSkeleton />);
        expect(
            screen.getByTestId('project-detail-page-skeleton'),
        ).toBeInTheDocument();
    });

    it('renders DashboardPageSkeleton', () => {
        render(<DashboardPageSkeleton />);
        expect(screen.getByTestId('dashboard-page-skeleton')).toBeInTheDocument();
    });

    it('renders PricingGridSkeleton with configured card count', () => {
        render(<PricingGridSkeleton count={3} />);
        const grid = screen.getByTestId('pricing-grid-skeleton');
        expect(grid).toBeInTheDocument();
        expect(grid.children).toHaveLength(3);
    });

    it('renders PricingPageSkeleton', () => {
        render(<PricingPageSkeleton />);
        expect(screen.getByTestId('pricing-page-skeleton')).toBeInTheDocument();
    });

    it('renders TablePageSkeleton with table skeleton inside', () => {
        render(<TablePageSkeleton rows={3} columns={4} />);
        expect(screen.getByTestId('table-page-skeleton')).toBeInTheDocument();
        expect(screen.getByTestId('table-skeleton')).toBeInTheDocument();
    });

    it('renders HomePageSkeleton', () => {
        render(<HomePageSkeleton />);
        expect(screen.getByTestId('home-page-skeleton')).toBeInTheDocument();
    });

    it('renders StatsRowSkeleton with three blocks', () => {
        render(<StatsRowSkeleton />);
        expect(screen.getByTestId('stats-row-skeleton')).toBeInTheDocument();
        expect(screen.getByTestId('stats-row-skeleton').children).toHaveLength(3);
    });

    it('renders FeaturedProjectsSkeleton with configured card count', () => {
        render(<FeaturedProjectsSkeleton count={3} />);
        expect(
            screen.getByTestId('featured-projects-skeleton'),
        ).toBeInTheDocument();
        expect(screen.getAllByTestId('project-card-skeleton')).toHaveLength(3);
    });

    it('renders HeroImageSkeleton', () => {
        render(<HeroImageSkeleton />);
        expect(screen.getByTestId('hero-image-skeleton')).toBeInTheDocument();
    });

    it('renders AdvisorPanelSkeleton', () => {
        render(<AdvisorPanelSkeleton />);
        expect(screen.getByTestId('advisor-panel-skeleton')).toBeInTheDocument();
    });
});
