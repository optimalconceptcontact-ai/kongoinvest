import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import {
    MatchCardSkeleton,
    MatchListSkeleton,
    PageHeaderSkeleton,
    ProjectCardSkeleton,
    ProjectGridSkeleton,
    Skeleton,
    StatBlockSkeleton,
    StatsGridSkeleton,
    TableRowSkeleton,
    TableSkeleton,
} from '@/components/ui/skeleton';

describe('Skeleton components', () => {
    it('renders the base skeleton with pulse animation, aria-hidden and surface classes', () => {
        render(<Skeleton className="h-6 w-24" />);

        const el = screen.getByTestId('skeleton');
        expect(el).toBeInTheDocument();
        expect(el).toHaveClass('animate-pulse');
        expect(el).toHaveClass('bg-surface-2');
        expect(el).toHaveClass('h-6');
        expect(el).toHaveClass('w-24');
        expect(el).toHaveAttribute('aria-hidden', 'true');
    });

    it('renders ProjectCardSkeleton structure without throwing', () => {
        render(<ProjectCardSkeleton />);

        const card = screen.getByTestId('project-card-skeleton');
        expect(card).toBeInTheDocument();
        expect(card).toHaveClass('border');
        expect(card).toHaveClass('bg-paper');
    });

    it('renders ProjectGridSkeleton with configured card count', () => {
        render(<ProjectGridSkeleton count={4} />);

        const grid = screen.getByTestId('project-grid-skeleton');
        expect(grid).toBeInTheDocument();
        const cards = screen.getAllByTestId('project-card-skeleton');
        expect(cards).toHaveLength(4);
    });

    it('renders MatchCardSkeleton structure matching MatchCard layout', () => {
        render(<MatchCardSkeleton />);

        const card = screen.getByTestId('match-card-skeleton');
        expect(card).toBeInTheDocument();
        expect(card).toHaveClass('border');
        expect(card).toHaveClass('bg-paper');
    });

    it('renders MatchListSkeleton with configured count', () => {
        render(<MatchListSkeleton count={3} />);

        const list = screen.getByTestId('match-list-skeleton');
        expect(list).toBeInTheDocument();
        const items = screen.getAllByTestId('match-card-skeleton');
        expect(items).toHaveLength(3);
    });

    it('renders StatBlockSkeleton', () => {
        render(<StatBlockSkeleton />);

        const block = screen.getByTestId('stat-block-skeleton');
        expect(block).toBeInTheDocument();
    });

    it('renders StatsGridSkeleton with configured number of items', () => {
        render(<StatsGridSkeleton count={3} />);

        const grid = screen.getByTestId('stats-grid-skeleton');
        expect(grid).toBeInTheDocument();
        expect(grid.children).toHaveLength(3);
    });

    it('renders PageHeaderSkeleton', () => {
        render(<PageHeaderSkeleton />);

        const header = screen.getByTestId('page-header-skeleton');
        expect(header).toBeInTheDocument();
    });

    it('renders TableRowSkeleton with configured columns', () => {
        render(
            <table>
                <tbody>
                    <TableRowSkeleton columns={5} />
                </tbody>
            </table>,
        );

        const row = screen.getByTestId('table-row-skeleton');
        expect(row).toBeInTheDocument();
        const cells = row.querySelectorAll('td');
        expect(cells).toHaveLength(5);
    });

    it('renders TableSkeleton with the right number of header cells and rows', () => {
        render(<TableSkeleton rows={4} columns={3} />);

        const tableWrapper = screen.getByTestId('table-skeleton');
        expect(tableWrapper).toBeInTheDocument();

        const rows = screen.getAllByTestId('table-row-skeleton');
        expect(rows).toHaveLength(4);

        const headers = tableWrapper.querySelectorAll('th');
        expect(headers).toHaveLength(3);
    });
});
