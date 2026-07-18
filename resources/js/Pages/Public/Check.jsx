import MobileShell from '@/Layouts/MobileShell';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Check({ bioDataHref }) {
    const { data, setData, post, processing, errors } = useForm({
        mat_no: '',
        pin: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('public.check.store'));
    };

    return (
        <MobileShell>
            <Head title="Check your result" />

            <div className="flex flex-1 flex-col animate-screenin">
                <div className="rounded-b-[28px] bg-navy px-6 pb-11 pt-8 text-white">
                    <div className="flex items-center gap-3.5">
                        <Link
                            href={route('landing')}
                            className="flex h-9 w-9 flex-none items-center justify-center rounded-[10px] border border-white/30 text-lg leading-none text-white"
                        >
                            &lsaquo;
                        </Link>
                        <div className="flex h-[52px] w-[52px] flex-none items-center justify-center rounded-full border-[1.5px] border-white/35 bg-white/[.12] text-[17px] font-extrabold tracking-[.5px]">
                            US
                        </div>
                        <div>
                            <div className="text-[17px] font-extrabold tracking-[.2px]">
                                Unity State University
                            </div>
                            <div className="mt-0.5 text-xs opacity-75">
                                Department of Computer Science · Result
                                Portal
                            </div>
                        </div>
                    </div>
                </div>

                <div className="mx-[18px] -mt-[26px] rounded-[20px] bg-white px-5 py-[22px] shadow-[0_8px_28px_rgba(18,32,66,.10)]">
                    <div className="text-lg font-bold text-ink">
                        Check your result
                    </div>
                    <div className="mb-4 mt-1 text-[12.5px] text-muted">
                        No sign-in needed — enter your matriculation number
                        and access PIN.
                    </div>

                    <form onSubmit={submit}>
                        <label className="mb-1.5 block text-[11.5px] font-semibold uppercase tracking-[.4px] text-body">
                            Matriculation number
                        </label>
                        <input
                            value={data.mat_no}
                            onChange={(e) =>
                                setData('mat_no', e.target.value)
                            }
                            placeholder="e.g. U2022/5570001"
                            className="h-12 w-full rounded-xl border-[1.5px] border-border-input bg-input-bg px-3.5 text-[15px] text-ink"
                        />

                        <label className="mb-1.5 mt-3.5 block text-[11.5px] font-semibold uppercase tracking-[.4px] text-body">
                            Access PIN
                        </label>
                        <input
                            inputMode="numeric"
                            maxLength={6}
                            value={data.pin}
                            onChange={(e) =>
                                setData(
                                    'pin',
                                    e.target.value.replace(/\D/g, ''),
                                )
                            }
                            placeholder="6-digit PIN"
                            className="h-12 w-full rounded-xl border-[1.5px] border-border-input bg-input-bg px-3.5 text-[15px] tracking-[4px] text-ink"
                        />
                        <div className="mt-1.5 text-[11px] text-faint">
                            Given to you at registration. Contact the Exam
                            Officer if you don't have one.
                        </div>

                        {(errors.mat_no || errors.pin) && (
                            <div className="mt-3 rounded-[10px] bg-error-bg px-3 py-2.5 text-[13px] text-error">
                                {errors.mat_no ?? errors.pin}
                            </div>
                        )}

                        <button
                            type="submit"
                            disabled={processing}
                            className="mt-4 h-[50px] w-full rounded-[13px] bg-primary text-[15px] font-bold text-white hover:bg-primary-hover disabled:opacity-70"
                        >
                            View my result
                        </button>
                    </form>
                </div>

                <div className="mx-[18px] mt-4 flex items-center justify-between gap-3 rounded-2xl bg-white p-4">
                    <div>
                        <div className="text-sm font-bold text-ink">
                            Staff access
                        </div>
                        <div className="mt-0.5 text-xs text-muted">
                            Lecturers, Exam Officer &amp; HoD
                        </div>
                    </div>
                    <Link
                        href={route('login')}
                        className="flex h-10 items-center rounded-[11px] border-[1.5px] border-primary px-[18px] text-[13px] font-bold text-primary hover:bg-tint"
                    >
                        Sign in
                    </Link>
                </div>

                {bioDataHref && (
                    <div className="mx-[18px] mt-3 text-center text-[12px] font-semibold text-muted">
                        Just need to update your bio data or photo?{' '}
                        <Link
                            href={bioDataHref}
                            className="text-primary underline-offset-2 hover:underline"
                        >
                            Update it here
                        </Link>
                    </div>
                )}

                <div className="mt-auto p-[22px] text-center text-[11px] text-faint2">
                    Unity State University · Faculty of Computing
                    <br />
                    Results are provisional until approved by Senate.
                </div>
            </div>
        </MobileShell>
    );
}
