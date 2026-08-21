import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import type { ResolvedComponent } from '@inertiajs/react';
import { configureEcho } from '@laravel/echo-react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import RootLayout from '@/layouts/root-layout';

configureEcho({
    broadcaster: 'reverb',
});

const appName = import.meta.env.VITE_APP_NAME || 'Kanda';

function resolve(name: string) {
    const pages = import.meta.glob('./pages/**/*.tsx') as Record<
        string,
        () => Promise<{ default: ResolvedComponent }>
    >;

    return resolvePageComponent(
        `./pages/${name}.tsx`,
        pages,
    ) as unknown as Promise<ResolvedComponent>;
}

createInertiaApp({
    title: (title) => (title ? `${title} — ${appName}` : appName),
    layout: () => RootLayout,
    resolve,
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(<App {...props} />);
    },
    progress: {
        color: '#3B82F6',
    },
});
