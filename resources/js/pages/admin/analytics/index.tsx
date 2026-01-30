import React, { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import {
    BarChart3,
    TrendingUp,
    Users,
    Lightbulb,
    Target,
    Download,
    RefreshCw,
    Calendar,
    Activity
} from 'lucide-react';

export default function Index() {
    const [activeTab, setActiveTab] = useState('overview');
    const [timeRange, setTimeRange] = useState('30');
    const [systemData, setSystemData] = useState(null);
    const [ideaData, setIdeaData] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        loadAnalyticsData();
    }, [timeRange]);

    const loadAnalyticsData = async () => {
        setLoading(true);
        try {
            const [systemResponse, ideaResponse] = await Promise.all([
                fetch(`/admin/analytics/system?days=${timeRange}`),
                fetch(`/admin/analytics/ideas?days=${timeRange}`)
            ]);

            const systemResult = await systemResponse.json();
            const ideaResult = await ideaResponse.json();

            setSystemData(systemResult);
            setIdeaData(ideaResult);
        } catch (error) {
            console.error('Failed to load analytics data:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleExport = async (format) => {
        try {
            const response = await fetch(`/admin/analytics/export?type=system_overview&format=${format}&days=${timeRange}`);
            const data = await response.json();

            // Create and download file
            const blob = new Blob([format === 'csv' ? data.data : JSON.stringify(data.data, null, 2)], {
                type: format === 'csv' ? 'text/csv' : 'application/json'
            });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `analytics-export-${new Date().toISOString().split('T')[0]}.${format}`;
            a.click();
            window.URL.revokeObjectURL(url);
        } catch (error) {
            console.error('Export failed:', error);
        }
    };

    if (loading) {
        return (
            <AuthenticatedLayout>
                <div className="flex items-center justify-center min-h-screen">
                    <RefreshCw className="h-8 w-8 animate-spin" />
                </div>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout>
            <Head title="Analytics Dashboard" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Analytics Dashboard</h1>
                        <p className="text-muted-foreground">
                            Comprehensive insights into platform performance and user engagement
                        </p>
                    </div>
                    <div className="flex items-center space-x-2">
                        <Select value={timeRange} onValueChange={setTimeRange}>
                            <SelectTrigger className="w-32">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="7">7 days</SelectItem>
                                <SelectItem value="30">30 days</SelectItem>
                                <SelectItem value="90">90 days</SelectItem>
                                <SelectItem value="365">1 year</SelectItem>
                            </SelectContent>
                        </Select>
                        <Button variant="outline" onClick={() => handleExport('csv')}>
                            <Download className="h-4 w-4 mr-2" />
                            Export CSV
                        </Button>
                        <Button variant="outline" onClick={() => handleExport('json')}>
                            <Download className="h-4 w-4 mr-2" />
                            Export JSON
                        </Button>
                    </div>
                </div>

                {/* Key Metrics Cards */}
                {systemData && (
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Total Users</CardTitle>
                                <Users className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{systemData.overview.total_users}</div>
                                <p className="text-xs text-muted-foreground">
                                    {systemData.overview.active_users} active in period
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Engagement Rate</CardTitle>
                                <Activity className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{systemData.overview.engagement_rate}%</div>
                                <p className="text-xs text-muted-foreground">
                                    Active users / total users
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Total Ideas</CardTitle>
                                <Lightbulb className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{systemData.overview.total_ideas}</div>
                                <p className="text-xs text-muted-foreground">
                                    Created in period
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Avg Engagement</CardTitle>
                                <TrendingUp className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{systemData.metrics.average_engagement_score}</div>
                                <p className="text-xs text-muted-foreground">
                                    Points earned average
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                )}

                {/* Main Analytics Tabs */}
                <Tabs value={activeTab} onValueChange={setActiveTab}>
                    <TabsList className="grid w-full grid-cols-4">
                        <TabsTrigger value="overview">Overview</TabsTrigger>
                        <TabsTrigger value="users">User Analytics</TabsTrigger>
                        <TabsTrigger value="ideas">Idea Lifecycle</TabsTrigger>
                        <TabsTrigger value="insights">Insights</TabsTrigger>
                    </TabsList>

                    <TabsContent value="overview" className="space-y-4">
                        {systemData && (
                            <div className="grid gap-4 md:grid-cols-2">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Activity Overview</CardTitle>
                                        <CardDescription>Key metrics for the selected period</CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm">Total Logins</span>
                                            <Badge variant="secondary">{systemData.metrics.total_logins}</Badge>
                                        </div>
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm">Ideas Created</span>
                                            <Badge variant="secondary">{systemData.metrics.total_ideas_created}</Badge>
                                        </div>
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm">Suggestions Submitted</span>
                                            <Badge variant="secondary">{systemData.metrics.total_suggestions}</Badge>
                                        </div>
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm">Points Awarded</span>
                                            <Badge variant="secondary">{systemData.metrics.total_points_awarded}</Badge>
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader>
                                        <CardTitle>Top Contributors</CardTitle>
                                        <CardDescription>Highest scoring users this period</CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="space-y-2">
                                            {systemData.top_contributors.slice(0, 5).map((contributor, index) => (
                                                <div key={contributor.user.id} className="flex items-center justify-between">
                                                    <div className="flex items-center space-x-2">
                                                        <Badge variant="outline">{index + 1}</Badge>
                                                        <span className="text-sm">{contributor.user.name}</span>
                                                    </div>
                                                    <Badge>{contributor.total_points} pts</Badge>
                                                </div>
                                            ))}
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        )}
                    </TabsContent>

                    <TabsContent value="users" className="space-y-4">
                        {systemData && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>User Engagement Trends</CardTitle>
                                    <CardDescription>Daily engagement metrics over time</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-center text-muted-foreground">
                                        <BarChart3 className="h-12 w-12 mx-auto mb-4" />
                                        <p>Interactive charts will be implemented here</p>
                                        <p className="text-sm">Showing trends for logins, ideas, and points over time</p>
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </TabsContent>

                    <TabsContent value="ideas" className="space-y-4">
                        {ideaData && (
                            <div className="grid gap-4 md:grid-cols-2">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Idea Status Breakdown</CardTitle>
                                        <CardDescription>Distribution of ideas by current status</CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="space-y-2">
                                            {Object.entries(ideaData.status_breakdown).map(([status, count]) => (
                                                <div key={status} className="flex items-center justify-between">
                                                    <span className="text-sm capitalize">{status}</span>
                                                    <Badge variant="secondary">{count}</Badge>
                                                </div>
                                            ))}
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader>
                                        <CardTitle>Performance Metrics</CardTitle>
                                        <CardDescription>Average metrics across all ideas</CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm">Avg Time to Collaboration</span>
                                            <Badge variant="secondary">
                                                {ideaData.performance_metrics.avg_time_to_first_collaboration || 'N/A'}h
                                            </Badge>
                                        </div>
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm">Avg Collaboration Rate</span>
                                            <Badge variant="secondary">
                                                {ideaData.performance_metrics.avg_collaboration_rate}%
                                            </Badge>
                                        </div>
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm">Avg Acceptance Rate</span>
                                            <Badge variant="secondary">
                                                {ideaData.performance_metrics.avg_acceptance_rate}%
                                            </Badge>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        )}
                    </TabsContent>

                    <TabsContent value="insights" className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>Platform Insights</CardTitle>
                                <CardDescription>AI-generated insights and recommendations</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4">
                                    <div className="p-4 border rounded-lg">
                                        <div className="flex items-start space-x-3">
                                            <Target className="h-5 w-5 text-blue-500 mt-0.5" />
                                            <div>
                                                <h4 className="font-medium">Engagement Analysis</h4>
                                                <p className="text-sm text-muted-foreground">
                                                    {systemData?.overview?.engagement_rate > 70
                                                        ? "Excellent user engagement! The gamification system is working well."
                                                        : "Consider implementing more interactive features to boost engagement."
                                                    }
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="p-4 border rounded-lg">
                                        <div className="flex items-start space-x-3">
                                            <TrendingUp className="h-5 w-5 text-green-500 mt-0.5" />
                                            <div>
                                                <h4 className="font-medium">Growth Opportunities</h4>
                                                <p className="text-sm text-muted-foreground">
                                                    {ideaData?.overview?.ideas_with_collaboration > ideaData?.overview?.total_ideas * 0.5
                                                        ? "Great collaboration rate! Users are actively working together."
                                                        : "Encourage more collaboration by highlighting successful team efforts."
                                                    }
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </AuthenticatedLayout>
    );
}