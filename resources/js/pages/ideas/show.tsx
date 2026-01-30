import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    Edit,
    Users,
    MessageSquare,
    ThumbsUp,
    ThumbsDown,
    UserPlus,
    CheckCircle,
    XCircle,
    Clock,
    FileText
} from 'lucide-react';
import React, { useEffect, useState } from 'react';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { usePrivateChannel, useChannelEvent } from '@/hooks/use-echo';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

interface User {
    id: number;
    name: string;
    email: string;
}

interface Category {
    id: number;
    name: string;
}

interface Collaborator {
    id: number;
    user: User;
    joined_at: string;
    contribution_points: number;
}

interface Upvote {
    id: number;
    user: User;
}

interface Revision {
    id: number;
    revision_number: number;
    change_summary: string;
    user: User;
    created_at: string;
}

interface Idea {
    id: number;
    title: string;
    description: string;
    category: Category;
    problem_statement: string;
    proposed_solution: string;
    cost_benefit_analysis?: string;
    proposal_document_path?: string;
    collaboration_enabled: boolean;
    status: string;
    author: User;
    collaborators: Collaborator[];
    upvotes: Upvote[];
    revisions: Revision[];
    created_at: string;
    updated_at: string;
}

interface ShowIdeaProps {
    idea: Idea;
    stats: {
        total_upvotes: number;
        total_collaborators: number;
        total_suggestions: number;
        total_revisions: number;
        is_collaboration_enabled: boolean;
        status: string;
    };
    canEdit: boolean;
    canCollaborate: boolean;
    hasUpvoted: boolean;
    isCollaborator: boolean;
}

