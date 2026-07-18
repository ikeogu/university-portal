import AdminLayout from '@/Layouts/AdminLayout';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import { useEffect, useState } from 'react';

const TYPE_CHIPS = [
    { key: 'students', label: 'Students' },
    { key: 'lecturers', label: 'Lecturers' },
    { key: 'courses', label: 'Courses' },
    { key: 'scores', label: 'Semester scores' },
    { key: 'lecturer_sheet', label: "Lecturer's mark sheet" },
];

const TITLES = {
    students: {
        title: 'Bulk upload students',
        hint: '.xlsx or .csv · one row per student with full bio-data',
    },
    lecturers: {
        title: 'Bulk upload lecturers',
        hint: '.xlsx or .csv · course codes may be listed for auto-assignment',
    },
    courses: {
        title: 'Bulk upload courses',
        hint: '.xlsx or .csv · codes, titles, credit units and level',
    },
    scores: {
        title: 'Upload soft-copy results',
        hint: 'Choose a session, level and semester first',
    },
    lecturer_sheet: {
        title: "Upload a lecturer's mark sheet",
        hint: 'The official course examination mark sheet, exactly as the lecturer sends it — course, session and marks are all read straight from the file',
    },
};

export default function Index({ sessions }) {
    const [uploadType, setUploadType] = useState('students');
    const [stage, setStage] = useState(0);
    const [file, setFile] = useState(null);
    const [preview, setPreview] = useState(null);
    const [result, setResult] = useState(null);
    const [error, setError] = useState('');

    const [sessionId, setSessionId] = useState(sessions[0]?.id ?? '');
    const [level, setLevel] = useState(400);
    const [semester, setSemester] = useState(1);
    const targetChosen = uploadType !== 'scores' || (sessionId && level && semester);

    const reset = () => {
        setStage(0);
        setFile(null);
        setPreview(null);
        setResult(null);
        setError('');
    };

    const chooseFile = async (e) => {
        const chosen = e.target.files[0];
        if (!chosen) return;
        setFile(chosen);
        setError('');

        const form = new FormData();
        form.append('type', uploadType);
        form.append('file', chosen);
        if (uploadType === 'scores') {
            form.append('level', level);
            form.append('semester', semester);
        }

        try {
            const { data } = await axios.post(
                route('admin.upload.preview'),
                form,
            );
            setPreview(data);
            setStage(1);
        } catch (err) {
            setError(
                err.response?.data?.message ??
                    'Could not read that file. Check the format and try again.',
            );
        }
    };

    const process = async () => {
        setStage(3);
        try {
            const { data } = await axios.post(route('admin.upload.process'), {
                type: uploadType,
                token: preview.token,
                session_id: sessionId,
                level,
                semester,
            });
            setResult(data);
            setStage(2);
        } catch (err) {
            setError(
                err.response?.data?.message ?? 'Processing failed.',
            );
            setStage(1);
        }
    };

    return (
        <div className="flex-1 px-4 pt-4 pb-6 md:mx-auto md:w-full md:max-w-xl md:px-8">
            <Head title="Bulk upload" />

            <div className="text-[15px] font-extrabold text-ink">
                Bulk upload
            </div>
            <div className="mb-3.5 mt-1 text-xs text-muted">
                Students, lecturers, courses and semester scores can all be
                uploaded as spreadsheets.
            </div>

            <div className="mb-3.5 flex flex-wrap gap-1.5">
                {TYPE_CHIPS.map((chip) => (
                    <button
                        key={chip.key}
                        type="button"
                        onClick={() => {
                            setUploadType(chip.key);
                            reset();
                        }}
                        className={
                            'rounded-full border-[1.5px] px-3 py-1.5 text-[11.5px] font-bold ' +
                            (uploadType === chip.key
                                ? 'border-primary bg-primary text-white'
                                : 'border-border-input bg-white text-muted')
                        }
                    >
                        {chip.label}
                    </button>
                ))}
            </div>

            {uploadType === 'scores' && stage === 0 && (
                <div className="mb-3.5 flex flex-col gap-2.5 rounded-2xl border border-border bg-white p-3.5">
                    <TargetField label="Session">
                        <select
                            value={sessionId}
                            onChange={(e) => setSessionId(e.target.value)}
                            className="h-11 w-full rounded-xl border-[1.5px] border-border-input bg-input-bg px-2.5 text-sm text-ink"
                        >
                            {sessions.map((s) => (
                                <option key={s.id} value={s.id}>
                                    {s.name}
                                </option>
                            ))}
                        </select>
                    </TargetField>
                    <div className="grid grid-cols-2 gap-2.5">
                        <TargetField label="Level">
                            <select
                                value={level}
                                onChange={(e) =>
                                    setLevel(Number(e.target.value))
                                }
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
                                value={semester}
                                onChange={(e) =>
                                    setSemester(Number(e.target.value))
                                }
                                className="h-11 w-full rounded-xl border-[1.5px] border-border-input bg-input-bg px-2.5 text-sm text-ink"
                            >
                                <option value={1}>First</option>
                                <option value={2}>Second</option>
                            </select>
                        </TargetField>
                    </div>
                </div>
            )}

            {error && (
                <div className="mb-3.5 rounded-[10px] bg-error-bg px-3 py-2.5 text-[13px] text-error">
                    {error}
                </div>
            )}

            {stage === 0 && targetChosen && (
                <div className="rounded-2xl border-2 border-dashed border-[#b9c4da] bg-white p-8 text-center">
                    <div className="mx-auto mb-3 flex h-[46px] w-[46px] items-center justify-center rounded-full bg-tint text-xl font-extrabold text-primary">
                        ↑
                    </div>
                    <div className="text-[13.5px] font-bold text-ink">
                        {TITLES[uploadType].title}
                    </div>
                    <div className="mt-1 text-[11.5px] text-faint">
                        {TITLES[uploadType].hint}
                    </div>
                    <a
                        href={route('admin.upload.sample', uploadType)}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="mb-4 mt-2 inline-flex items-center gap-1 text-[12px] font-bold text-primary hover:underline"
                    >
                        ⬇ Download sample file
                    </a>
                    <label className="inline-flex h-[42px] cursor-pointer items-center rounded-[11px] bg-primary px-[22px] text-[13px] font-bold text-white hover:bg-primary-hover">
                        Choose file
                        <input
                            type="file"
                            accept=".xlsx,.xls,.csv"
                            onChange={chooseFile}
                            className="hidden"
                        />
                    </label>
                </div>
            )}

            {stage === 1 && preview && (
                <div className="rounded-2xl border border-border bg-white p-4">
                    <div className="flex items-center gap-2.5">
                        <div className="flex h-[38px] w-[38px] flex-none items-center justify-center rounded-[10px] bg-tint text-[11px] font-extrabold text-primary">
                            XLSX
                        </div>
                        <div className="min-w-0">
                            <div className="truncate text-[13px] font-bold text-ink">
                                {preview.fileName}
                            </div>
                            <div className="text-[11px] text-faint">
                                {preview.rowCount} rows detected
                            </div>
                        </div>
                    </div>

                    {preview.detectedCourse && (
                        <div className="mt-3.5 rounded-xl border border-[#d3e3d3] bg-success-bg/40 p-3">
                            <div className="text-[11px] font-bold uppercase tracking-[.4px] text-success">
                                Detected from file
                            </div>
                            <div className="mt-1 text-[12.5px] font-bold text-ink">
                                {preview.detectedCourse.code} —{' '}
                                {preview.detectedCourse.title}
                            </div>
                            <div className="mt-0.5 text-[11px] text-muted">
                                {preview.detectedCourse.semester} semester ·{' '}
                                {preview.detectedSession.name}
                            </div>
                        </div>
                    )}

                    <div className="mb-2 mt-3.5 text-[11px] font-bold uppercase tracking-[.4px] text-muted">
                        Column mapping
                    </div>
                    <div className="flex flex-wrap gap-1.5">
                        {preview.columnMap.map((mapping) => (
                            <span
                                key={mapping}
                                className="rounded-lg bg-table-head px-2.5 py-1 text-[11px] font-semibold text-ink"
                            >
                                {mapping}
                            </span>
                        ))}
                    </div>

                    <button
                        type="button"
                        onClick={process}
                        className="mt-4 h-[46px] w-full rounded-xl bg-primary text-sm font-bold text-white hover:bg-primary-hover"
                    >
                        Process file
                    </button>
                </div>
            )}

            {stage === 3 && (
                <div className="animate-popin rounded-2xl border border-border bg-white p-8 text-center">
                    <div className="mx-auto mb-3.5 h-10 w-10 animate-spin rounded-full border-[3.5px] border-border border-t-primary" />
                    <div className="text-[13.5px] font-bold text-ink">
                        Processing {preview?.fileName}
                    </div>
                    <div className="mt-1 text-[11.5px] text-faint">
                        Validating rows and computing grades…
                    </div>
                </div>
            )}

            {stage === 2 && result && (
                <div className="animate-popin rounded-2xl border border-[#d3e8dc] bg-white p-6 text-center">
                    <div className="mx-auto mb-3 flex h-[50px] w-[50px] items-center justify-center rounded-full bg-success-bg text-2xl font-extrabold text-success">
                        ✓
                    </div>
                    <div className="text-[15px] font-extrabold text-ink">
                        Upload complete
                    </div>
                    <div className="mb-4 mt-1.5 text-[12.5px] leading-relaxed text-muted">
                        {result.added} record(s) added
                        {result.skipped > 0 &&
                            ` (${result.skipped} duplicate(s) skipped)`}
                        .
                        {result.errors.length > 0 &&
                            ` ${result.errors.length} row(s) had errors.`}
                        {result.warnings?.length > 0 &&
                            ` ${result.warnings.length} row(s) had warnings.`}
                    </div>

                    {(result.errors.length > 0 || result.warnings?.length > 0) && (
                        <div className="mb-4 space-y-1 text-left text-[11px] text-faint">
                            {result.errors.map((e) => (
                                <div key={`err-${e.row}`}>
                                    Row {e.row}: {e.message}
                                </div>
                            ))}
                            {result.warnings?.map((w) => (
                                <div key={`warn-${w.row}`}>
                                    Row {w.row}: {w.message}
                                </div>
                            ))}
                        </div>
                    )}

                    {result.pins?.length > 0 && (
                        <div className="mb-4 rounded-xl border border-[#d3e8dc] bg-success-bg/40 p-3.5 text-left">
                            <div className="text-[12.5px] font-bold text-ink">
                                Access PINs generated
                            </div>
                            <div className="mt-1 text-[11px] leading-relaxed text-muted">
                                Each new student needs their PIN (with their
                                mat_no) to check their result. They're shown
                                only this once — download and hand them out.
                            </div>
                            <button
                                type="button"
                                onClick={() => downloadPins(result.pins)}
                                className="mt-2.5 h-9 rounded-lg bg-primary px-3.5 text-[12px] font-bold text-white hover:bg-primary-hover"
                            >
                                Download PINs (CSV)
                            </button>
                        </div>
                    )}

                    <button
                        type="button"
                        onClick={reset}
                        className="h-10 text-xs font-semibold text-muted"
                    >
                        Upload another file
                    </button>
                </div>
            )}
        </div>
    );
}

function downloadPins(pins) {
    const header = 'mat_no,name,pin\n';
    const rows = pins
        .map((p) => `${p.mat_no},"${p.name}",${p.pin}`)
        .join('\n');

    const blob = new Blob([header + rows], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'student-pins.csv';
    link.click();
    URL.revokeObjectURL(url);
}

function TargetField({ label, children }) {
    return (
        <div>
            <label className="mb-1.5 block text-[11px] font-semibold uppercase tracking-[.4px] text-faint">
                {label}
            </label>
            {children}
        </div>
    );
}

Index.layout = (page) => <AdminLayout active="upload">{page}</AdminLayout>;
