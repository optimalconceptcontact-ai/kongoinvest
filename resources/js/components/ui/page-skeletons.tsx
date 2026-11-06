import { cn } from '@/lib/utils';
import {
    ProjectCardSkeleton,
    Skeleton,
    StatsGridSkeleton,
    TableSkeleton,
} from '@/components/ui/skeleton';

/**
 * Page-level skeletons.
 *
 * These mirror the *ossature* (skeleton) of a full page — eyebrow, title,
 * filter/action bars, grids and sidebars — so a deferred page renders the
 * same structure as its loaded counterpart and avoids layout shift. They
 * reuse the atomic `Skeleton` and the card/table compositions from
 * `@/components/ui/skeleton` and follow the KongoInvest brutalist style:
 * sharp edges, `border-rule`/`border-rule-strong` rules, `bg-paper` /
 * `bg-surface-2` surfaces.
 */

/**
 * Generic editorial page (About, FAQ, HowItWorks, Terms…):
 * eyebrow + title + a column of prose lines.
 */
export function TextPageSkeleton({ className }: { className?: string }) {
    return (
        <div
            data-testid="text-page-skeleton"
            className={cn('mx-auto max-w-6xl px-4 py-12 md:px-6', className)}
        >
            <Skeleton className="mb-3 h-3 w-24 border-0" />
            <Skeleton className="mb-8 h-9 w-2/3 max-w-xl border-0" />
            <div className="max-w-3xl space-y-4 border-t border-rule pt-8">
                {Array.from({ length: 7 }).map((_, i) => (
                    <Skeleton
                        key={i}
                        className={cn(
                            'h-4 border-0',
                            i === 6 ? 'w-2/3' : 'w-full',
                        )}
                    />
                ))}
            </div>
        </div>
    );
}

/**
 * Project discovery page (Public/Projects): eyebrow + title + filter bar
 * + a grid of project cards.
 */
export function ProjectGridPageSkeleton({
    count = 8,
    className,
}: {
    count?: number;
    className?: string;
}) {
    return (
        <div
            data-testid="project-grid-page-skeleton"
            className={cn('mx-auto max-w-6xl px-4 py-12 md:px-6', className)}
        >
            <Skeleton className="mb-2 h-3 w-20 border-0" />
            <Skeleton className="mb-8 h-9 w-3/4 max-w-2xl border-0" />

            {/* Filter bar */}
            <div className="mb-8 flex flex-wrap items-center gap-3 border border-rule-strong bg-surface-2 p-4">
                <Skeleton className="h-11 min-w-[220px] flex-1 border-0" />
                <Skeleton className="h-11 min-w-[180px] flex-1 border-0" />
                <Skeleton className="h-11 min-w-[180px] flex-1 border-0" />
            </div>

            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                {Array.from({ length: count }).map((_, i) => (
                    <ProjectCardSkeleton key={i} />
                ))}
            </div>
        </div>
    );
}

/**
 * Project detail page (Public/ProjectDetail): hero image + badges + title
 * + meta + body sections, with a sticky funding/sidebar column.
 */
export function ProjectDetailPageSkeleton({ className }: { className?: string }) {
    return (
        <div
            data-testid="project-detail-page-skeleton"
            className={cn(
                'mx-auto grid max-w-6xl grid-cols-1 gap-10 px-4 py-12 md:px-6 lg:grid-cols-[1fr_320px]',
                className,
            )}
        >
            <div>
                {/* Hero image */}
                <div className="mb-6 aspect-[16/9] w-full border border-rule-strong bg-surface-2">
                    <Skeleton className="h-full w-full border-0" />
                </div>

                {/* Badges + report button */}
                <div className="mb-3 flex flex-wrap items-center gap-2">
                    <Skeleton className="h-7 w-24 border-0" />
                    <Skeleton className="h-7 w-24 border-0" />
                    <Skeleton className="ml-auto h-11 w-56 border-0" />
                </div>

                {/* Title + meta */}
                <Skeleton className="mb-3 h-10 w-3/4 border-0" />
                <div className="mb-6 flex flex-wrap gap-4">
                    <Skeleton className="h-4 w-48 border-0" />
                    <Skeleton className="h-4 w-40 border-0" />
                </div>

                {/* Description */}
                <div className="mb-6 space-y-2">
                    <Skeleton className="h-4 w-full border-0" />
                    <Skeleton className="h-4 w-5/6 border-0" />
                </div>

                {/* Content sections */}
                {Array.from({ length: 4 }).map((_, i) => (
                    <div key={i} className="border-t border-rule py-6">
                        <Skeleton className="mb-3 h-3 w-28 border-0" />
                        <div className="space-y-2">
                            <Skeleton className="h-4 w-full border-0" />
                            <Skeleton className="h-4 w-3/4 border-0" />
                        </div>
                    </div>
                ))}
            </div>

            {/* Funding / actions sidebar */}
            <aside className="h-max border border-rule-strong bg-paper p-5">
                <Skeleton className="mb-3 h-9 w-24 border-0" />
                <Skeleton className="mb-5 h-1.5 w-full border-0" />
                <div className="mb-5 space-y-3 border-y border-rule py-4">
                    <Skeleton className="h-4 w-full border-0" />
                    <Skeleton className="h-4 w-full border-0" />
                    <Skeleton className="h-4 w-2/3 border-0" />
                </div>
                <Skeleton className="h-12 w-full border-0" />
            </aside>
        </div>
    );
}

