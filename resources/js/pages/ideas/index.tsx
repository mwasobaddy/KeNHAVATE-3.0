import { router } from '@inertiajs/react';
import { Head, Link } from '@inertiajs/react';
import { Search, Plus, Users, MessageSquare, ThumbsUp } from 'lucide-react';
import React from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

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
    };
}

export default function Index({ ideas, categories, filters }: IdeasIndexProps) {
    const handleFilterChange = (key: string, value: string) => {
        const newFilters = { ...filters, [key]: value, page: '1' };
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

    return (
        <>
            <Head title="Ideas" />

            <div className="container mx-auto px-4 py-8">
                <div className="flex justify-between items-center mb-8">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Ideas</h1>
                        <p className="text-gray-600 mt-2">Discover and collaborate on innovative ideas</p>
                    </div>
                    <Link href="/ideas/create">
                        <Button>
                            <Plus className="w-4 h-4 mr-2" />
                            Submit New Idea
                        </Button>
                    </Link>
                </div>

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
                                value={filters.category_id || ''}
                                onValueChange={(value) => handleFilterChange('category_id', value)}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="All Categories" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">All Categories</SelectItem>
                                    {categories.map((category) => (
                                        <SelectItem key={category.id} value={category.id.toString()}>
                                            {category.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <Select
                                value={filters.status || ''}
                                onValueChange={(value) => handleFilterChange('status', value)}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="All Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">All Status</SelectItem>
                                    <SelectItem value="submitted">Submitted</SelectItem>
                                    <SelectItem value="under_review">Under Review</SelectItem>
                                    <SelectItem value="approved">Approved</SelectItem>
                                    <SelectItem value="implemented">Implemented</SelectItem>
                                </SelectContent>
                            </Select>

                            <Select
                                value={filters.collaboration_enabled || ''}
                                onValueChange={(value) => handleFilterChange('collaboration_enabled', value)}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="All Ideas" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">All Ideas</SelectItem>
                                    <SelectItem value="1">Open for Collaboration</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </CardContent>
                </Card>

                {/* Ideas Grid */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {ideas.data.map((idea) => (
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

                                <div className="mt-4 pt-4 border-t">
                                    <p className="text-sm text-gray-500">
                                        By {idea.author.name} • {new Date(idea.created_at).toLocaleDateString()}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {ideas.data.length === 0 && (
                    <div className="text-center py-12">
                        <MessageSquare className="w-12 h-12 text-gray-400 mx-auto mb-4" />
                        <h3 className="text-lg font-medium text-gray-900 mb-2">No ideas found</h3>
                        <p className="text-gray-500 mb-4">
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
            </div>
        </>
    );
}