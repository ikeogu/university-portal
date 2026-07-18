import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router } from '@inertiajs/react';

const CLASS_COLORS = {
    first_class: '#0a7d4f',
    second_upper: '#15697c',
    second_lower: '#22407e',
    third: '#8a5b00',
    pass: '#a34c0a',
    fail: '#b3261e',
};

const CLASS_LABELS = {
    first_class: 'First Class',
    second_upper: '2nd Class Upper',
    second_lower: '2nd Class Lower',
    third: '3rd Class',
    pass: 'Pass',
    fail: 'Fail',
};

export default function Index({ sets, selectedSet, rows, classDistribution }) {
    const total = Object.values(classDistribution).reduce((a, b) => a + b, 0);

    return (
        <div className="flex-1 px-4 pt-4 pb-6 md:px-8">
            <Head title="Master mark sheet" />

            <div className="flex items-start justify-between gap-2.5">
                <div>
                    <div className="text-[15px] font-extrabold text-ink">
                        Master mark sheet
                    </div>
                    <div className="mt-0.5 text-xs text-muted">
                        Eligible graduating students
                    </div>
                </div>
                <a
                    href={route('admin.master.print')}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="flex h-10 items-center whitespace-nowrap rounded-[11px] bg-primary px-3.5 text-[12.5px] font-bold text-white hover:bg-primary-hover"
                >
                    Print sheet
                </a>
            </div>

            <div className="mb-2.5 mt-3.5 flex flex-wrap gap-1.5">
                {sets.map((set) => (
                    <button
                        key={set}
                        type="button"
                        onClick={() =>
                            router.get(
                                route('admin.master.index'),
                                { set },
                                { preserveState: true, replace: true },
                            )
                        }
                        className={
                            'rounded-full border-[1.5px] px-3.5 py-1.5 text-[11.5px] font-bold ' +
                            (set === selectedSet
                                ? 'border-primary bg-primary text-white'
                                : 'border-border-input bg-white text-body')
                        }
                    >
                        {set} SET
                    </button>
                ))}
                {sets.length === 0 && (
                    <div className="text-xs text-faint">
                        No students have reached the graduating level yet.
                    </div>
                )}
            </div>

            {total > 0 && (
                <>
                    <div className="mb-2.5 flex h-2.5 overflow-hidden rounded-full bg-page">
                        {Object.entries(classDistribution)
                            .filter(([, n]) => n > 0)
                            .map(([key, n]) => (
                                <div
                                    key={key}
                                    className="animate-barin"
                                    style={{
                                        flexGrow: n,
                                        flexBasis: 0,
                                        background: CLASS_COLORS[key],
                                    }}
                                />
                            ))}
                    </div>

                    <div className="mb-3.5 flex flex-wrap gap-1.5">
                        {Object.entries(classDistribution).map(([key, n]) => (
                            <span
                                key={key}
                                className="rounded-full border border-border bg-white px-2.5 py-1 text-[11px] font-bold text-ink"
                            >
                                {CLASS_LABELS[key]}: {n}
                            </span>
                        ))}
                    </div>
                </>
            )}

            <div className="overflow-hidden rounded-2xl border border-border bg-white">
                <div className="grid grid-cols-[26px_1fr_52px_96px] gap-2 bg-table-head px-3.5 py-2.5 text-[10px] font-bold uppercase tracking-[.4px] text-muted">
                    <div>#</div>
                    <div>Student</div>
                    <div className="text-center">CGPA</div>
                    <div>Class</div>
                </div>

                {rows.map((row) => (
                    <div
                        key={row.mat_no}
                        className="grid grid-cols-[26px_1fr_52px_96px] items-center gap-2 border-t border-[#eef1f7] px-3.5 py-2.5"
                    >
                        <div className="text-[11.5px] text-faint">
                            {row.sn}
                        </div>
                        <div className="min-w-0">
                            <div className="truncate text-[12.5px] font-bold text-ink">
                                {row.name}
                            </div>
                            <div className="text-[10.5px] text-faint">
                                {row.mat_no}
                            </div>
                        </div>
                        <div className="text-center text-[13px] font-extrabold text-primary">
                            {row.cgpa.toFixed(2)}
                        </div>
                        <div className="text-[11px] font-bold text-body">
                            {row.class}
                        </div>
                    </div>
                ))}

                {rows.length === 0 && (
                    <div className="p-[22px] text-center text-[12.5px] text-faint">
                        No students in this set yet.
                    </div>
                )}
            </div>

            <div className="mt-2.5 text-center text-[11px] text-faint2">
                Printed sheet lists all sets in batches with the overall
                summary.
            </div>
        </div>
    );
}

Index.layout = (page) => <AdminLayout active="master">{page}</AdminLayout>;
