import { Head, Link, router } from '@inertiajs/react';
import {
    Bell,
    Check,
    CheckCheck,
    Trash2,
    ExternalLink,
    Clock,
    MessageSquare,
    ThumbsUp,
    UserPlus
} from 'lucide-react';
import React, { useState } from 'react';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface User {
    id: number;
    name: string;
    email: string;
}

interface Notification {
    id: number;
    type: string;
    title: string;
    message: string;
    data: unknown;
    sender: User | null;
    notifiable_id: number;
    notifiable_type: string;
    is_read: boolean;
    created_at: string;
    url: string;
}

interface NotificationsIndexProps {
    notifications: {
        data: Notification[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    unreadCount: number;
}

export default function Index({ notifications, unreadCount }: NotificationsIndexProps) {
    const [filter, setFilter] = useState('all');
    const [typeFilter, setTypeFilter] = useState('all');

    const handleMarkAsRead = async (notificationId: number) => {
        try {
            await router.patch(`/notifications/${notificationId}/read`);
        } catch (error) {
            console.error('Failed to mark notification as read:', error);
        }
    };

    const handleMarkAllAsRead = async () => {
        try {
            await router.patch('/notifications/mark-all-read');
        } catch (error) {
            console.error('Failed to mark all notifications as read:', error);
        }
    };

    const handleDelete = async (notificationId: number) => {
        if (!confirm('Are you sure you want to delete this notification?')) return;

        try {
            await router.delete(`/notifications/${notificationId}`);
        } catch (error) {
            console.error('Failed to delete notification:', error);
        }
    };

    const getNotificationIcon = (type: string) => {
        switch (type) {
            case 'suggestion_created':
                return <MessageSquare className="w-5 h-5 text-blue-500" />;
            case 'suggestion_accepted':
                return <Check className="w-5 h-5 text-green-500" />;
            case 'idea_upvoted':
                return <ThumbsUp className="w-5 h-5 text-purple-500" />;
            case 'collaborator_joined':
                return <UserPlus className="w-5 h-5 text-orange-500" />;
            default:
                return <Bell className="w-5 h-5 text-gray-500" />;
        }
    };

    const getNotificationBadgeVariant = (type: string) => {
        switch (type) {
            case 'suggestion_created':
                return 'default';
            case 'suggestion_accepted':
                return 'default';
            case 'idea_upvoted':
                return 'secondary';
            case 'collaborator_joined':
                return 'outline';
            default:
                return 'secondary';
        }
    };

    const filteredNotifications = notifications.data.filter(notification => {
        if (filter === 'unread' && notification.is_read) return false;
        if (filter === 'read' && !notification.is_read) return false;
        if (typeFilter !== 'all' && notification.type !== typeFilter) return false;
        return true;
    });
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Notifications',
            href: 'notifications',
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Notifications" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl mt-16 md:mt-12 p-4">
                {/* Header */}
                <div className="flex items-center justify-between mb-8">
                    <div className="flex items-center">
                        <Bell className="w-8 h-8 mr-3" />
                        <div>
                            <h1 className="text-3xl font-bold">Notifications</h1>
                            <p className="text-muted-foreground mt-1">
                                {unreadCount > 0 ? (
                                    <>You have <span className="font-semibold text-blue-600">{unreadCount}</span> unread notification{unreadCount !== 1 ? 's' : ''}</>
                                ) : (
                                    'You\'re all caught up!'
                                )}
                            </p>
                        </div>
                    </div>

                    {unreadCount > 0 && (
                        <Button onClick={handleMarkAllAsRead} variant="outline">
                            <CheckCheck className="w-4 h-4 mr-2" />
                            Mark All Read
                        </Button>
                    )}
                </div>

                {/* Filters */}
                <Card className="mb-6">
                    <CardContent className="pt-6">
                        <div className="flex flex-col sm:flex-row gap-4">
                            <div className="flex-1">
                                <label className="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                <Select value={filter} onValueChange={setFilter}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Notifications</SelectItem>
                                        <SelectItem value="unread">Unread Only</SelectItem>
                                        <SelectItem value="read">Read Only</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="flex-1">
                                <label className="block text-sm font-medium text-gray-700 mb-2">Type</label>
                                <Select value={typeFilter} onValueChange={setTypeFilter}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Types</SelectItem>
                                        <SelectItem value="suggestion_created">New Suggestions</SelectItem>
                                        <SelectItem value="suggestion_accepted">Accepted Suggestions</SelectItem>
                                        <SelectItem value="idea_upvoted">Idea Upvotes</SelectItem>
                                        <SelectItem value="collaborator_joined">New Collaborators</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Notifications List */}
                <div className="space-y-4">
                    {filteredNotifications.length === 0 ? (
                        <Card>
                            <CardContent className="py-12 text-center">
                                <Bell className="w-12 h-12 text-yellow-400 mx-auto mb-4" />
                                <h3 className="text-lg font-medium mb-2">No notifications</h3>
                                <p className="text-muted-foreground">
                                    {filter === 'unread' ? 'You have no unread notifications.' :
                                     filter === 'read' ? 'You have no read notifications.' :
                                     'You haven\'t received any notifications yet.'}
                                </p>
                            </CardContent>
                        </Card>
                    ) : (
                        filteredNotifications.map((notification) => (
                            <Card key={notification.id} className={`transition-all ${!notification.is_read ? 'border-l-4 border-l-blue-500 bg-blue-50' : ''}`}>
                                <CardContent className="p-6">
                                    <div className="flex items-start justify-between">
                                        <div className="flex items-start space-x-4 flex-1">
                                            <div className="shrink-0 mt-1">
                                                {getNotificationIcon(notification.type)}
                                            </div>

                                            <div className="flex-1 min-w-0">
                                                <div className="flex items-center space-x-2 mb-2">
                                                    <h3 className="text-lg font-semibold text-gray-900">
                                                        {notification.title}
                                                    </h3>
                                                    <Badge variant={getNotificationBadgeVariant(notification.type)}>
                                                        {notification.type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}
                                                    </Badge>
                                                    {!notification.is_read && (
                                                        <Badge variant="destructive" className="text-xs">New</Badge>
                                                    )}
                                                </div>

                                                <p className="text-gray-700 mb-3">{notification.message}</p>

                                                <div className="flex items-center text-sm text-gray-500 space-x-4">
                                                    <div className="flex items-center">
                                                        <Clock className="w-4 h-4 mr-1" />
                                                        {new Date(notification.created_at).toLocaleString()}
                                                    </div>

                                                    {notification.sender && (
                                                        <div className="flex items-center">
                                                            <Avatar className="w-4 h-4 mr-1">
                                                                <AvatarFallback className="text-xs">
                                                                    {notification.sender.name.charAt(0).toUpperCase()}
                                                                </AvatarFallback>
                                                            </Avatar>
                                                            {notification.sender.name}
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        </div>

                                        <div className="flex items-center space-x-2 ml-4">
                                            <Link href={notification.url}>
                                                <Button variant="outline" size="sm">
                                                    <ExternalLink className="w-4 h-4 mr-1" />
                                                    View
                                                </Button>
                                            </Link>

                                            {!notification.is_read && (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => handleMarkAsRead(notification.id)}
                                                >
                                                    <Check className="w-4 h-4 mr-1" />
                                                    Mark Read
                                                </Button>
                                            )}

                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleDelete(notification.id)}
                                                className="text-red-600 hover:text-red-700"
                                            >
                                                <Trash2 className="w-4 h-4" />
                                            </Button>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        ))
                    )}
                </div>

                {/* Pagination would go here if needed */}
            </div>
        </ AppLayout>
    );
}