import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ sessions, sessionId, level, semester, grid }) {
    const [pickedSession, setPickedSession] = useState(sessionId ?? sessions[0]?.id ?? '');
    const [pickedLevel, setPickedLevel] = useState(level ?? 100);
    const [pickedSemester, setPickedSemester] = useState(semester ?? 1);

    const load = () => {
        router.get(
            route('admin.electives.index'),
            { session_id: pickedSession, level: pickedLevel, semester: pickedSemester },
            { preserveState: true },
        );
    };

    return (
        <div className="flex-1 px-4 pt-4 pb-6 md:mx-auto md:w-full md:max-w-3xl md:px-8">
            <Head title="Elective registrations" />

            <div className="text-[15px] font-extrabold text-ink">
                Elective registrations
            </div>
            <div className="mb-3.5 mt-1 text-xs text-muted">
                Register which elective course each student is taking. Score
                entry for an elective course only shows students registered
                for it.
            </div>

            <div className="mb-3.5 flex flex-col gap-2.5 rounded-2xl border border-border bg-white p-3.5 sm:flex-row sm:items-end">
                <TargetField label="Session">
                    <select
                        value={pickedSession}
                        onChange={(e) => setPickedSession(e.target.value)}
                        className="h-11 w-full rounded-xl border-[1.5px] border-border-input bg-input-bg px-2.5 text-sm text-ink"
                    >
                        {sessions.map((s) => (
                            <option key={s.id} value={s.id}>
                                {s.name}
                            </option>
                        ))}
                    </select>
                </TargetField>
                <TargetField label="Level">
                    <select
                        value={pickedLevel}
                        onChange={(e) => setPickedLevel(Number(e.target.value))}
                        className="h-11 w-full rounded-xl border-[1.5px] border-border-input bg-input-bg px-2.5 text-sm text-ink"
                    >
                        {[100, 200, 300, 400].map((l) => (
                            <option key={l} value={l}>
                                {l} Level
                            </option>
                        ))}
                    </select>
                </TargetField>
                <TargetField label="Semester">
                    <select
                        value={pickedSemester}
                        onChange={(e) => setPickedSemester(Number(e.target.value))}
                        className="h-11 w-full rounded-xl border-[1.5px] border-border-input bg-input-bg px-2.5 text-sm text-ink"
                    >
                        <option value={1}>First</option>
                        <option value={2}>Second</option>
                    </select>
                </TargetField>
                <button
                    type="button"
                    onClick={load}
                    className="h-11 flex-none rounded-xl bg-primary px-5 text-[13px] font-bold text-white hover:bg-primary-hover"
                >
                    Load
                </button>
            </div>

            {grid && <Grid grid={grid} sessionId={pickedSession} level={pickedLevel} semester={pickedSemester} />}

            {!grid && (
                <div className="rounded-2xl border border-dashed border-border-input bg-white p-[22px] text-center text-[12.5px] text-faint">
                    Choose a session, level and semester, then Load.
                </div>
            )}
        </div>
    );
}

