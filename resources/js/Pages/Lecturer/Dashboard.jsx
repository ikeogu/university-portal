import MobileShell from '@/Layouts/MobileShell';
import { Head, Link, router, usePage } from '@inertiajs/react';

export default function Dashboard({ courses, semester }) {
    const { auth } = usePage().props;

    const setSemester = (value) => {
        router.get(
            route('lecturer.dashboard'),
            { semester: value },
            { preserveState: true, replace: true },
        );
    };

    return (
        <MobileShell width="wide">
            <Head title="Lecturer dashboard" />

            <div className="flex flex-1 flex-col pb-6">
                <div className="flex items-start justify-between gap-3 rounded-b-[24px] bg-navy px-[22px] pb-[30px] pt-6 text-white md:px-8">
                    <div>
                        <div className="text-xs opacity-70">Good day,</div>
                        <div className="mt-0.5 text-lg font-extrabold">
                            {auth.user.name}
                        </div>
                        <span className="mt-2 inline-block rounded-full bg-white/[.14] px-3 py-1 text-[11px] font-bold">
                            Lecturer
                        </span>
                    </div>
                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="h-[34px] rounded-[10px] border border-white/35 px-3.5 text-xs font-semibold text-white"
                    >
                        Sign out
                    </Link>
                </div>

                <div className="mx-4 mt-4 flex gap-2 md:mx-8 md:max-w-sm">
                    <button
                        type="button"
                        onClick={() => setSemester(1)}
                        className={
                            'h-[38px] flex-1 rounded-xl border-[1.5px] text-[12.5px] font-semibold ' +
                            (semester === 1
                                ? 'border-primary bg-primary text-white'
                                : 'border-border-input bg-white text-body')
                        }
                    >
                        First semester
                    </button>
                    <button
                        type="button"
                        onClick={() => setSemester(2)}
                        className={
                            'h-[38px] flex-1 rounded-xl border-[1.5px] text-[12.5px] font-semibold ' +
                            (semester === 2
                                ? 'border-primary bg-primary text-white'
                                : 'border-border-input bg-white text-body')
                        }
                    >
                        Second semester
                    </button>
                </div>

                <div className="mb-2 mt-[18px] px-4 text-[13px] font-bold text-body md:px-8">
                    Your courses — 2025/2026
                </div>

                <div className="grid grid-cols-1 gap-2.5 px-4 md:grid-cols-2 md:px-8 lg:grid-cols-3">
                    {courses.map((course) => (
                        <Link
                            key={course.id}
                            href={route('scores.show', course.id)}
                            className="flex items-center justify-between gap-3 rounded-2xl border border-border bg-white p-4 hover:border-primary"
                        >
                            <div>
                                <div className="text-[15px] font-extrabold text-ink">
                                    {course.code}
                                </div>
                                <div className="mt-0.5 text-[12.5px] text-muted">
                                    {course.title}
                                </div>
                                <div className="mt-1.5 text-[11.5px] text-faint">
                                    {course.credit_units} units ·{' '}
                                    {course.student_count} students
                                    registered
                                </div>
                            </div>
                            <div className="text-xl text-primary">›</div>
                        </Link>
                    ))}

                    {courses.length === 0 && (
                        <div className="rounded-2xl border border-dashed border-border-input bg-white p-[22px] text-center text-[12.5px] text-faint md:col-span-2 lg:col-span-3">
                            No courses allocated to you this semester.
                        </div>
                    )}
                </div>

                <div className="mx-4 mt-4 rounded-[14px] bg-tint p-3.5 text-xs leading-relaxed text-body md:mx-8">
                    Tap a course to enter CA and exam scores. Scores save
                    when you press Save.
                </div>
            </div>
        </MobileShell>
    );
}
