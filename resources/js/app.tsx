import { createInertiaApp } from '@inertiajs/react';
import axios from 'axios';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import '../css/app.css';
import { initializeTheme } from './hooks/use-appearance';
// eslint-disable-next-line import/order
import Echo from 'laravel-echo';
// eslint-disable-next-line import/order
import Pusher from 'pusher-js';

declare global {
    interface Window {
        Pusher: typeof Pusher;
    }
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob('./pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <StrictMode>
                <App {...props} />
            </StrictMode>,
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();

// Initialize Laravel Echo for real-time features (only if properly configured)
const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY;
const pusherCluster = import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1';

if (pusherKey && pusherKey !== 'local' && pusherCluster && pusherCluster !== 'local') {
    window.Pusher = Pusher;

    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: pusherKey,
        cluster: pusherCluster,
        wsHost: import.meta.env.VITE_PUSHER_HOST ? import.meta.env.VITE_PUSHER_HOST : `ws-${pusherCluster}.pusherapp.com`,
        wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
        wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
        authorizer: (channel: { name: string }) => {
            return {
                // eslint-disable-next-line @typescript-eslint/no-unsafe-function-type
                authorize: (socketId: string, callback: Function) => {
                    axios.post('/broadcasting/auth', {
                        socket_id: socketId,
                        channel_name: channel.name
                    })
                    .then(response => {
                        callback(false, response.data);
                    })
                    .catch(error => {
                        callback(true, error);
                    });
                }
            };
        },
    });
} else {
    // For local development without Pusher, create a mock Echo instance
    window.Echo = {
        channel: () => ({
            listen: () => ({})
        }),
        private: () => ({
            listen: () => ({})
        }),
        join: () => ({
            here: () => ({}),
            joining: () => ({}),
            leaving: () => ({}),
            listen: () => ({})
        }),
        leave: () => ({}),
        leaveChannel: () => ({})
    } as any;
}
