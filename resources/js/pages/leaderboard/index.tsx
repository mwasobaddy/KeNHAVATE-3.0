import { Head } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import { Trophy, Star, Flame, Users } from 'lucide-react';
import React, { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types/navigation';

interface LeaderboardEntry {
    rank: number;
    user: {
        id: number;
        name: string;
    };
    designation: string;
    department: string;
    total_points: number;
}

interface Props {
    leaderboard: LeaderboardEntry[];
    period: string;
    totalCount: number;
}

export default function Index({ leaderboard, period, totalCount }: Props) {
    const [selectedPeriod, setSelectedPeriod] = useState<string>(period || 'all');
    const [loading, setLoading] = useState(false);

    const periods = [
        { value: 'all', label: 'All Time', icon: Trophy },
        { value: 'year', label: 'This Year', icon: Star },
        { value: 'month', label: 'This Month', icon: Flame },
        { value: 'week', label: 'This Week', icon: Users },
    ];

    const handlePeriodChange = (period: string) => {
        setSelectedPeriod(period);
        setLoading(true);
        router.get('leaderboard', { period }, {
            preserveState: true,
            onFinish: () => setLoading(false),
        });
    };

    const getRankIcon = (rank: number) => {
        switch (rank) {
            case 1:
                return '🥇';
            case 2:
                return '🥈';
            case 3:
                return '🥉';
            default:
                return `#${rank}`;
        }
    };

    const getRankColor = (rank: number) => {
        switch (rank) {
            case 1:
                return 'text-yellow-600 bg-yellow-50';
            case 2:
                return 'text-gray-600 bg-gray-50';
            case 3:
                return 'text-orange-600 bg-orange-50';
            default:
                return 'text-gray-600 bg-gray-50';
        }
    };
    
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Leaderboard',
            href: 'leaderboard',
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Leaderboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Period Selector */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6">
                            <div className="flex flex-wrap gap-2">
                                {periods.map((period) => {
                                    const Icon = period.icon;
                                    return (
                                        <button
                                            key={period.value}
                                            onClick={() => handlePeriodChange(period.value)}
                                            disabled={loading}
                                            className={`flex items-center px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
                                                selectedPeriod === period.value
                                                    ? 'bg-blue-100 text-blue-700 border-2 border-blue-300'
                                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                            } ${loading ? 'opacity-50 cursor-not-allowed' : ''}`}
                                        >
                                            <Icon className="w-4 h-4 mr-2" />
                                            {period.label}
                                        </button>
                                    );
                                })}
                            </div>
                        </div>
                    </div>

                    {/* Leaderboard */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="flex items-center justify-between mb-6">
                                <h3 className="text-lg font-semibold text-gray-900">
                                    Top Contributors
                                </h3>
                                <span className="text-sm text-gray-500">
                                    {totalCount} participants
                                </span>
                            </div>

                            {loading ? (
                                <div className="space-y-4">
                                    {[...Array(10)].map((_, i) => (
                                        <div key={i} className="animate-pulse">
                                            <div className="h-16 bg-gray-200 rounded"></div>
                                        </div>
                                    ))}
                                </div>
                            ) : leaderboard.length === 0 ? (
                                <div className="text-center py-12">
                                    <Trophy className="mx-auto h-12 w-12 text-gray-400" />
                                    <h3 className="mt-2 text-sm font-medium text-gray-900">No participants yet</h3>
                                    <p className="mt-1 text-sm text-gray-500">
                                        Start contributing to see the leaderboard!
                                    </p>
                                </div>
                            ) : (
                                <div className="space-y-2">
                                    {leaderboard.map((entry) => (
                                        <div
                                            key={entry.user.id}
                                            className={`flex items-center justify-between p-4 rounded-lg border ${
                                                entry.rank <= 3
                                                    ? 'bg-linear-to-r from-yellow-50 to-orange-50 border-yellow-200'
                                                    : 'bg-white border-gray-200'
                                            }`}
                                        >
                                            <div className="flex items-center space-x-4">
                                                <div className={`flex items-center justify-center w-10 h-10 rounded-full font-bold text-lg ${
                                                    getRankColor(entry.rank)
                                                }`}>
                                                    {getRankIcon(entry.rank)}
                                                </div>

                                                <div className="shrink-0">
                                                    <div className="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
                                                        <span className="text-sm font-medium text-gray-700">
                                                            {entry.user.name.charAt(0).toUpperCase()}
                                                        </span>
                                                    </div>
                                                </div>

                                                <div>
                                                    <h4 className="text-sm font-medium text-gray-900">
                                                        {entry.user.name}
                                                    </h4>
                                                    <p className="text-sm text-gray-500">
                                                        {entry.designation} • {entry.department}
                                                    </p>
                                                </div>
                                            </div>

                                            <div className="text-right">
                                                <div className="text-2xl font-bold text-blue-600">
                                                    {entry.total_points.toLocaleString()}
                                                </div>
                                                <div className="text-sm text-gray-500">
                                                    points
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>

                    {/* How Points Work */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                        <div className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                How Points Work
                            </h3>
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div className="flex items-start space-x-3">
                                    <div className="shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                        <span className="text-sm font-medium text-blue-600">25</span>
                                    </div>
                                    <div>
                                        <h4 className="text-sm font-medium text-gray-900">Create Idea</h4>
                                        <p className="text-sm text-gray-500">Submit a new idea for review</p>
                                    </div>
                                </div>

                                <div className="flex items-start space-x-3">
                                    <div className="shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                        <span className="text-sm font-medium text-green-600">50</span>
                                    </div>
                                    <div>
                                        <h4 className="text-sm font-medium text-gray-900">Submit Idea</h4>
                                        <p className="text-sm text-gray-500">Move idea from draft to review</p>
                                    </div>
                                </div>

                                <div className="flex items-start space-x-3">
                                    <div className="shrink-0 w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                        <span className="text-sm font-medium text-purple-600">10</span>
                                    </div>
                                    <div>
                                        <h4 className="text-sm font-medium text-gray-900">Make Suggestion</h4>
                                        <p className="text-sm text-gray-500">Contribute to someone else's idea</p>
                                    </div>
                                </div>

                                <div className="flex items-start space-x-3">
                                    <div className="shrink-0 w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                        <span className="text-sm font-medium text-yellow-600">25</span>
                                    </div>
                                    <div>
                                        <h4 className="text-sm font-medium text-gray-900">Suggestion Accepted</h4>
                                        <p className="text-sm text-gray-500">Your suggestion gets implemented</p>
                                    </div>
                                </div>

                                <div className="flex items-start space-x-3">
                                    <div className="shrink-0 w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                                        <span className="text-sm font-medium text-red-600">5</span>
                                    </div>
                                    <div>
                                        <h4 className="text-sm font-medium text-gray-900">Receive Upvote</h4>
                                        <p className="text-sm text-gray-500">Someone upvotes your idea</p>
                                    </div>
                                </div>

                                <div className="flex items-start space-x-3">
                                    <div className="shrink-0 w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center">
                                        <span className="text-sm font-medium text-indigo-600">15</span>
                                    </div>
                                    <div>
                                        <h4 className="text-sm font-medium text-gray-900">Join Collaboration</h4>
                                        <p className="text-sm text-gray-500">Become a collaborator on an idea</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}