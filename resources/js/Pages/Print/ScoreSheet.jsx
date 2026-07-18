import PrintToolbar from '@/Components/Print/PrintToolbar';
import { Head } from '@inertiajs/react';

export default function ScoreSheet({ course, session, lecturerName, rows }) {
    return (
        <div id="printdoc" className="min-h-screen bg-[#dfe4ee]">
            <Head title={`${course.code} — Course Score Sheet`} />

            <PrintToolbar
                title={`Course Score Sheet — ${course.code}`}
                hint="A4 portrait"
            />

            <div
                className="mx-auto my-6 max-w-[794px] bg-white p-11 text-[#111] shadow-[0_6px_30px_rgba(18,32,66,.18)]"
                style={{ fontFamily: "'Times New Roman', Times, serif" }}
            >
                <div className="border-b-[2.5px] border-[#111] pb-3 text-center">
                    <div className="text-xl font-bold tracking-[1px]">
                        UNITY STATE UNIVERSITY
                    </div>
                    <div className="mt-1 text-[13px]">
                        FACULTY OF COMPUTING · DEPARTMENT OF COMPUTER SCIENCE
                    </div>
                    <div className="mt-2 text-sm font-bold underline">
                        COURSE SCORE SHEET
                    </div>
                </div>

                <div className="mt-3.5 grid grid-cols-2 gap-x-6 gap-y-1 text-[12.5px]">
                    <div>
                        <b>COURSE:</b> {course.code} —{' '}
                        {course.title.toUpperCase()}
                    </div>
                    <div>
                        <b>COURSE UNIT:</b> {course.credit_units}
                    </div>
                    <div>
                        <b>SESSION:</b> {session.name} ·{' '}
                        {course.semester.toUpperCase()} SEMESTER
                    </div>
                    <div>
                        <b>LECTURER:</b> {lecturerName.toUpperCase()}
                    </div>
                </div>

                <table className="mt-3.5 w-full border-collapse border border-[#333] text-[11px]">
                    <thead>
                        <tr className="bg-[#efefef]">
                            <th className="border-b border-r border-[#333] px-1.5 py-1 font-bold">
                                S/N
                            </th>
                            <th className="border-b border-r border-[#333] px-1.5 py-1 text-left font-bold">
                                MAT. NO.
                            </th>
                            <th className="border-b border-r border-[#333] px-1.5 py-1 text-left font-bold">
                                NAME
                            </th>
                            <th className="border-b border-r border-[#333] px-1 py-1 font-bold">
                                CA (30)
                            </th>
                            <th className="border-b border-r border-[#333] px-1 py-1 font-bold">
                                EXAM (70)
                            </th>
                            <th className="border-b border-r border-[#333] px-1 py-1 font-bold">
                                TOTAL
                            </th>
                            <th className="border-b border-[#333] px-1 py-1 font-bold">
                                GRADE
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row) => (
                            <tr key={row.mat_no} className="border-b border-[#bbb]">
                                <td className="border-r border-[#bbb] px-1.5 py-1 text-center">
                                    {row.i}
                                </td>
                                <td className="border-r border-[#bbb] px-1.5 py-1">
                                    {row.mat_no}
                                </td>
                                <td className="border-r border-[#bbb] px-1.5 py-1">
                                    {row.name}
                                </td>
                                <td className="border-r border-[#bbb] px-1 py-1 text-center">
                                    {row.ca ?? '—'}
                                </td>
                                <td className="border-r border-[#bbb] px-1 py-1 text-center">
                                    {row.exam ?? '—'}
                                </td>
                                <td className="border-r border-[#bbb] px-1 py-1 text-center font-bold">
                                    {row.mark ?? '—'}
                                </td>
                                <td
                                    className="px-1 py-1 text-center font-bold"
                                    style={{
                                        color:
                                            row.grade === 'F'
                                                ? '#b3261e'
                                                : '#111',
                                    }}
                                >
                                    {row.grade ?? '—'}
                                </td>
                            </tr>
                        ))}
                        {rows.length === 0 && (
                            <tr>
                                <td
                                    colSpan={7}
                                    className="p-2 text-center text-[#999]"
                                >
                                    No students registered.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>

                <div className="mt-11 grid grid-cols-2 gap-6 text-[12px]">
                    <div className="border-t border-dotted border-[#111] pt-1.5">
                        Course Lecturer — Name, Sign &amp; Date
                    </div>
                    <div className="border-t border-dotted border-[#111] pt-1.5">
                        Head of Department — Name, Sign &amp; Date
                    </div>
                </div>
            </div>
        </div>
    );
}
