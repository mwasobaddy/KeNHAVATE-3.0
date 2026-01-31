import { Head, Link, router } from '@inertiajs/react';
import type {
    User} from 'lucide-react';
import {
    ArrowLeft,
    MessageSquare,
    Plus,
    ThumbsUp,
    Reply} from 'lucide-react';
import React, { useState } from 'react';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { usePrivateChannel, useChannelEvent } from '@/hooks/use-echo';

interface User {
    id: number;
    name: string;
    email: string;
}

interface Idea {
    id: number;
    title: string;
    author: User;
    category: {
        name: string;
    };
}

interface Suggestion {
    id: number;
    content: string;
    type: string;
    is_accepted: boolean;
    is_rejected: boolean;
    author: User;
    replies: Reply[];
    like_count: number;
    created_at: string;
}

interface Reply {
    id: number;
    content: string;
    author: User;
    like_count: number;
    created_at: string;
}

interface SuggestionsIndexProps {
    idea: Idea;
    suggestions: {
        data: Suggestion[];
        current_page: number;
        last_page: number;
    };
    stats: {
        total_suggestions: number;
        accepted_suggestions: number;
        rejected_suggestions: number;
        pending_suggestions: number;
        suggestions_by_type: Record<string, number>;
    };
    filters: {
        type?: string;
        status?: string;
    };
}

