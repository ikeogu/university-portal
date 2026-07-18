import MobileShell from '@/Layouts/MobileShell';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function BioData({ verified, closed, student }) {
    if (!verified) {
        return <VerifyForm />;
    }

    if (closed) {
        return <ClosedNotice />;
    }

    return <EditForm student={student} />;
}

function VerifyForm() {
    const { data, setData, post, processing, errors } = useForm({
        mat_no: '',
        pin: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('public.bio.verify'));
    };

    return (
        <MobileShell>
            <Head title="Update my bio data" />

            <div className="flex flex-1 flex-col animate-screenin">
                <div className="rounded-b-[28px] bg-navy px-6 pb-11 pt-8 text-white">
                    <div className="flex items-center gap-3.5">
                        <Link
                            href={route('landing')}
                            className="flex h-9 w-9 flex-none items-center justify-center rounded-[10px] border border-white/30 text-lg leading-none text-white"
                        >
                            &lsaquo;
                        </Link>
                        <div>
                            <div className="text-[17px] font-extrabold tracking-[.2px]">
                                Update my bio data &amp; photo
                            </div>
                            <div className="mt-0.5 text-xs opacity-75">
                                Cross-check your details and keep your photo
                                current
                            </div>
                        </div>
                    </div>
                </div>

                <div className="mx-[18px] -mt-[26px] rounded-[20px] bg-white px-5 py-[22px] shadow-[0_8px_28px_rgba(18,32,66,.10)]">
                    <div className="text-lg font-bold text-ink">
                        Verify it&apos;s you
                    </div>
                    <div className="mb-4 mt-1 text-[12.5px] text-muted">
                        Enter your matriculation number and access PIN to
                        continue.
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
                            Continue
                        </button>
                    </form>
                </div>

                <div className="mt-auto p-[22px] text-center text-[11px] text-faint2">
                    Already know your result?{' '}
                    <Link href={route('public.check')} className="underline">
                        Check it here
                    </Link>
                </div>
            </div>
        </MobileShell>
    );
}

function ClosedNotice() {
    return (
        <MobileShell>
            <Head title="Update my bio data" />

            <div className="flex flex-1 flex-col items-center justify-center gap-3 p-6 text-center animate-screenin">
                <div className="flex h-14 w-14 items-center justify-center rounded-full bg-tint text-2xl">
                    🔒
                </div>
                <div className="text-[15px] font-bold text-ink">
                    Not open right now
                </div>
                <div className="max-w-xs text-[12.5px] text-muted">
                    Bio-data and photo updates aren't open at the moment.
                    Contact the Exam Officer if you need to correct
                    something urgently.
                </div>
                <Link
                    href={route('landing')}
                    className="mt-2 flex h-11 items-center rounded-xl border-[1.5px] border-primary px-5 text-[13px] font-bold text-primary hover:bg-tint"
                >
                    Back to home
                </Link>
            </div>
        </MobileShell>
    );
}

