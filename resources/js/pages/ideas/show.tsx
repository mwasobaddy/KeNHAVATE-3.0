import { Head, Link, router, usePage } from '@inertiajs/react';
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
    FileText,
    Send
} from 'lucide-react';
import React, { useEffect, useState } from 'react';
import toast from 'react-hot-toast';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import type { SharedData } from '@/types';
import type { BreadcrumbItem } from '@/types';

type EchoInstance = {
    private: (channel: string) => {
        listen: <T = Record<string, unknown>>(event: string, callback: (data: T) => void) => void;
    };
    leave: (channel: string) => void;
};

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
    name: string;
    email: string;
    pivot: {
        joined_at: string;
        contribution_points: number;
    };
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

    const { auth } = usePage<SharedData>().props;

    // Set up real-time listeners for this idea
    useEffect(() => {
        const isAuthor = auth?.user?.id === idea.author.id;
        const canListen = isAuthor || isCollaborator;
        const echo: EchoInstance | undefined = typeof window !== 'undefined'
            ? (window as typeof window & { Echo?: EchoInstance }).Echo
            : undefined;

        if (!auth?.user || !canListen || !echo) {
            return;
        }

        console.log('Setting up Echo listeners for idea:', idea.id);

        const channel = echo.private(`idea.${idea.id}`);

        channel.listen('.idea.upvoted', () => {
            console.log('Received idea.upvoted event');
            setStats(prev => ({
                ...prev,
                total_upvotes: prev.total_upvotes + 1
            }));
        });

        channel.listen('.idea.upvote_removed', () => {
            console.log('Received idea.upvote_removed event');
            setStats(prev => ({
                ...prev,
                total_upvotes: Math.max(0, prev.total_upvotes - 1)
            }));
        });

        channel.listen('.collaborator.joined', (data: { collaborator: User }) => {
            console.log('Received collaborator.joined event:', data);
            setStats(prev => ({
                ...prev,
                total_collaborators: prev.total_collaborators + 1
            }));
            setIdea(prev => ({
                ...prev,
                collaborators: [...prev.collaborators, {
                    id: data.collaborator.id,
                    name: data.collaborator.name,
                    email: data.collaborator.email,
                    pivot: {
                        joined_at: new Date().toISOString(),
                        contribution_points: 0
                    }
                }]
            }));
        });

        return () => {
            console.log('Cleaning up Echo listeners');
            echo.leave(`idea.${idea.id}`);
        };
    }, [auth?.user, idea.author.id, idea.id, isCollaborator]);

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
                toast.success('Upvote removed!');
            } else {
                await router.post(`/ideas/${idea.id}/upvote`);
                setHasUpvoted(true);
                setStats(prev => ({
                    ...prev,
                    total_upvotes: prev.total_upvotes + 1
                }));
                toast.success('Idea upvoted!');
            }
        } catch (error) {
            console.error('Failed to toggle upvote:', error);
            toast.error('Failed to update upvote. Please try again.');
        } finally {
            setUpvoting(false);
        }
    };

    const handleJoinCollaboration = async () => {
        try {
            await router.post(`/ideas/${idea.id}/join-collaboration`);
            toast.success('Successfully joined as collaborator!');
            // Update local state
            setIsCollaborator(true);
            setStats(prev => ({
                ...prev,
                total_collaborators: prev.total_collaborators + 1
            }));
            setIdea(prev => ({
                ...prev,
                collaborators: [...prev.collaborators, {
                    id: auth.user!.id,
                    name: auth.user!.name || 'Unknown User',
                    email: auth.user!.email,
                    pivot: {
                        joined_at: new Date().toISOString(),
                        contribution_points: 0
                    }
                }]
            }));
        } catch (error) {
            console.error('Failed to join collaboration:', error);
            toast.error('Failed to join collaboration. Please try again.');
        }
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

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Ideas',
            href: '/ideas',
        },
        {
            title: 'Idea Details',
            href: `/ideas/${idea.id}`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Idea Details" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl mt-16 md:mt-12 p-4">
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
                        {idea.status === 'draft' && idea.author.id === auth?.user?.id && (
                            <Button
                                variant="default"
                                size="sm"
                                onClick={() => router.post(`/ideas/${idea.id}/submit`, {}, {
                                    onSuccess: () => {
                                        toast.success('Idea submitted for review!');
                                    },
                                    onError: () => {
                                        toast.error('Failed to submit idea. Please try again.');
                                    },
                                })}
                            >
                                <Send className="w-4 h-4 mr-2" />
                                Submit for Review
                            </Button>
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
                                                            {collaborator.name?.charAt(0)?.toUpperCase() || '?'}
                                                        </AvatarFallback>
                                                    </Avatar>
                                                    <div>
                                                        <p className="text-sm font-medium">{collaborator.name || 'Unknown User'}</p>
                                                        <p className="text-xs text-gray-500">
                                                            Joined {new Date(collaborator.pivot?.joined_at || Date.now()).toLocaleDateString()}
                                                        </p>
                                                    </div>
                                                </div>
                                                <Badge variant="secondary">
                                                    {collaborator.pivot?.contribution_points || 0} pts
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
        </ AppLayout>
    );
}