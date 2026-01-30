import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { store } from '@/routes/staff/onboarding';
import type { BreadcrumbItem } from '@/types';


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Onboarding',
        href: 'onboarding/staff',
    },
];

export default function StaffOnboarding() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Onboarding" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="work_email">Work Email</Label>
                                <Input
                                    id="work_email"
                                    type="email"
                                    name="work_email"
                                    required
                                    autoFocus
                                    placeholder="staff@kenha.co.ke"
                                />
                                <InputError message={errors.work_email} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="personal_email">Personal Email (Optional)</Label>
                                <Input
                                    id="personal_email"
                                    type="email"
                                    name="personal_email"
                                    placeholder="personal@example.com"
                                />
                                <InputError message={errors.personal_email} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="designation">Designation</Label>
                                <Input
                                    id="designation"
                                    type="text"
                                    name="designation"
                                    placeholder="Engineer"
                                />
                                <InputError message={errors.designation} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="employment_type">Employment Type</Label>
                                <Input
                                    id="employment_type"
                                    type="text"
                                    name="employment_type"
                                    placeholder="Permanent"
                                />
                                <InputError message={errors.employment_type} />
                            </div>
                        </div>

                        <Button type="submit" className="w-full" disabled={processing}>
                            Complete Onboarding
                        </Button>
                    </>
                )}
            </Form>
        </div>
        </AppLayout>
    );
}