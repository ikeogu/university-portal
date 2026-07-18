import PrintToolbar from '@/Components/Print/PrintToolbar';
import { Head } from '@inertiajs/react';

const ROMAN = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];

export default function MasterSheet({
    institution,
    terminalLevel,
    groups,
    summary,
    total,
}) {
    const yearCount = terminalLevel / 100;
    const years = Array.from({ length: yearCount }, (_, i) => (i + 1) * 100);

    return (
        <div id="printdoc" className="print-landscape min-h-screen bg-[#dfe4ee]">
            <Head title="Master Mark Sheet" />

            <PrintToolbar title="Master Mark Sheet — all sets" hint="A4 landscape" />

            <div
                className="mx-auto my-6 w-[1160px] bg-white p-10 text-[#111] shadow-[0_6px_30px_rgba(18,32,66,.18)]"
                style={{ fontFamily: "'Times New Roman', Times, serif" }}
            >
                <div className="border-b-[2.5px] border-[#111] pb-3 text-center">
                    <div className="text-[19px] font-bold tracking-[1px]">
                        {institution.name.toUpperCase()}
                    </div>
                    <div className="mt-1 text-[12.5px]">
                        {institution.faculty.toUpperCase()} ·{' '}
                        {institution.department.toUpperCase()}
                    </div>
                    <div className="mt-2 text-[13.5px] font-bold underline">
                        MASTER MARK SHEET FOR THE AWARD OF{' '}
                        {institution.programme.toUpperCase()} DEGREE
                    </div>
                </div>

                <div className="mt-2.5 mb-3 flex flex-wrap gap-4 text-[11.5px]">
                    <b>Overall Summary:</b>
                    {Object.entries(summary).map(([key, n]) => (
                        <span key={key}>
                            {key.replace('_', ' ')}:{' '}
                            <b>{String(n).padStart(2, '0')}</b>
                        </span>
                    ))}
                    <span>
                        Total: <b>{total}</b>
                    </span>
                </div>

                <table className="w-full border-collapse border border-[#333] text-center text-[10.5px]">
                    <thead>
                        <tr className="bg-[#efefef]">
                            <th className="border-b border-r border-[#333] p-1 font-bold">S/N</th>
                            <th className="border-b border-r border-[#333] p-1 font-bold">MAT. NO.</th>
                            <th className="border-b border-r border-[#333] p-1 text-left font-bold">NAME</th>
                            {years.map((level, i) => (
                                <th key={level} className="border-b border-r border-[#333] p-1 font-bold">
                                    YEAR {ROMAN[i]}
                                    <br />
                                    QP / CU
                                </th>
                            ))}
                            <th className="border-b border-r border-[#333] p-1 font-bold">TQP / TCU</th>
                            <th className="border-b border-r border-[#333] p-1 font-bold">CGPA</th>
                            <th className="border-b border-[#333] p-1 font-bold">CLASS</th>
                        </tr>
                    </thead>
                    {groups.map((group) => (
                        <tbody key={group.label}>
                            <tr>
                                <td
                                    colSpan={4 + years.length}
                                    className="border-b border-[#333] bg-[#f4f4f4] p-1.5 text-left text-[11px] font-bold tracking-[.5px]"
                                >
                                    {group.label}
                                </td>
                            </tr>
                            {group.rows.map((row) => (
                                <tr key={row.mat_no} className="border-b border-[#bbb]">
                                    <td className="border-r border-[#bbb] p-1">{row.sn}</td>
                                    <td className="border-r border-[#bbb] p-1">{row.mat_no}</td>
                                    <td className="border-r border-[#bbb] p-1 text-left">{row.name}</td>
                                    {years.map((level) => (
                                        <td key={level} className="border-r border-[#bbb] p-1">
                                            {row.byLevel[level]
                                                ? `${row.byLevel[level].tqp} / ${row.byLevel[level].tcu}`
                                                : '—'}
                                        </td>
                                    ))}
                                    <td className="border-r border-[#bbb] p-1 font-bold">
                                        {row.tqp} / {row.tcu}
                                    </td>
                                    <td className="border-r border-[#bbb] p-1 font-bold">
                                        {row.cgpa.toFixed(2)}
                                    </td>
                                    <td className="p-1 font-bold">{row.classShort}</td>
                                </tr>
                            ))}
                        </tbody>
                    ))}
                </table>

                <div className="mt-10 grid grid-cols-3 gap-7 text-[11.5px]">
                    <div>
                        <b>Head of Department:</b>
                        <div className="mt-6.5 border-t border-dotted border-[#111] pt-1">
                            Name, Sign &amp; Date
                        </div>
                    </div>
                    <div>
                        <b>Dean of Faculty:</b>
                        <div className="mt-6.5 border-t border-dotted border-[#111] pt-1">
                            Name, Sign &amp; Date
                        </div>
                    </div>
                    <div>
                        <b>External Examiner:</b>
                        <div className="mt-6.5 border-t border-dotted border-[#111] pt-1">
                            Name, Sign &amp; Date
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
