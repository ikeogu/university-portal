import PrintToolbar from '@/Components/Print/PrintToolbar';
import { Head } from '@inertiajs/react';

export default function Statement({ student, session, institution, sem1, sem2, cgpa }) {
    return (
        <div id="printdoc" className="min-h-screen bg-[#dfe4ee]">
            <Head title="Statement of Academic Results" />

            <PrintToolbar
                title="Statement of Student's Academic Results"
                hint="A4 portrait"
            />

            <div
                className="mx-auto my-6 max-w-[794px] bg-white p-11 text-[#111] shadow-[0_6px_30px_rgba(18,32,66,.18)]"
                style={{ fontFamily: "'Times New Roman', Times, serif" }}
            >
                <div className="border-b-[2.5px] border-[#111] pb-3 text-center">
                    <div className="text-xl font-bold tracking-[1px]">
                        {institution.name.toUpperCase()}
                    </div>
                    <div className="mt-1 text-[13px]">
                        {institution.faculty.toUpperCase()} ·{' '}
                        {institution.department.toUpperCase()}
                    </div>
                    <div className="mt-2 text-sm font-bold underline">
                        STATEMENT OF STUDENT&apos;S ACADEMIC RESULTS
                    </div>
                </div>

                <div className="mt-3.5 flex gap-4">
                    <div className="grid flex-1 grid-cols-2 gap-x-6 gap-y-1 text-[12.5px]">
                        <div>
                            <b>FULL NAMES:</b> {student.name}
                        </div>
                        <div>
                            <b>DATE OF BIRTH:</b> {student.dob}
                        </div>
                        <div>
                            <b>MATRICULATION NUMBER:</b> {student.mat_no}
                        </div>
                        <div>
                            <b>STATE OF ORIGIN:</b>{' '}
                            {student.state_of_origin.toUpperCase()}
                        </div>
                        <div>
                            <b>Programme:</b>{' '}
                            {institution.programme.toUpperCase()}
                        </div>
                        <div>
                            <b>Mode of Study:</b>{' '}
                            {student.mode_of_study.toUpperCase()}
                        </div>
                        <div>
                            <b>Marital Status:</b>{' '}
                            {student.marital_status.toUpperCase()}
                        </div>
                        <div>
                            <b>Academic Year:</b> {session.name}
                        </div>
                    </div>

                    {student.photo_url && (
                        <img
                            src={student.photo_url}
                            alt={student.name}
                            className="h-[110px] w-[90px] flex-none border border-[#111] object-cover"
                        />
                    )}
                </div>

                <div className="mt-2.5 border border-[#999] bg-[#f7f7f7] p-2 text-[10.5px]">
                    KEY: CU = Course Unit, GP = Grade Point, QP = Quality
                    Point. Course codes highlighted indicate failed courses.
                    Grading: A (70–100) = 5, B (60–69) = 4, C (50–59) = 3, D
                    (45–49) = 2, E (40–44) = 1, F (0–39) = 0.
                </div>

                <SemesterTable label="SEMESTER I" result={sem1} />
                <SemesterTable label="SEMESTER II" result={sem2} />

                <div className="mt-2.5 border-t-[1.5px] border-[#111] pt-2 text-[13px]">
                    Cumulative Grade Point Average (CGPA) :{' '}
                    <b>{cgpa.toFixed(2)}</b>
                </div>

                <div className="mt-11 grid grid-cols-2 gap-6 text-[12px]">
                    <div>
                        {institution.examOfficerSignatureUrl && (
                            <img
                                src={institution.examOfficerSignatureUrl}
                                alt="Exam Officer signature"
                                className="h-11 object-contain object-left"
                            />
                        )}
                        <div className="border-t border-dotted border-[#111] pt-1.5">
                            Exam Officer — Name, Sign &amp; Date
                        </div>
                    </div>
                    <div>
                        {institution.hodSignatureUrl && (
                            <img
                                src={institution.hodSignatureUrl}
                                alt="Head of Department signature"
                                className="h-11 object-contain object-left"
                            />
                        )}
                        <div className="border-t border-dotted border-[#111] pt-1.5">
                            Head of Department — Name, Sign &amp; Date
                        </div>
                    </div>
                </div>

                <div className="mt-[22px] text-center text-[9.5px] text-[#333]">
                    DISCLAIMER: THIS DOCUMENT IS FOR THE STUDENT&apos;S
                    PERSONAL USE ONLY. IT DOES NOT REPRESENT AN OFFICIAL
                    TRANSCRIPT OF {institution.name.toUpperCase()}.
                </div>
            </div>
        </div>
    );
}

function SemesterTable({ label, result }) {
    return (
        <>
            <div className="mt-4 mb-1.5 text-[13px] font-bold">{label}</div>
            <table className="w-full border-collapse border border-[#333] text-[11px]">
                <thead>
                    <tr className="bg-[#efefef]">
                        <th className="border-b border-r border-[#333] px-1.5 py-1 text-left font-bold">
                            CODE
                        </th>
                        <th className="border-b border-r border-[#333] px-1.5 py-1 text-left font-bold">
                            TITLE OF COURSE
                        </th>
                        <th className="border-b border-r border-[#333] px-1 py-1 font-bold">
                            CU
                        </th>
                        <th className="border-b border-r border-[#333] px-1 py-1 font-bold">
                            CA
                        </th>
                        <th className="border-b border-r border-[#333] px-1 py-1 font-bold">
                            EXAM
                        </th>
                        <th className="border-b border-r border-[#333] px-1 py-1 font-bold">
                            MARK
                        </th>
                        <th className="border-b border-r border-[#333] px-1 py-1 font-bold">
                            GRADE
                        </th>
                        <th className="border-b border-r border-[#333] px-1 py-1 font-bold">
                            GP
                        </th>
                        <th className="border-b border-[#333] px-1 py-1 font-bold">
                            QP
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {result.rows.map((row) => (
                        <tr key={row.code} className="border-b border-[#bbb]">
                            <td className="border-r border-[#bbb] px-1.5 py-1 font-bold">
                                {row.code}
                            </td>
                            <td className="border-r border-[#bbb] px-1.5 py-1">
                                {row.title.toUpperCase()}
                            </td>
                            <td className="border-r border-[#bbb] px-1 py-1 text-center">
                                {row.cu}
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
                                className="border-r border-[#bbb] px-1 py-1 text-center font-bold"
                                style={{
                                    color:
                                        row.grade === 'F' ? '#b3261e' : '#111',
                                }}
                            >
                                {row.grade ?? '—'}
                            </td>
                            <td className="border-r border-[#bbb] px-1 py-1 text-center">
                                {row.gp ?? '—'}
                            </td>
                            <td className="px-1 py-1 text-center">
                                {row.qp ?? '—'}
                            </td>
                        </tr>
                    ))}
                    {result.rows.length === 0 && (
                        <tr>
                            <td
                                colSpan={9}
                                className="p-2 text-center text-[#999]"
                            >
                                No courses registered.
                            </td>
                        </tr>
                    )}
                </tbody>
            </table>
            <div className="mt-1.5 text-[12px] leading-[1.6]">
                Total Course Unit (TCU) : <b>{result.tcu}</b> &nbsp;·&nbsp;
                Total Quality Point (TQP) : <b>{result.tqp}</b> &nbsp;·&nbsp;
                Semester GPA : <b>{result.gpa.toFixed(2)}</b>
            </div>
        </>
    );
}
