import MobileShell from '@/Layouts/MobileShell';
import { SHELL_WIDTH_WIDE } from '@/lib/layout';
import { Link, usePage } from '@inertiajs/react';

const TABS = [
    { key: 'students', label: 'Students', route: 'admin.students.index' },
    { key: 'lecturers', label: 'Lecturers', route: 'admin.lecturers.index' },
    { key: 'courses', label: 'Courses', route: 'admin.courses.index' },
    { key: 'upload', label: 'Upload', route: 'admin.upload.index' },
    { key: 'master', label: 'Master', route: 'admin.master.index' },
];

export default function AdminLayout({ active, children }) {
    const { auth } = usePage().props;

    return (
        <MobileShell width="wide">
            <div className="flex flex-1 flex-col pb-[70px]">
                <div className="flex items-start justify-between gap-3 bg-navy px-[22px] pb-6 pt-[22px] text-white md:px-8">
                    <div>
                        <div className="text-[17px] font-extrabold">
                            {auth.user.name}
                        </div>
                        <span className="mt-[7px] inline-block rounded-full bg-white/[.14] px-3 py-1 text-[11px] font-bold">
                            {auth.user.roleLabel}
                        </span>
                    </div>
                    <div className="flex items-center gap-2">
                        <Link
                            href={route('admin.settings.index')}
                            className={
                                'flex h-[34px] w-[34px] items-center justify-center rounded-[10px] border text-base ' +
                                (active === 'settings'
                                    ? 'border-white bg-white/[.14]'
                                    : 'border-white/35')
                            }
                            title="Sessions & settings"
                        >
                            ⚙
                        </Link>
                        <Link
                            href={route('logout')}
                            method="post"
                            as="button"
                            className="h-[34px] rounded-[10px] border border-white/35 px-3.5 text-xs font-semibold text-white"
                        >
                            Sign out
                        </Link>
                    </div>
                </div>

                <div className="flex flex-1 animate-screenin flex-col">
                    {children}
                </div>
            </div>

            <div
                className={`fixed bottom-0 left-1/2 grid w-full ${SHELL_WIDTH_WIDE} -translate-x-1/2 grid-cols-5 border-t border-border bg-white`}
            >
                {TABS.map((tab) => (
                    <Link
                        key={tab.key}
                        href={route(tab.route)}
                        className={
                            'flex h-14 items-center justify-center border-t-[2.5px] text-[11px] font-bold md:text-[13px] ' +
                            (active === tab.key
                                ? 'border-primary text-primary'
                                : 'border-transparent text-faint')
                        }
                    >
                        {tab.label}
                    </Link>
                ))}
            </div>
        </MobileShell>
    );
}
