import { createInertiaApp } from '@inertiajs/react';
import axios from 'axios';
import Echo from 'laravel-echo';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { Channel } from 'pusher-js';
import Pusher from 'pusher-js';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { Toaster } from 'react-hot-toast';
import '../css/app.css';
import GlobalNotifications from './components/global-notifications';
import { initializeTheme } from './hooks/use-appearance';

// Configure axios for CSRF and credentials
axios.defaults.withCredentials = true;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.baseURL = window.location.origin;

// Set up CSRF token for all requests
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = (token as HTMLMetaElement).content;
}

// Type definitions for Pusher authorization
interface PusherAuthResponse {
    auth: string;
    channel_data?: string;
}

interface AuthProps {
    user?: {
        id: number;
        name: string;
        email: string;
    } | null;
}

// Extend window interface for Pusher and Echo
declare global {
    interface Window {
        Pusher: typeof Pusher;
        Echo: Echo<'pusher'>;
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
        const auth = (props as { initialPage?: { props?: { auth?: AuthProps } } }).initialPage?.props?.auth as AuthProps;
        const authUser = auth?.user;

        console.log('Booting Inertia app with auth user:', authUser?.id ?? null);

        // Configure Echo if Pusher credentials are available
        if (import.meta.env.VITE_PUSHER_APP_KEY) {
            window.Pusher = window.Pusher ?? Pusher;

            if (!window.Echo) {
                window.Echo = new Echo({
                    broadcaster: 'pusher',
                    key: import.meta.env.VITE_PUSHER_APP_KEY,
                    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
                    wsHost: 'ws.pusherapp.com',
                    wsPort: 80,
                    wssPort: 443,
                    forceTLS: true,
                    enabledTransports: ['ws', 'wss'],
                    authorizer: (channel: Channel) => ({
                        authorize: (socketId: string, callback: (error: Error | null, authData: PusherAuthResponse | null) => void) => {
                            // Only attempt authentication if user is logged in
                            if (!authUser) {
                                callback(new Error('User not authenticated'), null);
                                return;
                            }

                            axios.post<PusherAuthResponse>('/broadcasting/auth', {
                                socket_id: socketId,
                                channel_name: channel.name,
                            })
                            .then(response => {
                                callback(null, response.data);
                            })
                            .catch((error: unknown) => {
                                if (axios.isAxiosError(error)) {
                                    console.error('Broadcast auth failed', {
                                        status: error.response?.status,
                                        data: error.response?.data,
                                        message: error.message,
                                    });
                                }

                                const authError = new Error(
                                    axios.isAxiosError(error) && error.message ? error.message : 'Authentication failed'
                                );

                                callback(authError, null);
                            });
                        }
                    })
                });

                console.log('Echo instance created for user:', authUser?.id ?? null);
            } else {
                console.log('Reusing existing Echo instance');
            }
        }

        const root = createRoot(el);

        root.render(
            <StrictMode>
                <App {...props} />
                <Toaster
                    position="top-right"
                    toastOptions={{
                        duration: 4000,
                        style: {
                            background: '#363636',
                            color: '#fff',
                        },
                        success: {
                            duration: 3000,
                            iconTheme: {
                                primary: '#10B981',
                                secondary: '#fff',
                            },
                        },
                        error: {
                            duration: 5000,
                            iconTheme: {
                                primary: '#EF4444',
                                secondary: '#fff',
                            },
                        },
                    }}
                />
                <GlobalNotifications auth={auth} />
            </StrictMode>,
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
