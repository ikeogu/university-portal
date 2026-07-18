import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function Index({ students, search, stats }) {
    const [query, setQuery] = useState(search ?? '');

    useEffect(() => {
        const timeout = setTimeout(() => {
            if (query !== search) {
                router.get(
                    route('admin.students.index'),
                    query ? { q: query } : {},
                    { preserveState: true, replace: true },
                );
            }
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [query]);

    return (
        <div className="flex-1 px-4 pt-4 md:px-8">
            <Head title="Students" />

            <div className="flex gap-2">
                <input
                    value={query}
                    onChange={(e) => setQuery(e.target.value)}
                    placeholder="Search name or mat no…"
                    className="h-11 min-w-0 flex-1 rounded-xl border-[1.5px] border-border-input bg-white px-3.5 text-sm"
                />
                <Link
                    href={route('admin.students.create')}
                    className="flex h-11 items-center whitespace-nowrap rounded-xl bg-primary px-3.5 text-[13px] font-bold text-white hover:bg-primary-hover"
                >
                    + Onboard
                </Link>
                <Link
                    href={route('admin.upload.index')}
                    className="flex h-11 items-center whitespace-nowrap rounded-xl border-[1.5px] border-primary px-3 text-[13px] font-bold text-primary hover:bg-tint"
                >
                    ⬆ Bulk
                </Link>
            </div>

            <div className="mt-3 grid animate-popin grid-cols-3 gap-2">
                <StatCard label="Students" value={stats.students} />
                <StatCard label="Courses" value={stats.courses} />
                <StatCard label="Lecturers" value={stats.lecturers} />
            </div>

            <div className="mb-2 mt-3.5 text-xs font-bold text-muted">
                {stats.students} students
            </div>

            <div className="flex flex-col gap-2 pb-4">
                {students.map((student) => (
                    <Link
                        key={student.id}
                        href={route('admin.students.show', student.id)}
                        className="flex items-center justify-between gap-2.5 rounded-2xl border border-border bg-white px-[15px] py-3.5 hover:border-primary"
                    >
                        <div className="flex min-w-0 items-center gap-2.5">
                            {student.photo_url ? (
                                <img
                                    src={student.photo_url}
                                    alt={student.name}
                                    className="h-10 w-10 flex-none rounded-full border border-border-input object-cover"
                                />
                            ) : (
                                <div className="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-tint text-[12px] font-bold text-primary">
                                    {student.first_name?.[0]}
                                    {student.last_name?.[0]}
                                </div>
                            )}
                            <div className="min-w-0">
                                <div className="truncate text-[13.5px] font-bold text-ink">
                                    {student.name}
                                </div>
                                <div className="mt-0.5 text-[11.5px] text-faint">
                                    {student.mat_no} · {student.mode_of_study}{' '}
                                    · {student.state_of_origin ?? '—'} ·{' '}
                                    {student.entry_year} set
                                </div>
                            </div>
                        </div>
                        <div className="text-lg text-faint">›</div>
                    </Link>
                ))}

                {students.length === 0 && (
                    <div className="rounded-2xl border border-dashed border-border-input bg-white p-[22px] text-center text-[12.5px] text-faint">
                        No students found.
                    </div>
                )}
            </div>
        </div>
    );
}

function StatCard({ label, value }) {
    return (
        <div className="rounded-2xl border border-border bg-white p-3 text-center">
            <div className="text-xl font-extrabold text-primary">{value}</div>
            <div className="mt-0.5 text-[10px] font-bold uppercase tracking-[.4px] text-faint">
                {label}
            </div>
        </div>
    );
}

Index.layout = (page) => <AdminLayout active="students">{page}</AdminLayout>;
