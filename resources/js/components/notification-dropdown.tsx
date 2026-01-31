import { Link, router } from '@inertiajs/react';
import { Bell, Check, ExternalLink, MessageSquare, Settings, ThumbsUp, UserPlus } from 'lucide-react';
import React, { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { ScrollArea } from '@/components/ui/scroll-area';

interface User {
    id: number;
    name: string;
    email: string;
}

type NotificationType = 'suggestion_created' | 'suggestion_accepted' | 'idea_upvoted' | 'collaborator_joined' | string;

interface Notification {
    id: number;
    type: NotificationType;
    title: string;
    message: string;
    sender: User | null;
    is_read: boolean;
    created_at: string;
    url: string;
}

interface NotificationDropdownProps {
    unreadCount: number;
    recentNotifications?: Notification[];
}

export function NotificationDropdown({ unreadCount, recentNotifications = [] }: NotificationDropdownProps) {
    const [notifications, setNotifications] = useState<Notification[]>(recentNotifications);

    const handleMarkAsRead = async (notificationId: number) => {
        try {
            await router.patch(`/notifications/${notificationId}/read`);
            setNotifications(prev =>
                prev.map(n =>
                    n.id === notificationId ? { ...n, is_read: true } : n
                )
            );
        } catch (error) {
            console.error('Failed to mark notification as read:', error);
        }
    };

    const getNotificationIcon = (type: NotificationType): React.ReactElement => {
        switch (type) {
            case 'suggestion_created':
                return <MessageSquare className="w-4 h-4 text-blue-500" />;
            case 'suggestion_accepted':
                return <Check className="w-4 h-4 text-green-500" />;
            case 'idea_upvoted':
                return <ThumbsUp className="w-4 h-4 text-purple-500" />;
            case 'collaborator_joined':
                return <UserPlus className="w-4 h-4 text-orange-500" />;
            default:
                return <Bell className="w-4 h-4 text-gray-500" />;
        }
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="sm" className="relative">
                    <Bell className="w-5 h-5" />
                    {unreadCount > 0 && (
                        <Badge
                            variant="destructive"
                            className="absolute -top-1 -right-1 h-5 w-5 flex items-center justify-center p-0 text-xs"
                        >
                            {unreadCount > 99 ? '99+' : unreadCount}
                        </Badge>
                    )}
                </Button>
            </DropdownMenuTrigger>

            <DropdownMenuContent align="end" className="w-80">
                <DropdownMenuLabel className="flex items-center justify-between">
                    <span>Notifications</span>
                    {unreadCount > 0 && (
                        <Badge variant="secondary">{unreadCount} unread</Badge>
                    )}
                </DropdownMenuLabel>

                <DropdownMenuSeparator />

                <ScrollArea className="h-80">
                    {notifications.length === 0 ? (
                        <div className="p-4 text-center text-sm text-gray-500">
                            No notifications yet
                        </div>
                    ) : (
                        notifications.slice(0, 10).map((notification) => (
                            <DropdownMenuItem
                                key={notification.id}
                                className={`flex flex-col items-start p-4 cursor-pointer ${
                                    !notification.is_read ? 'bg-blue-50' : ''
                                }`}
                                onClick={() => !notification.is_read && handleMarkAsRead(notification.id)}
                            >
                                <div className="flex items-start space-x-3 w-full">
                                    <div className="shrink-0 mt-0.5">
                                        {getNotificationIcon(notification.type)}
                                    </div>

                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center justify-between">
                                            <p className="text-sm font-medium text-gray-900 truncate">
                                                {notification.title}
                                            </p>
                                            {!notification.is_read && (
                                                <div className="w-2 h-2 bg-blue-500 rounded-full shrink-0 ml-2" />
                                            )}
                                        </div>

                                        <p className="text-sm text-gray-600 line-clamp-2 mt-1">
                                            {notification.message}
                                        </p>

                                        <div className="flex items-center justify-between mt-2">
                                            <span className="text-xs text-gray-500">
                                                {new Date(notification.created_at).toLocaleDateString()}
                                            </span>

                                            <Link
                                                href={notification.url}
                                                className="text-xs text-blue-600 hover:text-blue-800 flex items-center"
                                                onClick={(e) => e.stopPropagation()}
                                            >
                                                View <ExternalLink className="w-3 h-3 ml-1" />
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </DropdownMenuItem>
                        ))
                    )}
                </ScrollArea>

                <DropdownMenuSeparator />

                <DropdownMenuItem asChild>
                    <Link href="/notifications" className="flex items-center">
                        <Bell className="w-4 h-4 mr-2" />
                        View All Notifications
                    </Link>
                </DropdownMenuItem>

                <DropdownMenuItem asChild>
                    <Link href="/notifications/preferences" className="flex items-center">
                        <Settings className="w-4 h-4 mr-2" />
                        Notification Settings
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}