import { router } from '@inertiajs/react';
import { Head, Link, usePage } from '@inertiajs/react';
import { Search, Plus, Users, MessageSquare, ThumbsUp, Edit, Send } from 'lucide-react';
import React, { useEffect, useRef, useState } from 'react';
import toast from 'react-hot-toast';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import type { SharedData } from '@/types';
import type { BreadcrumbItem } from '@/types/navigation';

interface Idea {
    id: number;
    title: string;
    description: string;
    status: string;
    collaboration_enabled: boolean;
    author: {
        name: string;
    };
    category: {
        name: string;
    };
    collaborators_count: number;
    upvotes_count: number;
    suggestions_count: number;
    created_at: string;
    can_edit?: boolean;
    can_submit?: boolean;
}

interface Category {
    id: number;
    name: string;
}

interface IdeasIndexProps {
    ideas: {
        data: Idea[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    categories: Category[];
    filters: {
        category_id?: string;
        status?: string;
        collaboration_enabled?: string;
        search?: string;
        sort_by?: string;
        sort_direction?: string;
        author_id?: string;
        tab?: string;
    };
    currentTab: string;
}

export default function Index({ ideas, categories, filters, currentTab }: IdeasIndexProps) {
    const { flash } = usePage<SharedData>().props;
    const lastFlashMessageRef = useRef<string | null>(null);
    const [localIdeas, setLocalIdeas] = useState(ideas.data);

    // Update localIdeas when ideas prop changes (e.g., when switching tabs)
    useEffect(() => {
        // eslint-disable-next-line react-hooks/set-state-in-effect
        setLocalIdeas(ideas.data);
    }, [ideas.data]);

    useEffect(() => {
        if (flash?.success && flash.success !== lastFlashMessageRef.current) {
            toast.success(flash.success);
            lastFlashMessageRef.current = flash.success;
        }
    }, [flash]);

    const handleIdeaSubmit = (ideaId: number) => {
        console.log('handleIdeaSubmit called for idea:', ideaId);
        // Optimistically update the local state
        setLocalIdeas(prevIdeas =>
            prevIdeas.map(idea =>
                idea.id === ideaId
                    ? { ...idea, status: 'submitted', can_submit: false }
                    : idea
            )
        );

        router.post(`/ideas/${ideaId}/submit`, {}, {
            onSuccess: () => {
                console.log('Submit success for idea:', ideaId);
                toast.success('Idea submitted for review!');
            },
            onError: (errors: Record<string, string>) => {
                console.log('Submit error for idea:', ideaId, errors);
                // Revert the optimistic update on error
                setLocalIdeas(prevIdeas =>
                    prevIdeas.map(idea =>
                        idea.id === ideaId
                            ? { ...idea, status: 'draft', can_submit: true }
                            : idea
                    )
                );
                toast.error('Failed to submit idea. Please try again.');
            },
        });
    };

    const handleFilterChange = (key: string, value: string) => {
        const newFilters = { ...filters, [key]: value, page: '1' };
        router.get('/ideas', newFilters, { preserveState: true });
    };

    const handleTabChange = (tab: string) => {
        const newFilters = { ...filters, tab, page: '1' };
        // Clear author-specific filters when switching tabs
        if (tab === 'all') {
            delete newFilters.author_id;
        }
        router.get('/ideas', newFilters, { preserveState: true });
    };

    const handleSearch = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const formData = new FormData(e.currentTarget);
        const search = formData.get('search') as string;
        handleFilterChange('search', search);
    };

    const getStatusBadgeVariant = (status: string) => {
        switch (status) {
            case 'draft': return 'secondary';
            case 'submitted': return 'default';
            case 'under_review': return 'outline';
            case 'approved': return 'default';
            case 'rejected': return 'destructive';
            case 'implemented': return 'default';
            default: return 'secondary';
        }
    };

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Ideas',
            href: '/ideas',
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Ideas" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl mt-16 md:mt-12 p-4">
                <div className="flex justify-between items-center mb-8">
                    <div>
                        <h1 className="text-3xl font-bold">Ideas</h1>
                        <p className="text-muted-foreground mt-2">Discover and collaborate on innovative ideas</p>
                    </div>
                    <Link href="/ideas/create">
                        <Button>
                            <Plus className="w-4 h-4 mr-2" />
                            Submit New Idea
                        </Button>
                    </Link>
                </div>

                {/* Tabs */}
                <Tabs value={currentTab} onValueChange={handleTabChange} className="w-full">
                    <TabsList className="grid w-full grid-cols-2">
                        <TabsTrigger value="personal">My Ideas</TabsTrigger>
                        <TabsTrigger value="all">All Ideas</TabsTrigger>
                    </TabsList>

                    {/* Filters */}
                    <Card className="mb-8">
                        <CardContent className="pt-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <form onSubmit={handleSearch} className="relative">
                                    <Search className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                                    <Input
                                        name="search"
                                        placeholder="Search ideas..."
                                        defaultValue={filters.search}
                                        className="pl-10"
                                    />
                                </form>

                                <Select
                                    value={filters.category_id || 'all'}
                                    onValueChange={(value) => handleFilterChange('category_id', value === 'all' ? '' : value)}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="All Categories" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Categories</SelectItem>
                                        {categories.map((category) => (
                                            <SelectItem key={category.id} value={category.id.toString()}>
                                                {category.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>

                                <Select
                                    value={filters.status || 'all'}
                                    onValueChange={(value) => handleFilterChange('status', value === 'all' ? '' : value)}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="All Status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Status</SelectItem>
                                        <SelectItem value="submitted">Submitted</SelectItem>
                                        <SelectItem value="under_review">Under Review</SelectItem>
                                        <SelectItem value="approved">Approved</SelectItem>
                                        <SelectItem value="implemented">Implemented</SelectItem>
                                    </SelectContent>
                                </Select>

                                <Select
                                    value={filters.collaboration_enabled || 'all'}
                                    onValueChange={(value) => handleFilterChange('collaboration_enabled', value === 'all' ? '' : value)}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="All Ideas" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Published Ideas</SelectItem>
                                        <SelectItem value="1">Open for Collaboration</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </CardContent>
                    </Card>

                    <TabsContent value="personal" className="mt-6">
                        <div className="mb-4">
                            <h2 className="text-xl font-semibold">My Ideas</h2>
                            <p className="text-muted-foreground">Ideas you've submitted</p>
                        </div>

                        {/* Ideas Grid */}
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            {localIdeas.map((idea) => (
                                <Card key={idea.id} className="hover:shadow-lg transition-shadow">
                                    <CardHeader>
                                        <div className="flex justify-between items-start">
                                            <div className="flex-1">
                                                <CardTitle className="text-lg line-clamp-2">
                                                    <Link
                                                        href={`/ideas/${idea.id}`}
                                                        className="hover:text-blue-600 transition-colors"
                                                    >
                                                        {idea.title}
                                                    </Link>
                                                </CardTitle>
                                                <CardDescription className="mt-2 line-clamp-3">
                                                    {idea.description}
                                                </CardDescription>
                                            </div>
                                    {idea.collaboration_enabled && (
                                        <Badge variant="secondary" className="ml-2">
                                            <Users className="w-3 h-3 mr-1" />
                                            Open
                                        </Badge>
                                    )}
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div className="flex items-center justify-between mb-4">
                                    <Badge variant={getStatusBadgeVariant(idea.status)}>
                                        {idea.status.replace('_', ' ')}
                                    </Badge>
                                    <span className="text-sm text-gray-500">
                                        {idea.category.name}
                                    </span>
                                </div>

                                <div className="flex items-center justify-between text-sm text-gray-600">
                                    <div className="flex items-center space-x-4">
                                        <div className="flex items-center">
                                            <Users className="w-4 h-4 mr-1" />
                                            {idea.collaborators_count}
                                        </div>
                                        <div className="flex items-center">
                                            <ThumbsUp className="w-4 h-4 mr-1" />
                                            {idea.upvotes_count}
                                        </div>
                                        <div className="flex items-center">
                                            <MessageSquare className="w-4 h-4 mr-1" />
                                            {idea.suggestions_count}
                                        </div>
                                    </div>
                                </div>

                                {/* Action Buttons */}
                                {(idea.can_edit || idea.can_submit || idea.status === 'submitted') && (
                                    <div className="mt-4 flex gap-2">
                                        {idea.can_edit && (
                                            <Link href={`/ideas/${idea.id}/edit`}>
                                                <Button variant="outline" size="sm" className="flex-1">
                                                    <Edit className="w-3 h-3 mr-1" />
                                                    Edit
                                                </Button>
                                            </Link>
                                        )}
                                        {idea.can_submit ? (
                                            <Button
                                                variant="default"
                                                size="sm"
                                                className="flex-1"
                                                onClick={() => handleIdeaSubmit(idea.id)}
                                            >
                                                <Send className="w-3 h-3 mr-1" />
                                                Submit
                                            </Button>
                                        ) : (
                                            <Link href={`/ideas/${idea.id}`} className="flex-1">
                                                <Button variant="secondary" size="sm" className="w-full">
                                                    View submission
                                                </Button>
                                            </Link>
                                        )}
                                    </div>
                                )}

                                <div className="mt-4 pt-4 border-t">
                                    <p className="text-sm text-gray-500">
                                        By {idea.author.name} • {new Date(idea.created_at).toLocaleDateString()}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {localIdeas.length === 0 && (
                    <div className="text-center py-12">
                        <MessageSquare className="w-12 h-12 text-yellow-400 mx-auto mb-4" />
                        <h3 className="text-lg font-medium mb-2">No ideas found</h3>
                        <p className="text-muted-foreground mb-4">
                            {filters.search || filters.category_id || filters.status
                                ? 'Try adjusting your filters to see more ideas.'
                                : 'Be the first to submit an idea and start the collaboration!'}
                        </p>
                        <Link href="/ideas/create">
                            <Button>
                                <Plus className="w-4 h-4 mr-2" />
                                Submit First Idea
                            </Button>
                        </Link>
                    </div>
                )}

                {/* Pagination would go here */}
                    </TabsContent>

                    <TabsContent value="all" className="mt-6">
                        <div className="mb-4">
                            <h2 className="text-xl font-semibold">All Ideas</h2>
                            <p className="text-muted-foreground">Discover ideas from the community</p>
                        </div>

                        {/* Ideas Grid */}
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            {localIdeas.map((idea) => (
                                <Card key={idea.id} className="hover:shadow-lg transition-shadow">
                                    <CardHeader>
                                        <div className="flex justify-between items-start">
                                            <div className="flex-1">
                                                <CardTitle className="text-lg line-clamp-2">
                                                    <Link
                                                        href={`/ideas/${idea.id}`}
                                                        className="hover:text-blue-600 transition-colors"
                                                    >
                                                        {idea.title}
                                                    </Link>
                                                </CardTitle>
                                                <CardDescription className="mt-2 line-clamp-3">
                                                    {idea.description}
                                                </CardDescription>
                                            </div>
                                            {idea.collaboration_enabled && (
                                                <Badge variant="secondary" className="ml-2">
                                                    <Users className="w-3 h-3 mr-1" />
                                                    Open
                                                </Badge>
                                            )}
                                        </div>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="flex items-center justify-between mb-4">
                                            <Badge variant={getStatusBadgeVariant(idea.status)}>
                                                {idea.status.replace('_', ' ')}
                                            </Badge>
                                            <span className="text-sm text-gray-500">
                                                {idea.category.name}
                                            </span>
                                        </div>

                                        <div className="flex items-center justify-between text-sm text-gray-600">
                                            <div className="flex items-center space-x-4">
                                                <div className="flex items-center">
                                                    <Users className="w-4 h-4 mr-1" />
                                                    {idea.collaborators_count}
                                                </div>
                                                <div className="flex items-center">
                                                    <ThumbsUp className="w-4 h-4 mr-1" />
                                                    {idea.upvotes_count}
                                                </div>
                                                <div className="flex items-center">
                                                    <MessageSquare className="w-4 h-4 mr-1" />
                                                    {idea.suggestions_count}
                                                </div>
                                            </div>
                                        </div>

                                        {/* Action Buttons */}
                                        {(idea.can_edit || idea.can_submit || idea.status === 'submitted') && (
                                            <div className="mt-4 flex gap-2">
                                                {idea.can_edit && (
                                                    <Link href={`/ideas/${idea.id}/edit`}>
                                                        <Button variant="outline" size="sm" className="flex-1">
                                                            <Edit className="w-3 h-3 mr-1" />
                                                            Edit
                                                        </Button>
                                                    </Link>
                                                )}
                                                {idea.can_submit ? (
                                                    <Button
                                                        variant="default"
                                                        size="sm"
                                                        className="flex-1"
                                                        onClick={() => handleIdeaSubmit(idea.id)}
                                                    >
                                                        <Send className="w-3 h-3 mr-1" />
                                                        Submit
                                                    </Button>
                                                ) : (
                                                    <Link href={`/ideas/${idea.id}`} className="flex-1">
                                                        <Button variant="secondary" size="sm" className="w-full">
                                                            View submission
                                                        </Button>
                                                    </Link>
                                                )}
                                            </div>
                                        )}

                                        <div className="mt-4 pt-4 border-t">
                                            <p className="text-sm text-gray-500">
                                                By {idea.author.name} • {new Date(idea.created_at).toLocaleDateString()}
                                            </p>
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>

                        {localIdeas.length === 0 && (
                            <div className="text-center py-12">
                                <MessageSquare className="w-12 h-12 text-yellow-400 mx-auto mb-4" />
                                <h3 className="text-lg font-medium mb-2">No ideas found</h3>
                                <p className="text-muted-foreground mb-4">
                                    {filters.search || filters.category_id || filters.status
                                        ? 'Try adjusting your filters to see more ideas.'
                                        : 'Be the first to submit an idea and start the collaboration!'}
                                </p>
                                <Link href="/ideas/create">
                                    <Button>
                                        <Plus className="w-4 h-4 mr-2" />
                                        Submit First Idea
                                    </Button>
                                </Link>
                            </div>
                        )}

                        {/* Pagination would go here */}
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}