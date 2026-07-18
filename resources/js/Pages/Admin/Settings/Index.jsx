import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({
    settings,
    sessions,
    hodSignatureUrl,
    examOfficerSignatureUrl,
}) {
    const settingsForm = useForm({
        bioUpdateOpen: settings.bioUpdateOpen,
        masterSort: settings.masterSort,
        programme_duration_years: settings.programme_duration_years,
        institution_name: settings.institution_name,
        faculty_name: settings.faculty_name,
        department_name: settings.department_name,
        programme_name: settings.programme_name,
        hod_signature: null,
        exam_officer_signature: null,
    });

    const [hodPreview, setHodPreview] = useState(hodSignatureUrl ?? null);
    const [examOfficerPreview, setExamOfficerPreview] = useState(
        examOfficerSignatureUrl ?? null,
    );

    const onHodSignatureChange = (e) => {
        const file = e.target.files?.[0] ?? null;
        settingsForm.setData('hod_signature', file);
        setHodPreview(file ? URL.createObjectURL(file) : hodSignatureUrl ?? null);
    };

    const onExamOfficerSignatureChange = (e) => {
        const file = e.target.files?.[0] ?? null;
        settingsForm.setData('exam_officer_signature', file);
        setExamOfficerPreview(
            file ? URL.createObjectURL(file) : examOfficerSignatureUrl ?? null,
        );
    };

    const sessionForm = useForm({ name: '', is_current: false });

    const advanceForm = useForm({
        from_session_id: sessions[0]?.id ?? '',
        from_level: 100,
        to_session_id: sessions.find((s) => s.is_current)?.id ?? '',
    });

    const levelOptions = Array.from(
        { length: Math.max(settings.programme_duration_years - 1, 1) },
        (_, i) => (i + 1) * 100,
    );

    return (
        <div className="flex-1 space-y-4 px-4 pt-4 pb-6 md:mx-auto md:w-full md:max-w-xl md:px-8">
            <Head title="Settings" />

            <Section title="General settings">
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        settingsForm.patch(route('admin.settings.update'), {
                            preserveScroll: true,
                            forceFormData: true,
                        });
                    }}
                    className="flex flex-col gap-3"
                >
                    <label className="flex items-center justify-between text-[13px] text-ink">
                        Let students update their own bio data &amp; photo
                        <input
                            type="checkbox"
                            checked={settingsForm.data.bioUpdateOpen}
                            onChange={(e) =>
                                settingsForm.setData(
                                    'bioUpdateOpen',
                                    e.target.checked,
                                )
                            }
                        />
                    </label>

                    <Field label="Master sheet ordering">
                        <select
                            value={settingsForm.data.masterSort}
                            onChange={(e) =>
                                settingsForm.setData(
                                    'masterSort',
                                    e.target.value,
                                )
                            }
                            className="h-11 w-full rounded-xl border-[1.5px] border-border-input bg-input-bg px-2.5 text-sm text-ink"
                        >
                            <option value="cgpa">CGPA (highest first)</option>
                            <option value="matno">Matriculation number</option>
                        </select>
                    </Field>

                    <Field label="Programme duration (years)">
                        <input
                            type="number"
                            min="1"
                            max="10"
                            value={settingsForm.data.programme_duration_years}
                            onChange={(e) =>
                                settingsForm.setData(
                                    'programme_duration_years',
                                    e.target.value,
                                )
                            }
                            className="h-11 w-full rounded-xl border-[1.5px] border-border-input bg-input-bg px-3.5 text-sm text-ink"
                        />
                    </Field>

                    <Field label="Institution name">
                        <input
                            value={settingsForm.data.institution_name}
                            onChange={(e) =>
                                settingsForm.setData(
                                    'institution_name',
                                    e.target.value,
                                )
                            }
                            className="h-11 w-full rounded-xl border-[1.5px] border-border-input bg-input-bg px-3.5 text-sm text-ink"
                        />
                    </Field>

                    <Field label="Faculty name">
                        <input
                            value={settingsForm.data.faculty_name}
                            onChange={(e) =>
                                settingsForm.setData(
                                    'faculty_name',
                                    e.target.value,
                                )
                            }
                            className="h-11 w-full rounded-xl border-[1.5px] border-border-input bg-input-bg px-3.5 text-sm text-ink"
                        />
                    </Field>

                    <Field label="Department name">
                        <input
                            value={settingsForm.data.department_name}
                            onChange={(e) =>
                                settingsForm.setData(
                                    'department_name',
                                    e.target.value,
                                )
                            }
                            className="h-11 w-full rounded-xl border-[1.5px] border-border-input bg-input-bg px-3.5 text-sm text-ink"
                        />
                    </Field>

                    <Field label="Programme name">
                        <input
                            value={settingsForm.data.programme_name}
                            onChange={(e) =>
                                settingsForm.setData(
                                    'programme_name',
                                    e.target.value,
                                )
                            }
                            className="h-11 w-full rounded-xl border-[1.5px] border-border-input bg-input-bg px-3.5 text-sm text-ink"
                        />
                    </Field>

                    <div className="mt-1 border-t border-border pt-3.5 text-[13px] font-bold text-ink">
                        Signatures for the result sheet
                    </div>

                    <Field label="Head of Department signature">
                        <div className="flex items-center gap-3">
                            {hodPreview ? (
                                <img
                                    src={hodPreview}
                                    alt="Head of Department signature preview"
                                    className="h-14 w-28 flex-none rounded-lg border border-border-input bg-white object-contain p-1"
                                />
                            ) : (
                                <div className="flex h-14 w-28 flex-none items-center justify-center rounded-lg border border-dashed border-border-input bg-input-bg text-center text-[10px] text-faint">
                                    No signature
                                </div>
                            )}
                            <input
                                type="file"
                                accept="image/*"
                                onChange={onHodSignatureChange}
                                className="min-w-0 flex-1 text-[13px] text-body file:mr-3 file:rounded-lg file:border-0 file:bg-tint file:px-3 file:py-2 file:text-[12.5px] file:font-semibold file:text-primary"
                            />
                        </div>
                    </Field>
                    {settingsForm.errors.hod_signature && (
                        <FieldError message={settingsForm.errors.hod_signature} />
                    )}

                    <Field label="Exam Officer signature">
                        <div className="flex items-center gap-3">
                            {examOfficerPreview ? (
                                <img
                                    src={examOfficerPreview}
                                    alt="Exam Officer signature preview"
                                    className="h-14 w-28 flex-none rounded-lg border border-border-input bg-white object-contain p-1"
                                />
                            ) : (
                                <div className="flex h-14 w-28 flex-none items-center justify-center rounded-lg border border-dashed border-border-input bg-input-bg text-center text-[10px] text-faint">
                                    No signature
                                </div>
                            )}
                            <input
                                type="file"
                                accept="image/*"
                                onChange={onExamOfficerSignatureChange}
                                className="min-w-0 flex-1 text-[13px] text-body file:mr-3 file:rounded-lg file:border-0 file:bg-tint file:px-3 file:py-2 file:text-[12.5px] file:font-semibold file:text-primary"
                            />
                        </div>
                    </Field>
                    {settingsForm.errors.exam_officer_signature && (
                        <FieldError
                            message={settingsForm.errors.exam_officer_signature}
                        />
                    )}

                    <button
                        type="submit"
                        disabled={settingsForm.processing}
                        className="h-11 rounded-xl bg-primary text-[13px] font-bold text-white hover:bg-primary-hover disabled:opacity-70"
                    >
                        Save settings
                    </button>
                </form>
            </Section>

            <Section title="Academic sessions">
                <div className="flex flex-col gap-2">
                    {sessions.map((session) => (
                        <div
                            key={session.id}
                            className="rounded-xl border border-border px-3.5 py-3"
                        >
                            <div className="flex items-center justify-between gap-2">
                                <div className="text-[13.5px] font-bold text-ink">
                                    {session.name}
                                </div>
                                {session.is_current ? (
                                    <span className="rounded-full bg-success-bg px-2.5 py-1 text-[10.5px] font-bold text-success">
                                        Current
                                    </span>
                                ) : (
                                    <button
                                        type="button"
                                        onClick={() =>
                                            router.post(
                                                route(
                                                    'admin.settings.sessions.set-current',
                                                    session.id,
                                                ),
                                                {},
                                                { preserveScroll: true },
                                            )
                                        }
                                        className="rounded-full border-[1.5px] border-primary px-2.5 py-1 text-[10.5px] font-bold text-primary"
                                    >
                                        Set current
                                    </button>
                                )}
                            </div>
                            {session.levels.length > 0 && (
                                <div className="mt-1.5 text-[11px] text-faint">
                                    {session.levels
                                        .map(
                                            (l) =>
                                                `${l.level}L: ${l.count}`,
                                        )
                                        .join(' · ')}
                                </div>
                            )}
                        </div>
                    ))}
                </div>

                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        sessionForm.post(
                            route('admin.settings.sessions.store'),
                            {
                                preserveScroll: true,
                                onSuccess: () => sessionForm.reset(),
                            },
                        );
                    }}
                    className="mt-3 flex gap-2"
                >
                    <input
                        value={sessionForm.data.name}
                        onChange={(e) =>
                            sessionForm.setData('name', e.target.value)
                        }
                        placeholder="e.g. 2026/2027"
                        className="h-11 min-w-0 flex-1 rounded-xl border-[1.5px] border-border-input bg-input-bg px-3.5 text-sm text-ink"
                    />
                    <label className="flex items-center gap-1.5 text-xs text-muted">
                        <input
                            type="checkbox"
                            checked={sessionForm.data.is_current}
                            onChange={(e) =>
                                sessionForm.setData(
                                    'is_current',
                                    e.target.checked,
                                )
                            }
                        />
                        Current
                    </label>
                    <button
                        type="submit"
                        disabled={sessionForm.processing}
                        className="h-11 rounded-xl bg-primary px-4 text-[13px] font-bold text-white hover:bg-primary-hover disabled:opacity-70"
                    >
                        Add
                    </button>
                </form>
                {sessionForm.errors.name && (
                    <div className="mt-1.5 rounded-[10px] bg-error-bg px-3 py-2 text-[13px] text-error">
                        {sessionForm.errors.name}
                    </div>
                )}
            </Section>

            <Section title="Advance a cohort">
                <div className="mb-2 text-[11.5px] text-faint">
                    Move every active student at a given level in one session
                    up one level into another session — run this once a year
                    when a new session begins.
                </div>

                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        advanceForm.post(
                            route('admin.settings.advance-cohort'),
                            { preserveScroll: true },
                        );
                    }}
                    className="flex flex-col gap-2.5"
                >
                    <Field label="From session">
                        <select
                            value={advanceForm.data.from_session_id}
                            onChange={(e) =>
                                advanceForm.setData(
                                    'from_session_id',
                                    e.target.value,
                                )
                            }
                            className="h-11 w-full rounded-xl border-[1.5px] border-border-input bg-input-bg px-2.5 text-sm text-ink"
                        >
                            {sessions.map((s) => (
                                <option key={s.id} value={s.id}>
                                    {s.name}
                                </option>
                            ))}
                        </select>
                    </Field>

                    <Field label="From level">
                        <select
                            value={advanceForm.data.from_level}
                            onChange={(e) =>
                                advanceForm.setData(
                                    'from_level',
                                    e.target.value,
                                )
                            }
                            className="h-11 w-full rounded-xl border-[1.5px] border-border-input bg-input-bg px-2.5 text-sm text-ink"
                        >
                            {levelOptions.map((level) => (
                                <option key={level} value={level}>
                                    {level} Level → {level + 100} Level
                                </option>
                            ))}
                        </select>
                    </Field>

                    <Field label="To session">
                        <select
                            value={advanceForm.data.to_session_id}
                            onChange={(e) =>
                                advanceForm.setData(
                                    'to_session_id',
                                    e.target.value,
                                )
                            }
                            className="h-11 w-full rounded-xl border-[1.5px] border-border-input bg-input-bg px-2.5 text-sm text-ink"
                        >
                            {sessions.map((s) => (
                                <option key={s.id} value={s.id}>
                                    {s.name}
                                </option>
                            ))}
                        </select>
                    </Field>

                    <button
                        type="submit"
                        disabled={advanceForm.processing}
                        className="h-11 rounded-xl bg-primary text-[13px] font-bold text-white hover:bg-primary-hover disabled:opacity-70"
                    >
                        Advance cohort
                    </button>
                </form>
            </Section>
        </div>
    );
}

function Section({ title, children }) {
    return (
        <div className="rounded-[18px] border border-border bg-white px-[18px] py-5">
            <div className="mb-3 text-[15px] font-bold text-ink">{title}</div>
            {children}
        </div>
    );
}

function Field({ label, children }) {
    return (
        <div>
            <label className="mb-1.5 block text-[11.5px] font-semibold uppercase tracking-[.4px] text-body">
                {label}
            </label>
            {children}
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

Index.layout = (page) => <AdminLayout active="settings">{page}</AdminLayout>;
