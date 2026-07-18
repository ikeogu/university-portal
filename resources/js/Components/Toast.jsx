import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function Toast() {
    const { toast } = usePage().props.flash ?? {};
    const [message, setMessage] = useState(null);

    useEffect(() => {
        if (!toast) {
            return;
        }

        setMessage(toast);

        const timeout = setTimeout(() => setMessage(null), 2400);

        return () => clearTimeout(timeout);
    }, [toast]);

    if (!message) {
        return null;
    }

    return (
        <div
            data-noprint
            className="fixed bottom-6 left-1/2 z-[80] -translate-x-1/2 animate-toastin rounded-full bg-ink px-5 py-[11px] text-[13px] font-semibold text-white shadow-[0_8px_24px_rgba(18,32,66,.3)]"
        >
            {message}
        </div>
    );
}
