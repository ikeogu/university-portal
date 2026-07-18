import { GRADE_COLORS } from '@/lib/grading';
import MobileShell from '@/Layouts/MobileShell';
import { Head, Link, router } from '@inertiajs/react';

export default function Show({
    student,
    programmeName,
    sessions,
    selectedSessionId,
    selectedLevel,
    semester,
    rows,
    semTcu,
    semGpa,
    cgpa,
    backHref,
    backLabel,
    resultHref,
    printHref,
    bioDataHref,
    regeneratePinHref,
}) {
    const goTo = (params) => {
        router.get(
            resultHref,
            {
                session: selectedSessionId,
                semester,
                ...params,
            },
            { preserveState: true, replace: true },
        );
    };

    return (
        <MobileShell>
            <Head title="Academic result" />

            <div className="flex flex-1 flex-col animate-screenin pb-6">
                <div className="sticky top-0 z-[5] flex items-center gap-2.5 border-b border-border bg-white p-3.5">
                    <Link
                        href={backHref}
                        className="flex h-9 w-9 flex-none items-center justify-center rounded-[10px] border border-border-soft bg-white text-lg leading-none text-primary"
                    >
                        &lsaquo;
                    </Link>
                    <div className="text-[15px] font-bold text-ink">
                        {backLabel}
                    </div>
                </div>

                <div className="mx-4 mt-4 rounded-[18px] bg-navy px-[18px] pb-4 pt-[18px] text-white">
                    <div className="flex items-center gap-3">
                        {student.photo_url ? (
                            <img
                                src={student.photo_url}
                                alt={student.name}
                                className="h-14 w-14 flex-none rounded-full border-2 border-white/30 object-cover"
                            />
                        ) : (
                            <div className="flex h-14 w-14 flex-none items-center justify-center rounded-full bg-white/[.14] text-[15px] font-extrabold">
                                {student.name?.[0]}
                            </div>
                        )}
                        <div className="min-w-0">
                            <div className="text-[16px] font-extrabold">
                                {student.name}
                            </div>
                            <div className="mt-1 text-[12.5px] opacity-80">
                                {student.mat_no} · {programmeName}
                            </div>
                        </div>
                    </div>
                    <div className="mt-3 flex flex-wrap gap-1.5">
                        <Chip>{student.mode_of_study}</Chip>
                        <Chip>{student.state_of_origin ?? '—'} State</Chip>
                        <Chip>
                            {student.entry_year} set · {selectedLevel} Level
                        </Chip>
                    </div>
                </div>

                {bioDataHref && (
                    <Link
                        href={bioDataHref}
                        className="mx-4 mt-3.5 flex h-11 items-center justify-center rounded-xl border-[1.5px] border-primary text-[13px] font-bold text-primary hover:bg-tint"
                    >
                        Cross-check my bio data &amp; photo
                    </Link>
                )}

                {regeneratePinHref && (
                    <button
                        type="button"
                        onClick={() => {
                            if (
                                confirm(
                                    "Generate a new access PIN? The student's old PIN will stop working immediately.",
                                )
                            ) {
                                router.post(regeneratePinHref);
                            }
                        }}
                        className="mx-4 mt-2.5 flex h-11 items-center justify-center rounded-xl border-[1.5px] border-border-input text-[13px] font-bold text-muted hover:bg-tint"
                    >
                        Regenerate access PIN
                    </button>
                )}

                {sessions.length > 1 && (
                    <div className="mx-4 mt-3.5">
                        <label className="mb-1.5 block text-[11px] font-semibold uppercase tracking-[.4px] text-faint">
                            Session
                        </label>
                        <select
                            value={selectedSessionId}
                            onChange={(e) =>
                                goTo({ session: e.target.value })
                            }
                            className="h-11 w-full rounded-xl border-[1.5px] border-border-input bg-white px-3 text-sm text-ink"
                        >
                            {sessions.map((s) => (
                                <option key={s.id} value={s.id}>
                                    {s.name} ({s.level}L)
                                </option>
                            ))}
                        </select>
                    </div>
                )}

                <div className="mx-4 mt-3.5 flex gap-2">
                    <ToggleButton
                        active={semester === 1}
                        onClick={() => goTo({ semester: 1 })}
                    >
                        First semester
                    </ToggleButton>
                    <ToggleButton
                        active={semester === 2}
                        onClick={() => goTo({ semester: 2 })}
                    >
                        Second semester
                    </ToggleButton>
                </div>

                <div className="mx-4 mt-3.5 overflow-hidden rounded-2xl border border-border bg-white">
                    <div className="grid grid-cols-[1fr_36px_48px_44px] gap-2 bg-table-head px-4 py-2.5 text-[10.5px] font-bold uppercase tracking-[.5px] text-muted">
                        <div>Course</div>
                        <div className="text-center">CU</div>
                        <div className="text-center">Mark</div>
                        <div className="text-center">Grade</div>
                    </div>

                    {rows.map((row) => (
                        <div
                            key={row.code}
                            className="grid grid-cols-[1fr_36px_48px_44px] items-center gap-2 border-t border-[#eef1f7] px-4 py-3"
                        >
                            <div>
                                <div className="text-[13.5px] font-bold text-ink">
                                    {row.code}
                                </div>
                                <div className="mt-0.5 text-[11.5px] text-[#7a839c]">
                                    {row.title}
                                </div>
                            </div>
                            <div className="text-center text-[13px] text-body">
                                {row.cu}
                            </div>
                            <div className="text-center text-[13.5px] font-semibold text-ink">
                                {row.mark ?? '—'}
                            </div>
                            <div className="flex justify-center">
                                {row.grade ? (
                                    <span
                                        className="min-w-[26px] rounded-lg px-1.5 py-[3px] text-center text-[12.5px] font-extrabold text-white"
                                        style={{
                                            background:
                                                GRADE_COLORS[row.grade],
                                        }}
                                    >
                                        {row.grade}
                                    </span>
                                ) : (
                                    <span className="text-[11px] text-faint">
                                        —
                                    </span>
                                )}
                            </div>
                        </div>
                    ))}

                    {rows.length === 0 && (
                        <div className="p-[22px] text-center text-[12.5px] text-faint">
                            No courses registered for this semester.
                        </div>
                    )}
                </div>

                <div className="mx-4 mt-3.5 grid grid-cols-3 gap-2.5 rounded-2xl border border-border bg-white p-4 text-center">
                    <Stat label="TCU" value={semTcu} />
                    <Stat label="GPA" value={semGpa.toFixed(2)} color="text-primary" />
                    <Stat label="CGPA" value={cgpa.toFixed(2)} color="text-success" />
                </div>

                <a
                    href={`${printHref}?session=${selectedSessionId}`}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="mx-4 mt-4 flex h-[50px] items-center justify-center rounded-[13px] bg-primary text-[15px] font-bold text-white hover:bg-primary-hover"
                >
                    Print result statement
                </a>

                <div className="mx-4 mt-2.5 text-center text-[11px] text-faint2">
                    Statement follows the official university format.
                </div>
            </div>
        </MobileShell>
    );
}

function Chip({ children }) {
    return (
        <span className="rounded-full bg-white/[.14] px-2.5 py-1 text-[11px] font-semibold">
            {children}
        </span>
    );
}

function ToggleButton({ active, onClick, children }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={
                'h-10 flex-1 rounded-xl border-[1.5px] text-[13px] font-semibold ' +
                (active
                    ? 'border-primary bg-primary text-white'
                    : 'border-border-input bg-white text-body')
            }
        >
            {children}
        </button>
    );
}

function Stat({ label, value, color = 'text-ink' }) {
    return (
        <div>
            <div className="text-[10.5px] font-bold uppercase tracking-[.4px] text-faint">
                {label}
            </div>
            <div className={'mt-0.5 text-xl font-extrabold ' + color}>
                {value}
            </div>
        </div>
    );
}
