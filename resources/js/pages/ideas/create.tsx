import { router } from '@inertiajs/react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import React from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types/navigation';
import { Textarea } from '@/components/ui/textarea';

interface Category {
    id: number;
    name: string;
}

interface CreateIdeaProps {
    categories: Category[];
}

export default function Create({ categories }: CreateIdeaProps) {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        description: '',
        category_id: '',
        problem_statement: '',
        proposed_solution: '',
        cost_benefit_analysis: '',
        proposal_document_path: '',
        collaboration_enabled: false,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/ideas', {
            onSuccess: () => {
                router.visit('/ideas');
            },
        });
    };
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Ideas',
            href: '/ideas',
        },
        {
            title: 'Submit New Idea',
            href: '/ideas/create',
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Submit New Idea" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl mt-16 md:mt-12 p-4">
                <div className="flex justify-between items-center mb-8">
                    <div>
                        <h1 className="text-3xl font-bold">Submit New Idea</h1>
                        <p className="text-muted-foreground mt-2">Share your innovative idea and get feedback from the community</p>
                    </div>
                    <Link href="/ideas">
                        <Button variant="default" size="sm" className="mr-4">
                            <ArrowLeft className="w-4 h-4 mr-2" />
                            Back to Ideas
                        </Button>
                    </Link>
                </div>

                <form onSubmit={handleSubmit}>
                    <div className="space-y-8">
                        {/* Basic Information */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Basic Information</CardTitle>
                                <CardDescription>
                                    Provide the essential details about your idea
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <Label htmlFor="title">Title *</Label>
                                    <Input
                                        id="title"
                                        value={data.title}
                                        onChange={(e: { target: { value: string; }; }) => setData('title', e.target.value)}
                                        placeholder="Enter a clear, concise title for your idea"
                                        className={errors.title ? 'border-red-500' : ''}
                                    />
                                    {errors.title && (
                                        <p className="text-sm text-red-600 mt-1">{errors.title}</p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="description">Description *</Label>
                                    <Textarea
                                        id="description"
                                        value={data.description}
                                        onChange={(e: { target: { value: string; }; }) => setData('description', e.target.value)}
                                        placeholder="Provide a detailed description of your idea"
                                        rows={4}
                                        className={errors.description ? 'border-red-500' : ''}
                                    />
                                    {errors.description && (
                                        <p className="text-sm text-red-600 mt-1">{errors.description}</p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="category_id">Category *</Label>
                                    <Select
                                        value={data.category_id}
                                        onValueChange={(value: string) => setData('category_id', value)}
                                    >
                                        <SelectTrigger className={errors.category_id ? 'border-red-500' : ''}>
                                            <SelectValue placeholder="Select a category" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {categories.map((category) => (
                                                <SelectItem key={category.id} value={category.id.toString()}>
                                                    {category.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.category_id && (
                                        <p className="text-sm text-red-600 mt-1">{errors.category_id}</p>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Problem & Solution */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Problem & Solution</CardTitle>
                                <CardDescription>
                                    Clearly define the problem and your proposed solution
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <Label htmlFor="problem_statement">Problem Statement *</Label>
                                    <Textarea
                                        id="problem_statement"
                                        value={data.problem_statement}
                                        onChange={(e: { target: { value: string; }; }) => setData('problem_statement', e.target.value)}
                                        placeholder="What problem does your idea address? Be specific about the current challenges."
                                        rows={6}
                                        className={errors.problem_statement ? 'border-red-500' : ''}
                                    />
                                    {errors.problem_statement && (
                                        <p className="text-sm text-red-600 mt-1">{errors.problem_statement}</p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="proposed_solution">Proposed Solution *</Label>
                                    <Textarea
                                        id="proposed_solution"
                                        value={data.proposed_solution}
                                        onChange={(e: { target: { value: string; }; }) => setData('proposed_solution', e.target.value)}
                                        placeholder="Describe your proposed solution in detail. How does it solve the problem?"
                                        rows={8}
                                        className={errors.proposed_solution ? 'border-red-500' : ''}
                                    />
                                    {errors.proposed_solution && (
                                        <p className="text-sm text-red-600 mt-1">{errors.proposed_solution}</p>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Additional Information */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Additional Information</CardTitle>
                                <CardDescription>
                                    Optional details to strengthen your idea
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <Label htmlFor="cost_benefit_analysis">Cost-Benefit Analysis</Label>
                                    <Textarea
                                        id="cost_benefit_analysis"
                                        value={data.cost_benefit_analysis}
                                        onChange={(e: { target: { value: string; }; }) => setData('cost_benefit_analysis', e.target.value)}
                                        placeholder="Analyze the costs and benefits of implementing your solution"
                                        rows={4}
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="proposal_document_path">Proposal Document</Label>
                                    <Input
                                        id="proposal_document_path"
                                        value={data.proposal_document_path}
                                        onChange={(e: { target: { value: string; }; }) => setData('proposal_document_path', e.target.value)}
                                        placeholder="URL to detailed proposal document (optional)"
                                    />
                                    <p className="text-sm text-gray-500 mt-1">
                                        Link to a Google Doc, PDF, or other document with additional details
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Collaboration Settings */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Collaboration Settings</CardTitle>
                                <CardDescription>
                                    Choose whether to enable community collaboration on your idea
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="collaboration_enabled"
                                        checked={data.collaboration_enabled}
                                        onCheckedChange={(checked) =>
                                            setData('collaboration_enabled', checked as boolean)
                                        }
                                    />
                                    <Label htmlFor="collaboration_enabled" className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                                        Enable collaboration on this idea
                                    </Label>
                                </div>
                                <p className="text-sm text-gray-500 mt-2">
                                    When enabled, other users can join as collaborators, submit suggestions, and participate in discussions to help improve your idea.
                                </p>
                            </CardContent>
                        </Card>

                        {/* Submit Actions */}
                        <div className="flex justify-end space-x-4">
                            <Link href="/ideas">
                                <Button type="button" variant="outline">
                                    Cancel
                                </Button>
                            </Link>
                            <Button type="submit" disabled={processing}>
                                <Save className="w-4 h-4 mr-2" />
                                {processing ? 'Submitting...' : 'Submit Idea'}
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </ AppLayout>
    );
}