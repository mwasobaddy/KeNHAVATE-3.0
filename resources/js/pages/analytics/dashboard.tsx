import { Head } from '@inertiajs/react';
import {
    TrendingUp,
    Target,
    Award,
    RefreshCw,
    BarChart3,
    Users,
    Lightbulb,
    ThumbsUp
} from 'lucide-react';
import React, { useState, useEffect } from 'react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Progress } from '@/components/ui/progress';

interface AnalyticsData {
    metrics: {
        total_points_earned: number;
        total_ideas_created: number;
        total_suggestions: number;
        avg_engagement_score: number;
        total_logins: number;
        total_upvotes_given: number;
        total_collaborations: number;
        total_time_spent: number;
        total_upvotes_received: number;
    };
    user: {
        rank: number;
        achievements: unknown[];
    };
    insights: string[];
}

export default function Dashboard() {
    const [timeRange, setTimeRange] = useState('30');
    const [analyticsData, setAnalyticsData] = useState<AnalyticsData | null>(null);
    const [loading, setLoading] = useState(true);

    // eslint-disable-next-line react-hooks/exhaustive-deps
    const loadAnalyticsData = async () => {
        setLoading(true);
        try {
            const response = await fetch(`/analytics/dashboard?days=${timeRange}`);
            const data = await response.json();
            setAnalyticsData(data);
        } catch (error) {
            console.error('Failed to load analytics data:', error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadAnalyticsData();
    }, [loadAnalyticsData, timeRange]);
    
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Analytics Dashboard',
            href: 'analytics/dashboard',
        },
    ];

    if (loading) {
        return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Analytics Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl mt-16 md:mt-8 p-4">
                <RefreshCw className="h-8 w-8 animate-spin" />
            </div>
        </AppLayout>
        );
    }

    const data = analyticsData;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Analytics Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl mt-16 md:mt-8 p-4">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">My Analytics</h1>
                        <p className="text-muted-foreground">
                            Track your progress and engagement on the platform
                        </p>
                    </div>
                    <Select value={timeRange} onValueChange={setTimeRange}>
                        <SelectTrigger className="w-32">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="7">7 days</SelectItem>
                            <SelectItem value="30">30 days</SelectItem>
                            <SelectItem value="90">90 days</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                {/* Key Metrics */}
                {data && (
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Total Points</CardTitle>
                                <Award className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{data.metrics.total_points_earned}</div>
                                <p className="text-xs text-muted-foreground">
                                    Earned this period
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Ideas Created</CardTitle>
                                <Lightbulb className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{data.metrics.total_ideas_created}</div>
                                <p className="text-xs text-muted-foreground">
                                    Your contributions
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Suggestions Made</CardTitle>
                                <Target className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{data.metrics.total_suggestions}</div>
                                <p className="text-xs text-muted-foreground">
                                    Collaborative input
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Avg Engagement</CardTitle>
                                <TrendingUp className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{data.metrics.avg_engagement_score}</div>
                                <p className="text-xs text-muted-foreground">
                                    Your activity score
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                )}

                {/* Detailed Analytics */}
                <div className="grid gap-6 md:grid-cols-2">
                    {/* Activity Breakdown */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Activity Breakdown</CardTitle>
                            <CardDescription>Your engagement metrics for this period</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <div className="flex items-center justify-between text-sm">
                                    <span>Logins</span>
                                    <span className="font-medium">{data?.metrics.total_logins || 0}</span>
                                </div>
                                <Progress value={(data?.metrics.total_logins || 0) / Math.max(data?.metrics.total_logins || 1, 10) * 100} className="h-2" />
                            </div>

                            <div className="space-y-2">
                                <div className="flex items-center justify-between text-sm">
                                    <span>Upvotes Given</span>
                                    <span className="font-medium">{data?.metrics.total_upvotes_given || 0}</span>
                                </div>
                                <Progress value={(data?.metrics.total_upvotes_given || 0) / Math.max(data?.metrics.total_upvotes_given || 1, 20) * 100} className="h-2" />
                            </div>

                            <div className="space-y-2">
                                <div className="flex items-center justify-between text-sm">
                                    <span>Collaborations Joined</span>
                                    <span className="font-medium">{data?.metrics.total_collaborations || 0}</span>
                                </div>
                                <Progress value={(data?.metrics.total_collaborations || 0) / Math.max(data?.metrics.total_collaborations || 1, 5) * 100} className="h-2" />
                            </div>

                            <div className="space-y-2">
                                <div className="flex items-center justify-between text-sm">
                                    <span>Time Spent (minutes)</span>
                                    <span className="font-medium">{data?.metrics.total_time_spent || 0}</span>
                                </div>
                                <Progress value={(data?.metrics.total_time_spent || 0) / Math.max(data?.metrics.total_time_spent || 1, 300) * 100} className="h-2" />
                            </div>
                        </CardContent>
                    </Card>

                    {/* Social Impact */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Social Impact</CardTitle>
                            <CardDescription>How your contributions affect the community</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center space-x-2">
                                    <ThumbsUp className="h-4 w-4 text-green-500" />
                                    <span className="text-sm">Upvotes Received</span>
                                </div>
                                <Badge variant="secondary">{data?.metrics.total_upvotes_received || 0}</Badge>
                            </div>

                            <div className="flex items-center justify-between">
                                <div className="flex items-center space-x-2">
                                    <Users className="h-4 w-4 text-blue-500" />
                                    <span className="text-sm">Rank</span>
                                </div>
                                <Badge variant="outline">#{data?.user?.rank || 'N/A'}</Badge>
                            </div>

                            <div className="flex items-center justify-between">
                                <div className="flex items-center space-x-2">
                                    <Award className="h-4 w-4 text-yellow-500" />
                                    <span className="text-sm">Achievements</span>
                                </div>
                                <Badge variant="secondary">{data?.user?.achievements?.length || 0}</Badge>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Insights */}
                {data?.insights && data.insights.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Personal Insights</CardTitle>
                            <CardDescription>Tailored recommendations to improve your engagement</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                {data.insights.map((insight: string | number | bigint | boolean | React.ReactElement<unknown, string | React.JSXElementConstructor<unknown>> | Iterable<React.ReactNode> | React.ReactPortal | Promise<string | number | bigint | boolean | React.ReactPortal | React.ReactElement<unknown, string | React.JSXElementConstructor<unknown>> | Iterable<React.ReactNode> | null | undefined> | null | undefined, index: React.Key | null | undefined) => (
                                    <div key={index} className="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                        <p className="text-sm text-blue-800">{insight}</p>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Activity Timeline Placeholder */}
                <Card>
                    <CardHeader>
                        <CardTitle>Activity Timeline</CardTitle>
                        <CardDescription>Your recent activity and contributions</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="text-center text-muted-foreground py-8">
                            <BarChart3 className="h-12 w-12 mx-auto mb-4" />
                            <p>Interactive timeline visualization</p>
                            <p className="text-sm">Showing your daily activity patterns and achievements</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}