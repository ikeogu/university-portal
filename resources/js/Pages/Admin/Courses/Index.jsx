import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

const CREDIT_UNIT_OPTIONS = [1, 2, 3, 4, 6];
const LEVEL_OPTIONS = [100, 200, 300, 400];

export default function Index({ semesters }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        code: '',
        title: '',
        credit_units: '1',
        semester: '1',
        level: '100',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.courses.store'), {
            preserveScroll: true,
            onSuccess: () => reset('code', 'title'),
        });
    };

    return (
        <div className="flex-1 px-4 pt-4 pb-6 md:px-8">
            <Head title="Courses" />

            <div className="flex items-center gap-2">
                <div className="flex-1 text-[15px] font-bold text-ink">Courses</div>
                <Link
                    href={route('admin.upload.index')}
                    className="flex h-11 items-center whitespace-nowrap rounded-xl border-[1.5px] border-primary px-3 text-[13px] font-bold text-primary hover:bg-tint"
                >
                    ⬆ Bulk upload
                </Link>
            </div>

            <div className="mt-3.5 grid grid-cols-1 gap-4 md:grid-cols-2 md:gap-5">
                {semesters.map((semester) => (
                    <SemesterList key={semester.value} semester={semester} />
                ))}
            </div>

            <div className="mb-2 mt-5 text-[15px] font-bold text-ink">
                Add a course
            </div>

            <form
                onSubmit={submit}
                className="rounded-[18px] border border-border bg-white px-[18px] py-5 md:mx-auto md:w-full md:max-w-xl"
            >
                <Field label="Course code">
                    <input
                        value={data.code}
                        onChange={(e) => setData('code', e.target.value)}
                        placeholder="e.g. CSC 411"
                        className="h-[46px] w-full rounded-xl border-[1.5px] border-border-input bg-input-bg px-3.5 text-sm text-ink"
                    />
                </Field>
                {errors.code && <FieldError message={errors.code} />}

                <Field label="Title" className="mt-3.5">
                    <input
                        value={data.title}
                        onChange={(e) => setData('title', e.target.value)}
                        placeholder="e.g. Software Engineering"
                        className="h-[46px] w-full rounded-xl border-[1.5px] border-border-input bg-input-bg px-3.5 text-sm text-ink"
                    />
                </Field>
                {errors.title && <FieldError message={errors.title} />}

                <div className="mt-3.5 grid grid-cols-2 gap-2.5">
                    <Field label="Credit units">
                        <select
                            value={data.credit_units}
                            onChange={(e) =>
                                setData('credit_units', e.target.value)
                            }
                            className="h-[46px] w-full rounded-xl border-[1.5px] border-border-input bg-input-bg px-2.5 text-sm text-ink"
                        >
                            {CREDIT_UNIT_OPTIONS.map((cu) => (
                                <option key={cu} value={cu}>
                                    {cu}
                                </option>
                            ))}
                        </select>
                    </Field>

                    <Field label="Semester">
                        <select
                            value={data.semester}
                            onChange={(e) => setData('semester', e.target.value)}
                            className="h-[46px] w-full rounded-xl border-[1.5px] border-border-input bg-input-bg px-2.5 text-sm text-ink"
                        >
                            <option value="1">First</option>
                            <option value="2">Second</option>
                        </select>
                    </Field>
                </div>
                {errors.credit_units && <FieldError message={errors.credit_units} />}
                {errors.semester && <FieldError message={errors.semester} />}

                <Field label="Level" className="mt-3.5">
                    <select
                        value={data.level}
                        onChange={(e) => setData('level', e.target.value)}
                        className="h-[46px] w-full rounded-xl border-[1.5px] border-border-input bg-input-bg px-2.5 text-sm text-ink"
                    >
                        {LEVEL_OPTIONS.map((level) => (
                            <option key={level} value={level}>
                                {level} Level
                            </option>
                        ))}
                    </select>
                </Field>
                {errors.level && <FieldError message={errors.level} />}

                <button
                    type="submit"
                    disabled={processing}
                    className="mt-[18px] h-[50px] w-full rounded-[13px] bg-primary text-[15px] font-bold text-white hover:bg-primary-hover disabled:opacity-70"
                >
                    Add course
                </button>
            </form>
        </div>
    );
}

function SemesterList({ semester }) {
    return (
        <div>
            <div className="mb-2 text-xs font-bold text-muted">
                {semester.label} semester · {semester.total_credit_units} units
            </div>

            <div className="overflow-hidden rounded-2xl border border-border bg-white">
                {semester.courses.map((course, index) => (
                    <div
                        key={course.id}
                        className={
                            'flex items-center justify-between gap-2.5 px-[15px] py-3.5' +
                            (index > 0 ? ' border-t border-border' : '')
                        }
                    >
                        <div className="min-w-0">
                            <div className="truncate text-[13.5px] text-ink">
                                <span className="font-bold">{course.code}</span>{' '}
                                — {course.title}
                            </div>
                            <div className="mt-0.5 truncate text-[11.5px] text-faint">
                                {course.lecturers}
                            </div>
                        </div>
                        <div className="flex flex-none items-center gap-2">
                            <span className="rounded-full bg-tint px-2.5 py-1 text-[11px] font-bold text-primary">
                                {course.credit_units} CU
                            </span>
                            <Link
                                href={route('scores.show', course.id)}
                                className="rounded-full border-[1.5px] border-border-input px-2.5 py-1 text-[11px] font-semibold text-muted hover:border-primary hover:text-primary"
                            >
                                Scores
                            </Link>
                        </div>
                    </div>
                ))}

                {semester.courses.length === 0 && (
                    <div className="p-[22px] text-center text-[12.5px] text-faint">
                        No courses yet.
                    </div>
                )}
            </div>
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

Index.layout = (page) => <AdminLayout active="courses">{page}</AdminLayout>;
