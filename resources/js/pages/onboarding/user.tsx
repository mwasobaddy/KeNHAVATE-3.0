import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { store } from '@/routes/user/onboarding';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Onboarding',
        href: 'onboarding/user',
    },
];

export default function UserOnboarding() {
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
                                    <Label htmlFor="name">Full Name</Label>
                                    <Input
                                        id="name"
                                        type="text"
                                        name="name"
                                        required
                                        autoFocus
                                        placeholder="e.g. Eng. Kelvin Mwangi"
                                    />
                                    <InputError message={errors.name} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="mobile">Mobile Number</Label>
                                    <Input
                                        id="mobile"
                                        type="text"
                                        name="mobile"
                                        required
                                        placeholder="+254 700 000 000"
                                    />
                                    <InputError message={errors.mobile} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="id_number">ID Number</Label>
                                    <Input
                                        id="id_number"
                                        type="text"
                                        name="id_number"
                                        required
                                        placeholder="12345678"
                                    />
                                    <InputError message={errors.id_number} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="gender">Gender</Label>
                                    <Select name="gender" required>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select gender" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="male">Male</SelectItem>
                                            <SelectItem value="female">Female</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.gender} />
                                </div>
                            </div>

                            <Button type="submit" className="w-full" disabled={processing}>
                                Complete Profile
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}