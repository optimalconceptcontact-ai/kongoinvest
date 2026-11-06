import { Head, usePage } from '@inertiajs/react';

type SharedUrl = { url?: string };

/**
 * Spec §123: every public page needs title, meta description,
 * OpenGraph, and canonical. Earlier phases added `<Head>` + a bare meta
 * description ad hoc per page (Welcome, About, Pricing); this
 * centralizes the full set so a new public page gets all four for free
 * instead of the pattern silently drifting per page.
 */
export function SeoHead({
    title,
    description,
    image,
}: {
    title: string;
    description: string;
    image?: string;
}) {
    const page = usePage<SharedUrl>();
    const canonical =
        typeof window !== 'undefined'
            ? window.location.origin + page.url
            : undefined;

    return (
        <Head title={title}>
            <meta name="description" content={description} />
            {canonical && <link rel="canonical" href={canonical} />}

            <meta property="og:type" content="website" />
            <meta property="og:title" content={title} />
            <meta property="og:description" content={description} />
            <meta property="og:site_name" content="InvestKongo" />
            {canonical && <meta property="og:url" content={canonical} />}
            {image && <meta property="og:image" content={image} />}

            <meta name="twitter:card" content="summary" />
            <meta name="twitter:title" content={title} />
            <meta name="twitter:description" content={description} />
        </Head>
    );
}
