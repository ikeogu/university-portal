import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router, useForm } from '@inertiajs/react';

export default function Index({ lecturers }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.lecturers.store'), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    const toggleCourse = (lecturerId, courseId) => {
        router.post(
            route('admin.lecturers.toggle-course', [lecturerId, courseId]),
            {},
            { preserveScroll: true },
        );
    };

    return (
        <div className="flex-1 px-4 pt-4 md:px-8">
            <Head title="Lecturers" />

            <div className="text-[15px] font-extrabold text-ink">
                Lecturers &amp; course allocation
            </div>

            <div className="mt-3 grid grid-cols-1 gap-2.5 pb-4 md:grid-cols-2 lg:grid-cols-3">
                {lecturers.map((lecturer) => (
                    <div
                        key={lecturer.id}
                        className="rounded-2xl border border-border bg-white px-[15px] py-3.5"
                    >
                        <div className="flex items-center justify-between gap-2.5">
                            <div className="truncate text-[13.5px] font-bold text-ink">
                                {lecturer.name}
                            </div>
                            <span className="flex-none rounded-full bg-tint px-2.5 py-[3px] text-[10.5px] font-bold text-primary">
                                {lecturer.role}
                            </span>
                        </div>

                        <div className="mb-1.5 mt-2 text-[11px] text-faint">
                            Tap a course to assign or remove:
                        </div>

                        <div className="flex flex-wrap gap-1.5">
                            {lecturer.chips.map((chip) => (
                                <button
                                    key={chip.course_id}
                                    type="button"
                                    onClick={() =>
                                        toggleCourse(
                                            lecturer.id,
                                            chip.course_id,
                                        )
                                    }
                                    className={
                                        'rounded-full border-[1.5px] px-3 py-1.5 text-[11px] font-bold ' +
                                        (chip.assigned
                                            ? 'border-primary bg-primary text-white'
                                            : 'border-border-input bg-white text-muted')
                                    }
                                >
                                    {chip.code}
                                </button>
                            ))}
                        </div>
                    </div>
                ))}

                {lecturers.length === 0 && (
                    <div className="rounded-2xl border border-dashed border-border-input bg-white p-[22px] text-center text-[12.5px] text-faint md:col-span-2 lg:col-span-3">
                        No staff found.
                    </div>
                )}
            </div>

            <form
                onSubmit={submit}
                className="mb-6 flex flex-col gap-2.5 rounded-2xl border border-border bg-white p-3.5 md:mx-auto md:w-full md:max-w-xl"
            >
                <div className="text-[11.5px] font-semibold uppercase tracking-[.4px] text-body">
                    Add a lecturer
                </div>

                <input
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    placeholder="Full name…"
                    className="h-11 w-full rounded-xl border-[1.5px] border-border-input bg-input-bg px-3.5 text-sm text-ink"
                />
                {errors.name && <FieldError message={errors.name} />}

                <div className="flex gap-2">
                    <input
                        type="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        placeholder="Email address…"
                        className="h-11 min-w-0 flex-1 rounded-xl border-[1.5px] border-border-input bg-input-bg px-3.5 text-sm text-ink"
                    />
                    <button
                        type="submit"
                        disabled={processing}
                        className="flex h-11 items-center whitespace-nowrap rounded-xl bg-primary px-4 text-[13px] font-bold text-white hover:bg-primary-hover disabled:opacity-70"
                    >
                        Add
                    </button>
                </div>
                {errors.email && <FieldError message={errors.email} />}

                <div className="text-[11px] text-faint2">
                    New lecturers are added without courses; allocate from
                    the chips above.
                </div>
            </form>
        </div>
    );
}

function FieldError({ message }) {
    return (
        <div className="rounded-[10px] bg-error-bg px-3 py-2 text-[13px] text-error">
            {message}
        </div>
    );
}

Index.layout = (page) => <AdminLayout active="lecturers">{page}</AdminLayout>;
