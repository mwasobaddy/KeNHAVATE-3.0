import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { login as googleLogin } from '@/routes/google';
import { send } from '@/routes/login';

export default function Login() {
    return (
        <AuthLayout
            title="Sign in to your account"
            description="Enter your email to receive an OTP or sign in with Google"
        >
            <Head title="Sign In" />

            <div className="space-y-6">
                <Form
                    {...send.form()}
                    resetOnSuccess={['password']}
                    className="flex flex-col gap-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-6">
                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email address</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        name="email"
                                        required
                                        autoFocus
                                        autoComplete="email"
                                        placeholder="email@example.com"
                                    />
                                    <InputError message={errors.email} />
                                </div>
                            </div>

                            <Button type="submit" className="w-full" disabled={processing}>
                                Send OTP
                            </Button>
                        </>
                    )}
                </Form>

                <div className="relative">
                    <div className="absolute inset-0 flex items-center">
                        <span className="w-full border-t" />
                    </div>
                    <div className="relative flex justify-center text-xs uppercase">
                        <span className="bg-background px-2 text-muted-foreground">
                            Or continue with
                        </span>
                    </div>
                </div>

                <a href={googleLogin.url()} className="w-full block">
                    <Button variant="outline" className="w-full">
                        Sign in with Google
                    </Button>
                </a>
            </div>
        </AuthLayout>
    );
}
