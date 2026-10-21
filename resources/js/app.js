import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Ticket Event';

const brandTokens = {
    '--brand-primary': import.meta.env.VITE_BRAND_PRIMARY || '#d95d39',
    '--brand-primary-dark': import.meta.env.VITE_BRAND_PRIMARY_DARK || '#b74327',
    '--brand-ink': import.meta.env.VITE_BRAND_INK || '#172033',
    '--brand-paper': import.meta.env.VITE_BRAND_PAPER || '#f7f4ee',
    '--brand-muted': import.meta.env.VITE_BRAND_MUTED || '#667085',
};

Object.entries(brandTokens).forEach(([name, value]) => {
    document.documentElement.style.setProperty(name, value);
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

