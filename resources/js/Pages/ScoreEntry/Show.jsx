import { GRADE_COLORS, gradeFromMark } from '@/lib/grading';
import MobileShell from '@/Layouts/MobileShell';
import { SHELL_WIDTH_WIDE } from '@/lib/layout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Show({ course, session, rows }) {
    const [entries, setEntries] = useState(() =>
        Object.fromEntries(
            rows.map((r) => [r.student_id, { ca: r.ca ?? 0, exam: r.exam ?? 0 }]),
        ),
    );
    const [saving, setSaving] = useState(false);

    const setValue = (studentId, field, raw, cap) => {
        const clamped = Math.max(0, Math.min(cap, parseInt(raw, 10) || 0));
        setEntries((prev) => ({
            ...prev,
            [studentId]: { ...prev[studentId], [field]: clamped },
        }));
    };

    const save = () => {
        setSaving(true);
        router.put(
            route('scores.update', course.id),
            { scores: entries },
            { preserveScroll: true, onFinish: () => setSaving(false) },
        );
    };

    return (
        <MobileShell width="wide">
            <Head title={`${course.code} — score entry`} />

            <div className="flex flex-1 flex-col pb-[86px]">
                <div className="sticky top-0 z-[5] flex items-center gap-2.5 border-b border-border bg-white p-3 md:px-8">
                    <Link
                        href={route('lecturer.dashboard')}
                        className="flex h-9 w-9 flex-none items-center justify-center rounded-[10px] border border-border-soft bg-white text-lg leading-none text-primary"
                    >
                        &lsaquo;
                    </Link>
                    <div className="min-w-0">
                        <div className="text-[15px] font-extrabold text-ink">
                            {course.code} — score entry
                        </div>
                        <div className="truncate text-[11.5px] text-muted">
                            {course.title} · {course.semester} semester{' '}
                            {session.name}
                        </div>
                    </div>
                </div>

                <div className="mx-4 mt-3.5 overflow-hidden rounded-2xl border border-border bg-white md:mx-8">
                    <div className="overflow-x-auto">
                        <div className="min-w-[432px]">
                            <div className="grid grid-cols-[26px_1fr_58px_58px_44px_40px] items-center gap-1.5 bg-table-head px-3 py-2.5 text-[10px] font-bold uppercase tracking-[.4px] text-muted">
                                <div>#</div>
                                <div>Student</div>
                                <div className="text-center">CA /30</div>
                                <div className="text-center">Exam /70</div>
                                <div className="text-center">Total</div>
                                <div className="text-center">Gr</div>
                            </div>

                            {rows.map((row, index) => {
                                const entry = entries[row.student_id];
                                const total = entry.ca + entry.exam;
                                const grade = gradeFromMark(total);

                                return (
                                    <div
                                        key={row.student_id}
                                        className="grid grid-cols-[26px_1fr_58px_58px_44px_40px] items-center gap-1.5 border-t border-border px-3 py-2"
                                    >
                                        <div className="text-[11.5px] text-faint">
                                            {index + 1}
                                        </div>
                                        <div className="min-w-0">
                                            <div className="truncate text-[12.5px] font-bold text-ink">
                                                {row.name}
                                            </div>
                                            <div className="text-[10.5px] text-faint">
                                                {row.mat_no}
                                            </div>
                                        </div>
                                        <input
                                            value={entry.ca}
                                            inputMode="numeric"
                                            onChange={(e) =>
                                                setValue(
                                                    row.student_id,
                                                    'ca',
                                                    e.target.value,
                                                    30,
                                                )
                                            }
                                            className="h-[38px] w-full rounded-[9px] border-[1.5px] border-border-input bg-input-bg text-center text-[13.5px] font-semibold text-ink"
                                        />
                                        <input
                                            value={entry.exam}
                                            inputMode="numeric"
                                            onChange={(e) =>
                                                setValue(
                                                    row.student_id,
                                                    'exam',
                                                    e.target.value,
                                                    70,
                                                )
                                            }
                                            className="h-[38px] w-full rounded-[9px] border-[1.5px] border-border-input bg-input-bg text-center text-[13.5px] font-semibold text-ink"
                                        />
                                        <div className="text-center text-[13.5px] font-bold text-ink">
                                            {total}
                                        </div>
                                        <div className="flex justify-center">
                                            <span
                                                className="min-w-[22px] rounded-[7px] px-1.5 py-0.5 text-center text-xs font-extrabold text-white"
                                                style={{
                                                    background:
                                                        GRADE_COLORS[grade],
                                                }}
                                            >
                                                {grade}
                                            </span>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </div>

                <div className="mx-4 mt-2.5 text-center text-[11px] text-faint2 md:mx-8">
                    CA is capped at 30, exam at 70. Grades update as you type.
                </div>
            </div>

            <div
                className={`fixed bottom-0 left-1/2 flex w-full ${SHELL_WIDTH_WIDE} -translate-x-1/2 gap-2.5 border-t border-border bg-shell/95 p-4 backdrop-blur md:px-8`}
            >
                <button
                    type="button"
                    onClick={save}
                    disabled={saving}
                    className="h-12 flex-1 rounded-xl bg-primary text-sm font-bold text-white hover:bg-primary-hover disabled:opacity-70"
                >
                    Save scores
                </button>
                <a
                    href={route('scores.print', course.id)}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="flex h-12 flex-1 items-center justify-center rounded-xl border-[1.5px] border-primary text-sm font-bold text-primary hover:bg-tint"
                >
                    Print score sheet
                </a>
            </div>
        </MobileShell>
    );
}