function EditForm({ student }) {
    const { data, setData, patch, processing, errors } = useForm({
        last_name: student.last_name ?? '',
        first_name: student.first_name ?? '',
        middle_name: student.middle_name ?? '',
        dob: student.dob ?? '',
        gender: student.gender ?? 'male',
        state_of_origin: student.state_of_origin ?? '',
        marital_status: student.marital_status ?? 'single',
        mode_of_study: student.mode_of_study ?? 'full_time',
        photo: null,
    });

    const [photoPreview, setPhotoPreview] = useState(student.photo_url ?? null);

    const onPhotoChange = (e) => {
        const file = e.target.files?.[0] ?? null;
        setData('photo', file);
        setPhotoPreview(file ? URL.createObjectURL(file) : student.photo_url ?? null);
    };

    const submit = (e) => {
        e.preventDefault();
        patch(route('public.bio.update'), { forceFormData: true });
    };

    return (
        <MobileShell>
            <Head title="Update my bio data" />

            <div className="flex flex-1 flex-col animate-screenin pb-6">
                <div className="sticky top-0 z-[5] flex items-center gap-2.5 border-b border-border bg-white p-3.5">
                    <Link
                        href={route('landing')}
                        className="flex h-9 w-9 flex-none items-center justify-center rounded-[10px] border border-border-soft bg-white text-lg leading-none text-primary"
                    >
                        &lsaquo;
                    </Link>
                    <div className="min-w-0">
                        <div className="text-[15px] font-bold text-ink">
                            Cross-check my bio data
                        </div>
                        <div className="mt-0.5 truncate text-[11.5px] text-faint">
                            {student.mat_no}
                        </div>
                    </div>
                </div>

                <form
                    onSubmit={submit}
                    className="mx-4 mt-4 rounded-[18px] border border-border bg-white px-[18px] py-5"
                >
                    <div className="mb-4 text-[12.5px] text-muted">
                        Check that everything below is correct — fix
                        anything that's wrong and update your photo if
                        you'd like.
                    </div>

                    <Field label="Photo">
                        <div className="flex items-center gap-3">
                            {photoPreview ? (
                                <img
                                    src={photoPreview}
                                    alt="Photo preview"
                                    className="h-14 w-14 flex-none rounded-full border border-border-input object-cover"
                                />
                            ) : (
                                <div className="flex h-14 w-14 flex-none items-center justify-center rounded-full border border-dashed border-border-input bg-input-bg text-[10px] text-faint">
                                    No photo
                                </div>
                            )}
                            <input
                                type="file"
                                accept="image/*"
                                onChange={onPhotoChange}
                                className="min-w-0 flex-1 text-[13px] text-body file:mr-3 file:rounded-lg file:border-0 file:bg-tint file:px-3 file:py-2 file:text-[12.5px] file:font-semibold file:text-primary"
                            />
                        </div>
                    </Field>
                    {errors.photo && <FieldError message={errors.photo} />}

                    <Field label="Last name (Surname)" className="mt-3.5">
                        <input
                            value={data.last_name}
                            onChange={(e) =>
                                setData('last_name', e.target.value)
                            }
                            className="h-[46px] w-full rounded-xl border-[1.5px] border-border-input bg-input-bg px-3.5 text-sm text-ink"
                        />
                    </Field>
                    {errors.last_name && (
                        <FieldError message={errors.last_name} />
                    )}

                    <Field label="First name" className="mt-3.5">
                        <input
                            value={data.first_name}
                            onChange={(e) =>
                                setData('first_name', e.target.value)
                            }
                            className="h-[46px] w-full rounded-xl border-[1.5px] border-border-input bg-input-bg px-3.5 text-sm text-ink"
                        />
                    </Field>
                    {errors.first_name && (
                        <FieldError message={errors.first_name} />
                    )}

                    <Field label="Middle name" className="mt-3.5">
                        <input
                            value={data.middle_name}
                            onChange={(e) =>
                                setData('middle_name', e.target.value)
                            }
                            className="h-[46px] w-full rounded-xl border-[1.5px] border-border-input bg-input-bg px-3.5 text-sm text-ink"
                        />
                    </Field>
                    {errors.middle_name && (
                        <FieldError message={errors.middle_name} />
                    )}

                    <Field label="Date of birth" className="mt-3.5">
                        <input
                            type="date"
                            value={data.dob}
                            onChange={(e) => setData('dob', e.target.value)}
                            className="h-[46px] w-full rounded-xl border-[1.5px] border-border-input bg-input-bg px-3.5 text-sm text-ink"
                        />
                    </Field>
                    {errors.dob && <FieldError message={errors.dob} />}

                    <Field label="Gender" className="mt-3.5">
                        <select
                            value={data.gender}
                            onChange={(e) =>
                                setData('gender', e.target.value)
                            }
                            className="h-[46px] w-full rounded-xl border-[1.5px] border-border-input bg-input-bg px-2.5 text-sm text-ink"
                        >
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </Field>
                    {errors.gender && <FieldError message={errors.gender} />}

                    <Field label="State of origin" className="mt-3.5">
                        <input
                            value={data.state_of_origin}
                            onChange={(e) =>
                                setData('state_of_origin', e.target.value)
                            }
                            className="h-[46px] w-full rounded-xl border-[1.5px] border-border-input bg-input-bg px-3.5 text-sm text-ink"
                        />
                    </Field>
                    {errors.state_of_origin && (
                        <FieldError message={errors.state_of_origin} />
                    )}

                    <div className="mt-3.5 grid grid-cols-2 gap-2.5">
                        <Field label="Marital status">
                            <select
                                value={data.marital_status}
                                onChange={(e) =>
                                    setData('marital_status', e.target.value)
                                }
                                className="h-[46px] w-full rounded-xl border-[1.5px] border-border-input bg-input-bg px-2.5 text-sm text-ink"
                            >
                                <option value="single">Single</option>
                                <option value="married">Married</option>
                            </select>
                        </Field>

                        <Field label="Mode of study">
                            <select
                                value={data.mode_of_study}
                                onChange={(e) =>
                                    setData('mode_of_study', e.target.value)
                                }
                                className="h-[46px] w-full rounded-xl border-[1.5px] border-border-input bg-input-bg px-2.5 text-sm text-ink"
                            >
                                <option value="full_time">Full time</option>
                                <option value="part_time">Part time</option>
                            </select>
                        </Field>
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="mt-[18px] h-[50px] w-full rounded-[13px] bg-primary text-[15px] font-bold text-white hover:bg-primary-hover disabled:opacity-70"
                    >
                        Save changes
                    </button>
                </form>
            </div>
        </MobileShell>
    );
}

function Field({ label, className = '', children }) {
    return (
        <div className={className}>
            <label className="mb-1.5 block text-[11.5px] font-semibold uppercase tracking-[.4px] text-body">
                {label}
            </label>
            {children}
        </div>
    );
}

function FieldError({ message }) {
    return (
        <div className="mt-1.5 rounded-[10px] bg-error-bg px-3 py-2 text-[13px] text-error">
            {message}
        </div>
    );
}