/**
 * Dashboard page (Investor/Entrepreneur/Admin): stats strip + a list
 * section + a plan/notice box.
 */
export function DashboardPageSkeleton({ className }: { className?: string }) {
    return (
        <div data-testid="dashboard-page-skeleton" className={cn('space-y-6', className)}>
            <StatsGridSkeleton count={3} />

            <div className="border border-rule-strong bg-paper p-6">
                <div className="mb-4 flex items-center justify-between">
                    <Skeleton className="h-3 w-32 border-0" />
                    <Skeleton className="h-4 w-24 border-0" />
                </div>
                <div className="flex flex-col">
                    {Array.from({ length: 4 }).map((_, i) => (
                        <div
                            key={i}
                            className="flex min-h-11 items-center justify-between gap-4 border-b border-rule py-2 last:border-b-0"
                        >
                            <Skeleton className="h-4 w-1/2 border-0" />
                            <Skeleton className="h-4 w-10 border-0" />
                        </div>
                    ))}
                </div>
            </div>

            <div className="border border-rule bg-surface-2 p-6">
                <Skeleton className="mb-2 h-3 w-28 border-0" />
                <Skeleton className="h-6 w-40 border-0" />
            </div>
        </div>
    );
}

/**
 * Pricing plan cards grid. Mirrors `PricingGrid`'s bordered columns.
 */
export function PricingGridSkeleton({
    count = 4,
    className,
}: {
    count?: number;
    className?: string;
}) {
    return (
        <div
            data-testid="pricing-grid-skeleton"
            className={cn(
                'grid grid-cols-1 border border-rule-strong md:grid-cols-4',
                className,
            )}
        >
            {Array.from({ length: count }).map((_, i) => (
                <div
                    key={i}
                    className="flex flex-col border-b border-rule bg-paper p-6 last:border-b-0 md:border-r md:border-b-0 md:last:border-r-0"
                >
                    <Skeleton className="mb-4 h-3 w-28 border-0" />
                    <Skeleton className="mb-2 h-6 w-36 border-0" />
                    <Skeleton className="mb-1 h-9 w-32 border-0" />
                    <Skeleton className="mb-6 h-4 w-3/4 border-0" />
                    <div className="mb-8 flex flex-1 flex-col gap-2.5">
                        {Array.from({ length: 5 }).map((_, j) => (
                            <Skeleton key={j} className="h-4 w-full border-0" />
                        ))}
                    </div>
                    <Skeleton className="h-11 w-full border-0" />
                </div>
            ))}
        </div>
    );
}

/**
 * Pricing page (Public/Pricing): eyebrow + title + plan cards grid.
 */
export function PricingPageSkeleton({ className }: { className?: string }) {
    return (
        <div
            data-testid="pricing-page-skeleton"
            className={cn('mx-auto max-w-6xl px-4 py-16 md:px-6', className)}
        >
            <Skeleton className="mb-2 h-3 w-20 border-0" />
            <Skeleton className="mb-10 h-9 w-1/2 max-w-lg border-0" />
            <PricingGridSkeleton />
        </div>
    );
}

/**
 * Admin / data-table page: header + action bar + table skeleton.
 */
export function TablePageSkeleton({
    rows = 6,
    columns = 5,
    className,
}: {
    rows?: number;
    columns?: number;
    className?: string;
}) {
    return (
        <div data-testid="table-page-skeleton" className={cn('space-y-6', className)}>
            <div>
                <Skeleton className="mb-2 h-3 w-20 border-0" />
                <Skeleton className="h-9 w-1/2 max-w-md border-0" />
            </div>
            <div className="flex flex-wrap items-center gap-3">
                <Skeleton className="h-11 w-40 border-0" />
                <Skeleton className="ml-auto h-11 w-32 border-0" />
            </div>
            <TableSkeleton rows={rows} columns={columns} />
        </div>
    );
}

/**
 * Region skeletons — the data-backed blocks of a page that are individually
 * deferred. These are what a page actually renders inside <Deferred> while
 * its prop loads; the page skeletons above compose them for whole-page
 * fallbacks.
 */

