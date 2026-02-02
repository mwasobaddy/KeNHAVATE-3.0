import { Form, Head } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import toast from 'react-hot-toast';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { verify, resend } from '@/routes/otp';

type Props = {
    email: string;
    remainingSeconds?: number;
};

export default function OtpVerify({ email, remainingSeconds = 60 }: Props) {
    const [countdown, setCountdown] = useState(remainingSeconds);
    const [canResend, setCanResend] = useState(false);
    const [resendLoading, setResendLoading] = useState(false);
    const [isRateLimited, setIsRateLimited] = useState(false);

    useEffect(() => {
        setCountdown(remainingSeconds);
        setCanResend(remainingSeconds === 0);
    }, [remainingSeconds]);

    useEffect(() => {
        if (countdown > 0) {
            const timer = setTimeout(() => setCountdown(countdown - 1), 1000);
            return () => clearTimeout(timer);
        } else {
            setCanResend(true);
        }
    }, [countdown]);

    // Reset rate limited state when resending OTP
    useEffect(() => {
        if (resendLoading) {
            setIsRateLimited(false);
        }
    }, [resendLoading]);

    const handleResend = async () => {
        setResendLoading(true);
        try {
            const response = await fetch(resend.url(), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ email }),
            });

            if (response.ok) {
                setCountdown(60);
                setCanResend(false);
                toast.success('New OTP sent to your email!');
            } else {
                toast.error('Failed to resend OTP. Please try again.');
            }
        } catch (error) {
            console.error('Failed to resend OTP:', error);
            toast.error('Failed to resend OTP. Please check your connection and try again.');
        } finally {
            setResendLoading(false);
        }
    };

    return (
        <AuthLayout
            title="Verify OTP"
            description={`Enter the 6-digit code sent to ${email}`}
        >
            <Head title="Verify OTP" />

            <Form
                {...verify.form()}
                onError={(errors) => {
                    if (errors.otp && errors.otp.includes('Too many failed verification attempts')) {
                        setIsRateLimited(true);
                        toast.error('Too many failed attempts. Please request a new OTP.');
                    } else if (errors.otp) {
                        toast.error('Invalid OTP code. Please try again.');
                    }
                }}
                onSuccess={() => {
                    setIsRateLimited(false);
                    toast.success('Successfully logged in!');
                }}
                resetOnSuccess={['password']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <input type="hidden" name="email" value={email} />
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="otp">OTP Code</Label>
                                <Input
                                    id="otp"
                                    type="text"
                                    name="otp"
                                    required
                                    autoFocus
                                    maxLength={6}
                                    placeholder="123456"
                                    disabled={isRateLimited}
                                />
                                <InputError message={errors.otp} />
                            </div>
                        </div>

                        <Button 
                            type="submit" 
                            className="w-full" 
                            disabled={processing || isRateLimited}
                        >
                            {isRateLimited ? 'Too Many Attempts - Request New OTP' : 'Verify OTP'}
                        </Button>

                        <div className="text-center">
                            <p className="text-sm text-muted-foreground mb-2">
                                {isRateLimited ? (
                                    'Too many failed attempts. Request a new OTP above.'
                                ) : canResend ? (
                                    'Didn\'t receive the code?'
                                ) : (
                                    `Resend code in ${countdown}s`
                                )}
                            </p>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={handleResend}
                                disabled={!canResend || resendLoading || isRateLimited}
                                className="w-full"
                            >
                                {resendLoading ? 'Sending...' : isRateLimited ? 'Request New OTP Above' : 'Resend OTP'}
                            </Button>
                        </div>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}