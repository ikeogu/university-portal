import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function PinReveal() {
    const { pinReveal } = usePage().props.flash ?? {};
    const [reveal, setReveal] = useState(null);

    useEffect(() => {
        if (pinReveal) {
            setReveal(pinReveal);
        }
    }, [pinReveal]);

    if (!reveal) {
        return null;
    }

    return (
        <div
            data-noprint
            className="fixed inset-0 z-[95] flex items-center justify-center bg-ink/50 p-4"
        >
            <div className="w-full max-w-[320px] rounded-[20px] bg-white p-6 text-center shadow-[0_20px_60px_rgba(18,32,66,.35)]">
                <div className="text-[13px] font-bold text-ink">
                    {reveal.name}&apos;s access PIN
                </div>
                <div className="mt-0.5 text-[11.5px] text-faint">
                    {reveal.mat_no}
                </div>
                <div className="mt-4 rounded-xl bg-tint px-4 py-3.5 text-[28px] font-extrabold tracking-[6px] text-primary">
                    {reveal.pin}
                </div>
                <div className="mt-3 text-[11.5px] leading-relaxed text-error">
                    Write this down now — it will not be shown again. The
                    student needs their mat_no + this PIN to check their
                    result.
                </div>
                <button
                    type="button"
                    onClick={() => setReveal(null)}
                    className="mt-4 h-11 w-full rounded-xl bg-primary text-sm font-bold text-white hover:bg-primary-hover"
                >
                    I&apos;ve noted this down
                </button>
            </div>
        </div>
    );
}