export default function Show({
    idea: initialIdea,
    stats: initialStats,
    canEdit,
    canCollaborate,
    hasUpvoted: initialHasUpvoted,
    isCollaborator: initialIsCollaborator
}: ShowIdeaProps) {
    const [upvoting, setUpvoting] = useState(false);
    const [idea, setIdea] = useState(initialIdea);
    const [stats, setStats] = useState(initialStats);
    const [hasUpvoted, setHasUpvoted] = useState(initialHasUpvoted);
    const [isCollaborator, setIsCollaborator] = useState(initialIsCollaborator);

    // Set up real-time channel for this idea
    const channel = usePrivateChannel(`idea.${idea.id}`);

    // Listen for upvotes
    useChannelEvent(channel, '.idea.upvoted', (data: any) => {
        setStats(prev => ({
            ...prev,
            total_upvotes: prev.total_upvotes + 1
        }));
    });

    // Listen for collaborator joins
    useChannelEvent(channel, '.collaborator.joined', (data: any) => {
        setStats(prev => ({
            ...prev,
            total_collaborators: prev.total_collaborators + 1
        }));
        setIdea(prev => ({
            ...prev,
            collaborators: [...prev.collaborators, {
                id: Date.now(), // Temporary ID
                user: data.collaborator,
                joined_at: new Date().toISOString(),
                contribution_points: 0
            }]
        }));
    });

    const handleUpvote = async () => {
        if (upvoting) return;

        setUpvoting(true);
        try {
            if (hasUpvoted) {
                await router.delete(`/ideas/${idea.id}/upvote`);
                setHasUpvoted(false);
                setStats(prev => ({
                    ...prev,
                    total_upvotes: Math.max(0, prev.total_upvotes - 1)
                }));
            } else {
                await router.post(`/ideas/${idea.id}/upvote`);
                setHasUpvoted(true);
                setStats(prev => ({
                    ...prev,
                    total_upvotes: prev.total_upvotes + 1
                }));
            }
        } catch (error) {
            console.error('Failed to toggle upvote:', error);
        } finally {
            setUpvoting(false);
        }
    };

    const handleJoinCollaboration = () => {
        router.post(`/ideas/${idea.id}/join-collaboration`);
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

    const getStatusIcon = (status: string) => {
        switch (status) {
            case 'approved': return <CheckCircle className="w-4 h-4" />;
            case 'rejected': return <XCircle className="w-4 h-4" />;
            case 'implemented': return <CheckCircle className="w-4 h-4" />;
            default: return <Clock className="w-4 h-4" />;
        }
    };

    return (
        <>
            <Head title={idea.title} />

            <div className="container mx-auto px-4 py-8 max-w-6xl">
                {/* Header */}
                <div className="flex items-center justify-between mb-8">
                    <div className="flex items-center">
                        <Link href="/ideas">
                            <Button variant="ghost" size="sm" className="mr-4">
                                <ArrowLeft className="w-4 h-4 mr-2" />
                                Back to Ideas
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">{idea.title}</h1>
                            <p className="text-gray-600 mt-2">By {idea.author.name} • {idea.category.name}</p>
                        </div>
                    </div>
                    <div className="flex items-center space-x-2">
                        <Badge variant={getStatusBadgeVariant(idea.status)} className="flex items-center">
                            {getStatusIcon(idea.status)}
                            <span className="ml-1 capitalize">{idea.status.replace('_', ' ')}</span>
                        </Badge>
                        {canEdit && (
                            <Link href={`/ideas/${idea.id}/edit`}>
                                <Button variant="outline" size="sm">
                                    <Edit className="w-4 h-4 mr-2" />
                                    Edit
                                </Button>
                            </Link>
                        )}
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    {/* Main Content */}
                    <div className="lg:col-span-2">
                        <Tabs defaultValue="overview" className="w-full">
                            <TabsList className="grid w-full grid-cols-4">
                                <TabsTrigger value="overview">Overview</TabsTrigger>
                                <TabsTrigger value="problem">Problem</TabsTrigger>
                                <TabsTrigger value="solution">Solution</TabsTrigger>
                                <TabsTrigger value="analysis">Analysis</TabsTrigger>
                            </TabsList>

                            <TabsContent value="overview" className="mt-6">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Description</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <p className="text-gray-700 whitespace-pre-wrap">{idea.description}</p>
                                    </CardContent>
                                </Card>
                            </TabsContent>

                            <TabsContent value="problem" className="mt-6">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Problem Statement</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <p className="text-gray-700 whitespace-pre-wrap">{idea.problem_statement}</p>
                                    </CardContent>
                                </Card>
                            </TabsContent>

                            <TabsContent value="solution" className="mt-6">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Proposed Solution</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <p className="text-gray-700 whitespace-pre-wrap">{idea.proposed_solution}</p>
                                    </CardContent>
                                </Card>
                            </TabsContent>

                            <TabsContent value="analysis" className="mt-6">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Cost-Benefit Analysis</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        {idea.cost_benefit_analysis ? (
                                            <p className="text-gray-700 whitespace-pre-wrap">{idea.cost_benefit_analysis}</p>
                                        ) : (
                                            <p className="text-gray-500 italic">No cost-benefit analysis provided</p>
                                        )}
                                    </CardContent>
                                </Card>

                                {idea.proposal_document_path && (
                                    <Card className="mt-4">
                                        <CardHeader>
                                            <CardTitle>Additional Documentation</CardTitle>
                                        </CardHeader>
                                        <CardContent>
                                            <a
                                                href={idea.proposal_document_path}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="text-blue-600 hover:text-blue-800 flex items-center"
                                            >
                                                <FileText className="w-4 h-4 mr-2" />
                                                View Proposal Document
                                            </a>
                                        </CardContent>
                                    </Card>
                                )}
                            </TabsContent>
                        </Tabs>
                    </div>

                    {/* Sidebar */}
                    <div className="space-y-6">
                        {/* Stats Card */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Statistics</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center">
                                        <ThumbsUp className="w-4 h-4 mr-2 text-gray-500" />
                                        <span className="text-sm">Upvotes</span>
                                    </div>
                                    <span className="font-semibold">{stats.total_upvotes}</span>
                                </div>
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center">
                                        <Users className="w-4 h-4 mr-2 text-gray-500" />
                                        <span className="text-sm">Collaborators</span>
                                    </div>
                                    <span className="font-semibold">{stats.total_collaborators}</span>
                                </div>
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center">
                                        <MessageSquare className="w-4 h-4 mr-2 text-gray-500" />
                                        <span className="text-sm">Suggestions</span>
                                    </div>
                                    <span className="font-semibold">{stats.total_suggestions}</span>
                                </div>
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center">
                                        <Edit className="w-4 h-4 mr-2 text-gray-500" />
                                        <span className="text-sm">Revisions</span>
                                    </div>
                                    <span className="font-semibold">{stats.total_revisions}</span>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Actions Card */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Actions</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <Button
                                    onClick={handleUpvote}
                                    disabled={upvoting}
                                    variant={hasUpvoted ? "default" : "outline"}
                                    className="w-full"
                                >
                                    {hasUpvoted ? (
                                        <>
                                            <ThumbsDown className="w-4 h-4 mr-2" />
                                            Remove Upvote
                                        </>
                                    ) : (
                                        <>
                                            <ThumbsUp className="w-4 h-4 mr-2" />
                                            Upvote Idea
                                        </>
                                    )}
                                </Button>

                                {canCollaborate && !isCollaborator && (
                                    <Button onClick={handleJoinCollaboration} className="w-full">
                                        <UserPlus className="w-4 h-4 mr-2" />
                                        Join as Collaborator
                                    </Button>
                                )}

                                {idea.collaboration_enabled && (
                                    <Link href={`/ideas/${idea.id}/suggestions`}>
                                        <Button variant="outline" className="w-full">
                                            <MessageSquare className="w-4 h-4 mr-2" />
                                            View Suggestions
                                        </Button>
                                    </Link>
                                )}

                                {isCollaborator && (
                                    <Link href={`/ideas/${idea.id}/suggestions/create`}>
                                        <Button variant="outline" className="w-full">
                                            <MessageSquare className="w-4 h-4 mr-2" />
                                            Add Suggestion
                                        </Button>
                                    </Link>
                                )}
                            </CardContent>
                        </Card>

                        {/* Collaborators Card */}
                        {idea.collaborators.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Collaborators</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-3">
                                        {idea.collaborators.map((collaborator) => (
                                            <div key={collaborator.id} className="flex items-center justify-between">
                                                <div className="flex items-center">
                                                    <Avatar className="w-8 h-8 mr-3">
                                                        <AvatarFallback>
                                                            {collaborator.user.name.charAt(0).toUpperCase()}
                                                        </AvatarFallback>
                                                    </Avatar>
                                                    <div>
                                                        <p className="text-sm font-medium">{collaborator.user.name}</p>
                                                        <p className="text-xs text-gray-500">
                                                            Joined {new Date(collaborator.joined_at).toLocaleDateString()}
                                                        </p>
                                                    </div>
                                                </div>
                                                <Badge variant="secondary">
                                                    {collaborator.contribution_points} pts
                                                </Badge>
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        {/* Recent Revisions */}
                        {idea.revisions.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Recent Changes</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-3">
                                        {idea.revisions.slice(0, 5).map((revision) => (
                                            <div key={revision.id} className="text-sm">
                                                <p className="font-medium">{revision.change_summary}</p>
                                                <p className="text-gray-500">
                                                    by {revision.user.name} • {new Date(revision.created_at).toLocaleDateString()}
                                                </p>
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}