function Grid({ grid, sessionId, level, semester }) {
    const key = (studentId, courseId) => `${studentId}:${courseId}`;

    const [selected, setSelected] = useState(
        () => new Set(grid.selections.map((s) => key(s.student_id, s.course_id))),
    );
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');

    const countFor = (studentId, group) =>
        group.courses.filter((c) => selected.has(key(studentId, c.id))).length;

    const toggle = (studentId, courseId) => {
        setSelected((prev) => {
            const next = new Set(prev);
            const k = key(studentId, courseId);
            if (next.has(k)) {
                next.delete(k);
            } else {
                next.add(k);
            }
            return next;
        });
    };

    const save = () => {
        setSaving(true);
        setError('');

        const selections = [];
        selected.forEach((entry) => {
            const [student_id, course_id] = entry.split(':');
            selections.push({ student_id, course_id });
        });

        router.post(
            route('admin.electives.update'),
            { session_id: sessionId, level, semester, selections },
            {
                preserveScroll: true,
                onError: (errors) => setError(errors.selections ?? 'Could not save.'),
                onFinish: () => setSaving(false),
            },
        );
    };

    if (grid.groups.length === 0) {
        return (
            <div className="rounded-2xl border border-dashed border-border-input bg-white p-[22px] text-center text-[12.5px] text-faint">
                No elective courses at this level/semester.
            </div>
        );
    }

    if (grid.students.length === 0) {
        return (
            <div className="rounded-2xl border border-dashed border-border-input bg-white p-[22px] text-center text-[12.5px] text-faint">
                No students enrolled at this level for this session.
            </div>
        );
    }

    return (
        <div>
            {error && (
                <div className="mb-3 rounded-[10px] bg-error-bg px-3 py-2.5 text-[13px] text-error">
                    {error}
                </div>
            )}

            <div className="overflow-x-auto rounded-2xl border border-border bg-white">
                <table className="w-full min-w-[560px] border-collapse text-[12px]">
                    <thead>
                        <tr className="bg-table-head text-left">
                            <th className="sticky left-0 z-[1] bg-table-head px-3 py-2.5 font-bold text-ink">
                                Student
                            </th>
                            {grid.groups.map((group) => (
                                <th
                                    key={group.key}
                                    colSpan={group.courses.length}
                                    className="border-l border-border px-3 py-2.5 text-center font-bold text-ink"
                                >
                                    {group.key} (choose {group.choose_count})
                                </th>
                            ))}
                        </tr>
                        <tr className="bg-table-head text-left">
                            <th className="sticky left-0 z-[1] bg-table-head px-3 pb-2.5" />
                            {grid.groups.flatMap((group) =>
                                group.courses.map((course, index) => (
                                    <th
                                        key={course.id}
                                        className={
                                            'px-2 pb-2.5 text-center text-[10.5px] font-semibold text-muted' +
                                            (index === 0 ? ' border-l border-border' : '')
                                        }
                                        title={course.title}
                                    >
                                        {course.code}
                                    </th>
                                )),
                            )}
                        </tr>
                    </thead>
                    <tbody>
                        {grid.students.map((student) => (
                            <tr key={student.id} className="border-t border-border">
                                <td className="sticky left-0 z-[1] bg-white px-3 py-2.5">
                                    <div className="font-bold text-ink">{student.name}</div>
                                    <div className="text-[10.5px] text-faint">{student.mat_no}</div>
                                </td>
                                {grid.groups.map((group) => {
                                    const count = countFor(student.id, group);
                                    const mismatch = count > 0 && count !== group.choose_count;

                                    return group.courses.map((course, index) => (
                                        <td
                                            key={course.id}
                                            className={
                                                'px-2 py-2.5 text-center' +
                                                (index === 0 ? ' border-l border-border' : '') +
                                                (mismatch ? ' bg-error-bg/40' : '')
                                            }
                                        >
                                            <input
                                                type="checkbox"
                                                checked={selected.has(key(student.id, course.id))}
                                                onChange={() => toggle(student.id, course.id)}
                                            />
                                        </td>
                                    ));
                                })}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <div className="mt-2.5 text-[11px] text-faint">
                A highlighted cell means that student's picks in that group
                don't add up to the required count yet.
            </div>

            <button
                type="button"
                onClick={save}
                disabled={saving}
                className="mt-3.5 h-[46px] w-full rounded-xl bg-primary text-sm font-bold text-white hover:bg-primary-hover disabled:opacity-70"
            >
                Save registrations
            </button>
        </div>
    );
}

function TargetField({ label, children }) {
    return (
        <div className="flex-1">
            <label className="mb-1.5 block text-[11px] font-semibold uppercase tracking-[.4px] text-faint">
                {label}
            </label>
            {children}
        </div>
    );
}
