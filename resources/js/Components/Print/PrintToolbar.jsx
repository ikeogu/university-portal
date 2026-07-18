export default function PrintToolbar({ title, hint }) {
    return (
        <div
            data-noprint
            className="sticky top-0 z-[5] flex items-center gap-3 bg-ink px-4 py-2.5 text-white"
        >
            <button
                type="button"
                onClick={() => window.close()}
                className="h-9 rounded-[9px] border border-white/35 px-3.5 text-xs font-semibold text-white"
            >
                &lsaquo; Close
            </button>
            <div className="flex-1 text-[13px] font-bold">{title}</div>
            <div className="text-[11px] opacity-70">{hint}</div>
            <button
                type="button"
                onClick={() => window.print()}
                className="h-9 rounded-[9px] bg-white px-[18px] text-[12.5px] font-bold text-ink"
            >
                Print / Save PDF
            </button>
        </div>
    );
}
