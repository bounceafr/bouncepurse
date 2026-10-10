import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { type ComponentType } from 'react';
import ReactDOMServer from 'react-dom/server';

const pages = import.meta.glob<{ default: ComponentType }>('./pages/**/*.tsx');

createServer((page) => {
    const appName = (page.props.name as string | undefined) || 'Bounce Nodes';

    return createInertiaApp({
        page,
        render: (element) => ReactDOMServer.renderToString(element),
        title: (title) => (title ? `${title} - ${appName}` : appName),
        resolve: (name) =>
            resolvePageComponent(`./pages/${name}.tsx`, pages).then(
                (page) => page.default,
            ),
        setup: ({ App, props }) => {
            return <App {...props} />;
        },
    });
});
