import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function Onboard() {
    const { data, setData, post, processing, errors } = useForm({
        last_name: '',
        first_name: '',
        middle_name: '',
        mat_no: '',
        dob: '',
        state_of_origin: '',
        marital_status: 'single',
        mode_of_study: 'full_time',
        photo: null,
    });

    const [photoPreview, setPhotoPreview] = useState(null);

    const onPhotoChange = (e) => {
        const file = e.target.files?.[0] ?? null;
        setData('photo', file);
        setPhotoPreview(file ? URL.createObjectURL(file) : null);
    };

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.students.store'), { forceFormData: true });
    };

    return (
        <div className="flex flex-1 flex-col pb-6">
            <Head title="Onboard a student" />

            <div className="flex items-center gap-2.5 border-b border-border bg-white p-3.5 md:px-8">
                <Link
                    href={route('admin.students.index')}
                    className="flex h-9 w-9 flex-none items-center justify-center rounded-[10px] border border-border-soft bg-white text-lg leading-none text-primary"
                >
                    &lsaquo;
                </Link>
                <div className="text-[15px] font-bold text-ink">
                    Onboard a student
                </div>
            </div>

            <form
                onSubmit={submit}
                className="mx-4 mt-4 rounded-[18px] border border-border bg-white px-[18px] py-5 md:mx-auto md:mt-6 md:w-full md:max-w-xl md:px-8 md:py-7"
            >
                <Field label="Photo (optional)">
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
                        onChange={(e) => setData('last_name', e.target.value)}
                        placeholder="e.g. Okoro"
                        className="h-[46px] w-full rounded-xl border-[1.5px] border-border-input bg-input-bg px-3.5 text-sm text-ink"
                    />
                </Field>
                {errors.last_name && <FieldError message={errors.last_name} />}

                <Field label="First name" className="mt-3.5">
                    <input
                        value={data.first_name}
                        onChange={(e) => setData('first_name', e.target.value)}
                        placeholder="e.g. Chidera"
                        className="h-[46px] w-full rounded-xl border-[1.5px] border-border-input bg-input-bg px-3.5 text-sm text-ink"
                    />
                </Field>
                {errors.first_name && <FieldError message={errors.first_name} />}

                <Field label="Middle name" className="mt-3.5">
                    <input
                        value={data.middle_name}
                        onChange={(e) => setData('middle_name', e.target.value)}
                        placeholder="e.g. Faith"
                        className="h-[46px] w-full rounded-xl border-[1.5px] border-border-input bg-input-bg px-3.5 text-sm text-ink"
                    />
                </Field>
                {errors.middle_name && <FieldError message={errors.middle_name} />}

                <Field label="Matriculation number" className="mt-3.5">
                    <input
                        value={data.mat_no}
                        onChange={(e) => setData('mat_no', e.target.value)}
                        placeholder="e.g. U2022/5570016"
                        className="h-[46px] w-full rounded-xl border-[1.5px] border-border-input bg-input-bg px-3.5 text-sm text-ink"
                    />
                </Field>
                {errors.mat_no && <FieldError message={errors.mat_no} />}

                <Field label="Date of birth" className="mt-3.5">
                    <input
                        type="date"
                        value={data.dob}
                        onChange={(e) => setData('dob', e.target.value)}
                        className="h-[46px] w-full rounded-xl border-[1.5px] border-border-input bg-input-bg px-3.5 text-sm text-ink"
                    />
                </Field>
                {errors.dob && <FieldError message={errors.dob} />}

                <Field label="State of origin" className="mt-3.5">
                    <input
                        value={data.state_of_origin}
                        onChange={(e) =>
                            setData('state_of_origin', e.target.value)
                        }
                        placeholder="e.g. Rivers"
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
                    Onboard student
                </button>
            </form>
        </div>
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

Onboard.layout = (page) => <AdminLayout active="students">{page}</AdminLayout>;
