import MobileShell from '@/Layouts/MobileShell';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <MobileShell>
            <Head title="Staff sign-in" />

            <div className="flex animate-screenin flex-col flex-1">
                <div className="flex items-center gap-2.5 p-3.5">
                    <Link
                        href="/"
                        className="flex h-9 w-9 flex-none items-center justify-center rounded-[10px] border border-border-soft bg-white text-lg leading-none text-primary"
                    >
                        &lsaquo;
                    </Link>
                    <div className="text-[15px] font-bold text-ink">
                        Staff sign-in
                    </div>
                </div>

                <div className="mx-[18px] mt-2 rounded-[20px] border border-border bg-white px-5 py-[22px]">
                    <div className="text-lg font-extrabold text-ink">
                        Welcome back
                    </div>
                    <div className="mb-[18px] mt-1 text-[12.5px] text-muted">
                        For lecturers, the Exam Officer and the HoD only.
                    </div>

                    {status && (
                        <div className="mb-4 rounded-[10px] bg-success-bg px-3 py-2.5 text-sm font-medium text-success">
                            {status}
                        </div>
                    )}

                    <form onSubmit={submit}>
                        <label
                            htmlFor="email"
                            className="mb-1.5 block text-[11.5px] font-semibold uppercase tracking-[.4px] text-body"
                        >
                            Staff email
                        </label>
                        <input
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            autoComplete="username"
                            autoFocus
                            placeholder="you@unitystate.edu.ng"
                            className="block w-full rounded-xl border-[1.5px] border-border-input bg-input-bg px-3.5 text-[15px] text-ink"
                            style={{ height: 48 }}
                        />
                        {errors.email && (
                            <div className="mt-1.5 text-sm text-error">
                                {errors.email}
                            </div>
                        )}

                        <label
                            htmlFor="password"
                            className="mb-1.5 mt-3.5 block text-[11.5px] font-semibold uppercase tracking-[.4px] text-body"
                        >
                            Password
                        </label>
                        <input
                            id="password"
                            type="password"
                            value={data.password}
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
                            autoComplete="current-password"
                            placeholder="********"
                            className="block w-full rounded-xl border-[1.5px] border-border-input bg-input-bg px-3.5 text-[15px] text-ink"
                            style={{ height: 48 }}
                        />
                        {errors.password && (
                            <div className="mt-1.5 text-sm text-error">
                                {errors.password}
                            </div>
                        )}

                        <button
                            type="submit"
                            disabled={processing}
                            className="mt-[18px] h-[50px] w-full rounded-[13px] bg-primary text-[15px] font-bold text-white hover:bg-primary-hover disabled:opacity-70"
                        >
                            Sign in
                        </button>
                    </form>

                    {canResetPassword && (
                        <div className="mt-3 text-center">
                            <Link
                                href={route('password.request')}
                                className="text-[12px] font-medium text-muted underline"
                            >
                                Forgot your password?
                            </Link>
                        </div>
                    )}
                </div>
            </div>
        </MobileShell>
    );
}