/** Hero stats row (Welcome): three stat blocks side by side. */
export function StatsRowSkeleton({ className }: { className?: string }) {
    return (
        <div
            data-testid="stats-row-skeleton"
            className={cn('grid grid-cols-3 gap-6', className)}
        >
            {Array.from({ length: 3 }).map((_, i) => (
                <div key={i} className="space-y-2">
                    <Skeleton className="h-9 w-20 border-0" />
                    <Skeleton className="h-3 w-24 border-0" />
                </div>
            ))}
        </div>
    );
}

/** Featured projects section (Welcome): header + cards in a 4-column grid. */
export function FeaturedProjectsSkeleton({
    count = 3,
    className,
}: {
    count?: number;
    className?: string;
}) {
    return (
        <section
            data-testid="featured-projects-skeleton"
            className={cn('border-b border-rule py-16 md:py-24', className)}
        >
            <div className="mx-auto max-w-6xl px-4 md:px-6">
                <div className="mb-12">
                    <Skeleton className="mb-3 h-3 w-24 border-0" />
                    <Skeleton className="h-9 w-1/2 max-w-lg border-0" />
                </div>
                <div className="grid grid-cols-1 gap-6 sm:grid-cols-4">
                    {Array.from({ length: count }).map((_, i) => (
                        <ProjectCardSkeleton key={i} />
                    ))}
                </div>
            </div>
        </section>
    );
}

/** Hero image placeholder (ProjectDetail): 16:9 bordered media block. */
export function HeroImageSkeleton({ className }: { className?: string }) {
    return (
        <div
            data-testid="hero-image-skeleton"
            className={cn(
                'mb-6 aspect-[16/9] w-full border border-rule-strong bg-surface-2',
                className,
            )}
        >
            <Skeleton className="h-full w-full border-0" />
        </div>
    );
}

/** AI coach panel placeholder (ProjectDetail sidebar). */
export function AdvisorPanelSkeleton({ className }: { className?: string }) {
    return (
        <div
            data-testid="advisor-panel-skeleton"
            className={cn('mt-4 border border-rule-strong p-4', className)}
        >
            <Skeleton className="mb-3 h-3 w-32 border-0" />
            <div className="space-y-2">
                <Skeleton className="h-4 w-full border-0" />
                <Skeleton className="h-4 w-5/6 border-0" />
                <Skeleton className="h-4 w-2/3 border-0" />
            </div>
            <Skeleton className="mt-3 h-11 w-full border-0" />
        </div>
    );
}

/**
 * Homepage (Public/Welcome): hero (eyebrow + display title + body + stats)
 * + a featured-projects section. The homepage is long and mostly static, so
 * only the data-backed blocks that are deferred get skeletonised here.
 */
export function HomePageSkeleton({ className }: { className?: string }) {
    return (
        <div data-testid="home-page-skeleton" className={className}>
            {/* Hero */}
            <section className="border-b border-rule">
                <div className="mx-auto grid max-w-6xl grid-cols-1 items-start gap-12 px-4 py-16 md:px-6 md:py-24 lg:grid-cols-[1.05fr_0.95fr] lg:gap-16">
                    <div>
                        <Skeleton className="mb-5 h-3 w-44 border-0" />
                        <Skeleton className="mb-6 h-24 w-full max-w-[34rem] border-0" />
                        <div className="mb-8 max-w-[32rem] space-y-2">
                            <Skeleton className="h-4 w-full border-0" />
                            <Skeleton className="h-4 w-5/6 border-0" />
                        </div>
                        <div className="mb-12 flex flex-wrap gap-3">
                            <Skeleton className="h-12 w-44 border-0" />
                            <Skeleton className="h-12 w-48 border-0" />
                        </div>
                        <div className="grid grid-cols-3 gap-6 border-t border-rule pt-6">
                            {Array.from({ length: 3 }).map((_, i) => (
                                <div key={i} className="space-y-2">
                                    <Skeleton className="h-9 w-20 border-0" />
                                    <Skeleton className="h-3 w-24 border-0" />
                                </div>
                            ))}
                        </div>
                    </div>
                    <div className="h-[380px] border border-rule-strong bg-surface-2 sm:h-[480px] lg:h-[560px]">
                        <Skeleton className="h-full w-full border-0" />
                    </div>
                </div>
            </section>

            {/* Featured projects */}
            <section className="border-b border-rule py-16 md:py-24">
                <div className="mx-auto max-w-6xl px-4 md:px-6">
                    <Skeleton className="mb-3 h-3 w-24 border-0" />
                    <Skeleton className="mb-12 h-9 w-1/2 max-w-lg border-0" />
                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-4">
                        {Array.from({ length: 3 }).map((_, i) => (
                            <ProjectCardSkeleton key={i} />
                        ))}
                    </div>
                </div>
            </section>
        </div>
    );
}
