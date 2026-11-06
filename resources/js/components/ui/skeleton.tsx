import type { HTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

/**
 * Atomic Skeleton component.
 * Follows the brutalist design system of KongoInvest:
 * sharp edges (--radius: 0px), subtle border, and muted surface background.
 */
export function Skeleton({
    className,
    ...props
}: HTMLAttributes<HTMLDivElement>) {
    return (
        <div
            data-testid="skeleton"
            aria-hidden="true"
            className={cn('animate-pulse border border-rule bg-surface-2', className)}
            {...props}
        />
    );
}

/**
 * Skeleton placeholder that mirrors the exact layout of ProjectCard
 * to eliminate Cumulative Layout Shift (CLS) when loading project lists.
 */
export function ProjectCardSkeleton({ className }: { className?: string }) {
    return (
        <div
            data-testid="project-card-skeleton"
            className={cn(
                'flex h-full flex-col border border-rule-strong bg-paper',
                className,
            )}
        >
            {/* Visual cover placeholder */}
            <div className="aspect-[16/10] w-full border-b border-rule bg-surface-2">
                <Skeleton className="h-full w-full border-0" />
            </div>

            {/* Content area */}
            <div className="flex flex-1 flex-col p-5">
                {/* Sector badge */}
                <Skeleton className="mb-3 h-3 w-20 border-0" />

                {/* Title */}
                <Skeleton className="mb-2 h-5 w-3/4 border-0" />

                {/* Description lines */}
                <div className="mb-4 space-y-2">
                    <Skeleton className="h-3.5 w-full border-0" />
                    <Skeleton className="h-3.5 w-5/6 border-0" />
                </div>

                {/* Progress bar and metrics */}
                <div className="mt-auto mb-3">
                    <Skeleton className="h-1 w-full border-0" />
                    <div className="mt-2 flex justify-between">
                        <Skeleton className="h-3 w-16 border-0" />
                        <Skeleton className="h-3 w-24 border-0" />
                    </div>
                </div>

                {/* Location footer */}
                <div className="border-t border-rule pt-3">
                    <Skeleton className="h-3 w-28 border-0" />
                </div>
            </div>
        </div>
    );
}

/**
 * Grid helper for rendering multiple ProjectCardSkeleton items.
 */
export function ProjectGridSkeleton({
    count = 6,
    className,
}: {
    count?: number;
    className?: string;
}) {
    return (
        <div
            data-testid="project-grid-skeleton"
            className={cn(
                'grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3',
                className,
            )}
        >
            {Array.from({ length: count }).map((_, i) => (
                <ProjectCardSkeleton key={i} />
            ))}
        </div>
    );
}

/**
 * Skeleton placeholder that mirrors the exact layout of MatchCard.
 */
export function MatchCardSkeleton({ className }: { className?: string }) {
    return (
        <article
            data-testid="match-card-skeleton"
            className={cn(
                'flex flex-col gap-3.5 border border-rule-strong bg-paper p-[18px]',
                className,
            )}
        >
            {/* Top row: Title/subtitle + Score block */}
            <div className="flex items-start justify-between gap-4">
                <div className="min-w-0 flex-1 space-y-2">
                    <Skeleton className="h-5 w-1/2 border-0" />
                    <Skeleton className="h-3.5 w-3/4 border-0" />
                </div>
                <div className="flex flex-shrink-0 flex-col items-end">
                    <Skeleton className="h-10 w-14 border-0" />
                    <div className="my-1.5 h-[3px] w-7 bg-signal/30" />
                    <Skeleton className="h-3 w-12 border-0" />
                </div>
            </div>

            {/* Criteria table placeholder */}
            <div className="w-full border-collapse space-y-2.5 pt-2">
                <div className="flex justify-between border-b border-rule-strong pb-1.5">
                    <Skeleton className="h-3 w-16 border-0" />
                    <Skeleton className="h-3 w-10 border-0" />
                </div>
                {Array.from({ length: 3 }).map((_, i) => (
                    <div
                        key={i}
                        className="flex justify-between border-b border-rule py-2 last:border-b-0"
                    >
                        <div className="space-y-1">
                            <Skeleton className="h-3.5 w-32 border-0" />
                            <Skeleton className="h-2.5 w-16 border-0" />
                        </div>
                        <Skeleton className="h-4 w-8 border-0" />
                    </div>
                ))}
            </div>

            {/* Footer row */}
            <div className="mt-auto flex items-center justify-between border-t border-rule pt-3">
                <Skeleton className="h-3 w-28 border-0" />
                <Skeleton className="h-3 w-20 border-0" />
            </div>
        </article>
    );
}

/**
 * List helper for rendering multiple MatchCardSkeleton items.
 */
export function MatchListSkeleton({
    count = 3,
    className,
}: {
    count?: number;
    className?: string;
}) {
    return (
        <div
            data-testid="match-list-skeleton"
            className={cn('flex flex-col gap-4', className)}
        >
            {Array.from({ length: count }).map((_, i) => (
                <MatchCardSkeleton key={i} />
            ))}
        </div>
    );
}

/**
 * Skeleton placeholder for stats blocks (e.g. Hero, Dashboard).
 */
export function StatBlockSkeleton({ className }: { className?: string }) {
    return (
        <div data-testid="stat-block-skeleton" className={cn('space-y-2', className)}>
            <Skeleton className="h-9 w-24 border-0" />
            <Skeleton className="h-3 w-32 border-0" />
        </div>
    );
}

/**
 * Multi-stat metrics grid used across investor and admin dashboards.
 */
export function StatsGridSkeleton({
    count = 3,
    className,
}: {
    count?: number;
    className?: string;
}) {
    return (
        <div
            data-testid="stats-grid-skeleton"
            className={cn(
                'grid grid-cols-1 divide-y divide-rule border border-rule-strong bg-paper sm:grid-cols-3 sm:divide-x sm:divide-y-0',
                className,
            )}
        >
            {Array.from({ length: count }).map((_, i) => (
                <div key={i} className="p-6 space-y-2">
                    <Skeleton className="h-10 w-20 border-0" />
                    <Skeleton className="h-3.5 w-36 border-0" />
                </div>
            ))}
        </div>
    );
}

/**
 * Skeleton placeholder for page headers (eyebrow, title, description).
 */
export function PageHeaderSkeleton({ className }: { className?: string }) {
    return (
        <div data-testid="page-header-skeleton" className={cn('space-y-3 mb-8', className)}>
            <Skeleton className="h-3 w-24 border-0" />
            <Skeleton className="h-9 w-2/3 border-0" />
            <Skeleton className="h-4 w-full max-w-2xl border-0" />
        </div>
    );
}

/**
 * Skeleton placeholder for table rows.
 */
export function TableRowSkeleton({
    columns = 4,
    className,
}: {
    columns?: number;
    className?: string;
}) {
    return (
        <tr data-testid="table-row-skeleton" className={cn('border-b border-rule', className)}>
            {Array.from({ length: columns }).map((_, i) => (
                <td key={i} className="p-4">
                    <Skeleton className="h-4 w-full border-0" />
                </td>
            ))}
        </tr>
    );
}

/**
 * Full table skeleton with header and rows for admin/data tables.
 */
export function TableSkeleton({
    rows = 5,
    columns = 4,
    className,
}: {
    rows?: number;
    columns?: number;
    className?: string;
}) {
    return (
        <div
            data-testid="table-skeleton"
            className={cn('w-full overflow-x-auto border border-rule-strong bg-paper', className)}
        >
            <table className="w-full border-collapse">
                <thead>
                    <tr className="border-b border-rule-strong bg-surface-2/60">
                        {Array.from({ length: columns }).map((_, i) => (
                            <th key={i} className="p-4 text-left">
                                <Skeleton className="h-3 w-20 border-0" />
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {Array.from({ length: rows }).map((_, i) => (
                        <TableRowSkeleton key={i} columns={columns} />
                    ))}
                </tbody>
            </table>
        </div>
    );
}