export default function Index({ idea, suggestions: initialSuggestions, stats, filters }: SuggestionsIndexProps) {
    const [newSuggestion, setNewSuggestion] = useState('');
    const [suggestionType, setSuggestionType] = useState('general');
    const [submitting, setSubmitting] = useState(false);
    const [suggestions, setSuggestions] = useState(initialSuggestions);

    // Set up real-time channel for this idea
    const channel = usePrivateChannel(`idea.${idea.id}`);

    // Listen for new suggestions
    useChannelEvent(channel, '.suggestion.created', (data: { suggestion: Suggestion }) => {
        setSuggestions(prev => ({
            ...prev,
            data: [data.suggestion, ...prev.data]
        }));
    });

    // Listen for accepted suggestions
    useChannelEvent(channel, '.suggestion.accepted', (data: { suggestion: Suggestion }) => {
        setSuggestions(prev => ({
            ...prev,
            data: prev.data.map(suggestion =>
                suggestion.id === data.suggestion.id
                    ? { ...suggestion, is_accepted: true, is_rejected: false }
                    : suggestion
            )
        }));
    });

    const handleSubmitSuggestion = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!newSuggestion.trim()) return;

        setSubmitting(true);
        try {
            await router.post(`/ideas/${idea.id}/suggestions`, {
                content: newSuggestion,
                type: suggestionType,
            }, {
                onSuccess: () => {
                    setNewSuggestion('');
                    setSuggestionType('general');
                },
            });
        } catch (error) {
            console.error('Failed to submit suggestion:', error);
        } finally {
            setSubmitting(false);
        }
    };

    const handleFilterChange = (key: string, value: string) => {
        router.get(`/ideas/${idea.id}/suggestions`, { ...filters, [key]: value }, { preserveState: true });
    };

    const getSuggestionBadgeVariant = (suggestion: Suggestion) => {
        if (suggestion.is_accepted) return 'default';
        if (suggestion.is_rejected) return 'destructive';
        return 'secondary';
    };

    const getSuggestionStatusText = (suggestion: Suggestion) => {
        if (suggestion.is_accepted) return 'Accepted';
        if (suggestion.is_rejected) return 'Rejected';
        return 'Pending';
    };

    const getTypeBadgeVariant = (type: string) => {
        switch (type) {
            case 'improvement': return 'default';
            case 'question': return 'secondary';
            case 'concern': return 'destructive';
            case 'support': return 'default';
            default: return 'outline';
        }
    };

    return (
        <>
            <Head title={`Suggestions - ${idea.title}`} />

            <div className="container mx-auto px-4 py-8 max-w-6xl">
                {/* Header */}
                <div className="flex items-center justify-between mb-8">
                    <div className="flex items-center">
                        <Link href={`/ideas/${idea.id}`}>
                            <Button variant="ghost" size="sm" className="mr-4">
                                <ArrowLeft className="w-4 h-4 mr-2" />
                                Back to Idea
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">Suggestions</h1>
                            <p className="text-gray-600 mt-2">{idea.title}</p>
                        </div>
                    </div>
                    <Link href={`/ideas/${idea.id}/suggestions/create`}>
                        <Button>
                            <Plus className="w-4 h-4 mr-2" />
                            Add Suggestion
                        </Button>
                    </Link>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-4 gap-8">
                    {/* Sidebar */}
                    <div className="lg:col-span-1">
                        {/* Stats Card */}
                        <Card className="mb-6">
                            <CardHeader>
                                <CardTitle className="text-lg">Statistics</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div className="flex justify-between">
                                    <span className="text-sm text-gray-600">Total</span>
                                    <span className="font-semibold">{stats.total_suggestions}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-sm text-green-600">Accepted</span>
                                    <span className="font-semibold text-green-600">{stats.accepted_suggestions}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-sm text-red-600">Rejected</span>
                                    <span className="font-semibold text-red-600">{stats.rejected_suggestions}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-sm text-yellow-600">Pending</span>
                                    <span className="font-semibold text-yellow-600">{stats.pending_suggestions}</span>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Filters */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-lg">Filters</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <Label>Status</Label>
                                    <Select
                                        value={filters.status || 'all'}
                                        onValueChange={(value) => handleFilterChange('status', value === 'all' ? '' : value)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="All Status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Status</SelectItem>
                                            <SelectItem value="pending">Pending</SelectItem>
                                            <SelectItem value="accepted">Accepted</SelectItem>
                                            <SelectItem value="rejected">Rejected</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <Label>Type</Label>
                                    <Select
                                        value={filters.type || 'all'}
                                        onValueChange={(value) => handleFilterChange('type', value === 'all' ? '' : value)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="All Types" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Types</SelectItem>
                                            <SelectItem value="improvement">Improvement</SelectItem>
                                            <SelectItem value="question">Question</SelectItem>
                                            <SelectItem value="concern">Concern</SelectItem>
                                            <SelectItem value="support">Support</SelectItem>
                                            <SelectItem value="general">General</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Main Content */}
                    <div className="lg:col-span-3">
                        {/* Add Suggestion Form */}
                        <Card className="mb-8">
                            <CardHeader>
                                <CardTitle>Add New Suggestion</CardTitle>
                                <CardDescription>
                                    Share your thoughts to help improve this idea
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handleSubmitSuggestion} className="space-y-4">
                                    <div>
                                        <Label htmlFor="suggestion-type">Type</Label>
                                        <Select value={suggestionType} onValueChange={setSuggestionType}>
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="general">General</SelectItem>
                                                <SelectItem value="improvement">Improvement</SelectItem>
                                                <SelectItem value="question">Question</SelectItem>
                                                <SelectItem value="concern">Concern</SelectItem>
                                                <SelectItem value="support">Support</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div>
                                        <Label htmlFor="suggestion-content">Your Suggestion</Label>
                                        <Textarea
                                            id="suggestion-content"
                                            value={newSuggestion}
                                            onChange={(e: { target: { value: React.SetStateAction<string>; }; }) => setNewSuggestion(e.target.value)}
                                            placeholder="Share your suggestion, feedback, or question..."
                                            rows={4}
                                        />
                                    </div>

                                    <Button type="submit" disabled={submitting || !newSuggestion.trim()}>
                                        {submitting ? 'Submitting...' : 'Submit Suggestion'}
                                    </Button>
                                </form>
                            </CardContent>
                        </Card>

                        {/* Suggestions List */}
                        <div className="space-y-6">
                            {suggestions.data.map((suggestion) => (
                                <Card key={suggestion.id}>
                                    <CardHeader>
                                        <div className="flex items-start justify-between">
                                            <div className="flex items-center space-x-3">
                                                <Avatar>
                                                    <AvatarFallback>
                                                        {suggestion.author.name.charAt(0).toUpperCase()}
                                                    </AvatarFallback>
                                                </Avatar>
                                                <div>
                                                    <p className="font-medium">{suggestion.author.name}</p>
                                                    <p className="text-sm text-gray-500">
                                                        {new Date(suggestion.created_at).toLocaleDateString()}
                                                    </p>
                                                </div>
                                            </div>
                                            <div className="flex items-center space-x-2">
                                                <Badge variant={getTypeBadgeVariant(suggestion.type)}>
                                                    {suggestion.type}
                                                </Badge>
                                                <Badge variant={getSuggestionBadgeVariant(suggestion)}>
                                                    {getSuggestionStatusText(suggestion)}
                                                </Badge>
                                            </div>
                                        </div>
                                    </CardHeader>
                                    <CardContent>
                                        <p className="text-gray-700 mb-4 whitespace-pre-wrap">
                                            {suggestion.content}
                                        </p>

                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center space-x-4">
                                                <Button variant="ghost" size="sm">
                                                    <ThumbsUp className="w-4 h-4 mr-1" />
                                                    {suggestion.like_count}
                                                </Button>
                                                <Button variant="ghost" size="sm">
                                                    <Reply className="w-4 h-4 mr-1" />
                                                    {suggestion.replies?.length || 0} replies
                                                </Button>
                                            </div>

                                            <Link href={`/ideas/${idea.id}/suggestions/${suggestion.id}`}>
                                                <Button variant="outline" size="sm">
                                                    View Thread
                                                </Button>
                                            </Link>
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}

                            {suggestions.data.length === 0 && (
                                <div className="text-center py-12">
                                    <MessageSquare className="w-12 h-12 text-gray-400 mx-auto mb-4" />
                                    <h3 className="text-lg font-medium text-gray-900 mb-2">No suggestions yet</h3>
                                    <p className="text-gray-500 mb-4">
                                        Be the first to share your thoughts and help improve this idea!
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}