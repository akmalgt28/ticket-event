import '../css/app.css';
import '@fortawesome/fontawesome-free/css/all.min.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Ticket Event';

const brandTokens = {
    '--brand-primary': import.meta.env.VITE_BRAND_PRIMARY || '#144ba3',
    '--brand-primary-dark': import.meta.env.VITE_BRAND_PRIMARY_DARK || '#0e397e',
    '--brand-ink': import.meta.env.VITE_BRAND_INK || '#122b53',
    '--brand-paper': import.meta.env.VITE_BRAND_PAPER || '#f4f7fb',
    '--brand-muted': import.meta.env.VITE_BRAND_MUTED || '#64748b',
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

