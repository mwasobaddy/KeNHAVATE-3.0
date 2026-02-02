import { useEffect, useRef } from 'react';
import toast from 'react-hot-toast';

interface User {
    id: number;
    name: string;
    email: string;
}

interface Auth {
    user?: User | null;
}

interface GlobalNotificationsProps {
    auth?: Auth | null;
}

export default function GlobalNotifications({ auth }: GlobalNotificationsProps) {
    const retryTimer = useRef<NodeJS.Timeout | null>(null);

    const showToastWithAction = (message: string, icon: string, url: string, duration = 6000) => {
        toast.custom(
            (t) => (
                <div
                    onClick={() => {
                        window.location.href = url;
                        toast.dismiss(t.id);
                    }}
                    style={{
                        display: 'flex',
                        gap: '10px',
                        alignItems: 'flex-start',
                        maxWidth: '420px',
                        background: '#fff',
                        border: '1px solid #e5e7eb',
                        borderRadius: '8px',
                        padding: '10px 12px',
                        boxShadow: '0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.1)',
                        cursor: 'pointer',
                    }}
                >
                    <span style={{ fontSize: '20px' }}>{icon}</span>
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '4px' }}>
                        <span style={{ color: '#111827', fontSize: '14px' }}>{message}</span>
                        <span style={{ color: '#2563eb', fontSize: '12px', textDecoration: 'underline' }}>View</span>
                    </div>
                </div>
            ),
            { duration }
        );
    };

    useEffect(() => {
        if (!auth?.user) {
            return;
        }

        let isCancelled = false;
        type EchoChannel = {
            subscribed: (callback: () => void) => void;
            error: (callback: (error: unknown) => void) => void;
            listen: <T = unknown>(event: string, callback: (data: T) => void) => void;
        };
        let userChannel: EchoChannel | null = null;

        const subscribe = () => {
            type EchoInstance = {
                private: (channel: string) => EchoChannel;
                leave?: (channel: string) => void;
            };

            const echo = (window as typeof window & { Echo?: EchoInstance }).Echo;

            if (!echo) {
                console.warn('Echo not ready yet, retrying global notifications...');
                retryTimer.current = setTimeout(() => {
                    if (!isCancelled) {
                        subscribe();
                    }
                }, 500);
                return;
            }

            console.log('Setting up global notification listeners for user:', auth.user!.id);

            userChannel = echo.private(`App.Models.User.${auth.user!.id}`);

            userChannel!.subscribed(() => {
                console.log('Subscribed to user channel for notifications');
            });

            userChannel!.error((error: unknown) => {
                console.error('User channel error', error);
            });

            // Listen for collaborator joined events
            userChannel!.listen('.collaborator.joined', (data: {
                idea: { id: number; title: string };
                collaborator: User;
            }) => {
                console.log('Global collaborator notification received:', data);

                // Don't show toast if user is currently on the idea page
                const isOnIdeaPage = window.location.pathname.includes(`/ideas/${data.idea.id}`);
                if (!isOnIdeaPage) {
                    showToastWithAction(
                        `${data.collaborator.name} joined your idea "${data.idea.title}" as a collaborator!`,
                        '👥',
                        `/ideas/${data.idea.id}`,
                        6000
                    );
                }
            });

            // Listen for idea upvoted events
            userChannel!.listen('.idea.upvoted', (data: {
                idea: { id: number; title: string };
                user: { id: number; name: string };
            }) => {
                console.log('Global idea upvote notification received:', data);

                const isOnIdeaPage = window.location.pathname.includes(`/ideas/${data.idea.id}`);
                if (!isOnIdeaPage) {
                    showToastWithAction(
                        `${data.user.name} upvoted your idea "${data.idea.title}"`,
                        '👍',
                        `/ideas/${data.idea.id}`,
                        5000
                    );
                }
            });

            // Listen for suggestion created events
            userChannel!.listen('.suggestion.created', (data: {
                suggestion: {
                    id: number;
                    content: string;
                    type: string;
                    idea_id: number;
                    author: { id: number; name: string };
                    created_at: string;
                };
            }) => {
                console.log('Global suggestion notification received:', data);

                const snippet = data.suggestion.content.length > 80
                    ? `${data.suggestion.content.substring(0, 77)}...`
                    : data.suggestion.content;

                const ideaId = data.suggestion.idea_id;
                const isOnSameIdea = window.location.pathname.includes(`/ideas/${ideaId}`);

                if (!isOnSameIdea) {
                    showToastWithAction(
                        `${data.suggestion.author.name} added a suggestion: "${snippet}"`,
                        '💡',
                        `/ideas/${ideaId}`,
                        6000
                    );
                }
            });

            // Store reference for cleanup
            const typedWindow = window as typeof window & { userNotificationChannel?: EchoChannel };
            typedWindow.userNotificationChannel = userChannel!;
        };

        subscribe();

        return () => {
            console.log('Cleaning up global notification listeners');
            isCancelled = true;
            if (retryTimer.current) {
                clearTimeout(retryTimer.current);
            }
            const typedWindow = window as typeof window & {
                userNotificationChannel?: EchoChannel;
                Echo?: { leave?: (channel: string) => void };
            };

            if (typedWindow.userNotificationChannel) {
                typedWindow.Echo?.leave?.(`App.Models.User.${auth.user?.id}`);
                delete typedWindow.userNotificationChannel;
            }
        };
    }, [auth?.user, auth?.user?.id]);

    return null;
}