import { Head, Link } from '@inertiajs/react';
import { useRef, useState } from 'react';

const FEATURES = [
    {
        icon: '≡',
        title: 'Instant result statements',
        detail: 'Per-semester GPA and CGPA in the official format, the moment scores are entered.',
    },
    {
        icon: '⎙',
        title: 'Print-ready documents',
        detail: 'Statements, score sheets and the master mark sheet, formatted for print.',
    },
    {
        icon: '▤',
        title: 'Graduating sets in batches',
        detail: 'Eligible students grouped by entry set, ranked on the 5.0 scale.',
    },
];

const STEPS = [
    { icon: '⚙', label: 'Compute' },
    { icon: '⎙', label: 'Print' },
    { icon: '✓', label: 'Graduate' },
];

export default function Landing({ institutionName, facultyName, departmentName }) {
    const subtitle = facultyName ? `${facultyName} · ${departmentName}` : departmentName;

    return (
        <div className="min-h-screen bg-shell text-ink">
            <Head title="Result Portal" />

            <header className="sticky top-0 z-10 border-b border-border bg-white/90 backdrop-blur">
                <div className="mx-auto flex max-w-6xl items-center justify-between px-5 py-3.5 lg:px-8">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 flex-none items-center justify-center rounded-full border-[1.5px] border-navy/25 bg-tint text-[13px] font-extrabold tracking-[.5px] text-navy">
                            US
                        </div>
                        <div className="min-w-0 text-[13px] font-bold leading-tight text-ink">
                            {institutionName}
                            <div className="truncate text-[11px] font-semibold text-muted">
                                {subtitle}
                            </div>
                        </div>
                    </div>

                    <Link
                        href={route('login')}
                        className="flex-none rounded-xl bg-navy px-4 py-2.5 text-[12.5px] font-bold text-white hover:bg-navy-dark"
                    >
                        Staff Portal
                    </Link>
                </div>
            </header>

            <main className="mx-auto max-w-6xl px-5 py-8 lg:px-8 lg:py-14">
                <div className="lg:grid lg:grid-cols-[1fr_300px] lg:items-start lg:gap-12">
                    <div className="flex flex-col">
                        <h1 className="order-1 animate-screenin text-[32px] font-extrabold leading-[1.12] tracking-[-0.5px] text-ink lg:text-[46px]">
                            Results without
                            <br />
                            the wait.
                        </h1>

                        <p
                            className="order-2 hidden max-w-md animate-screenin text-[15px] leading-relaxed text-body lg:mt-4 lg:block"
                            style={{ animationDelay: '.08s' }}
                        >
                            Result statements, score sheets and the master
                            mark sheet — computed on the 5.0 CGPA scale the
                            moment scores land. No notice boards, no waiting.
                        </p>

                        <div
                            className="order-2 mt-4 flex animate-screenin items-center gap-5 lg:hidden"
                            style={{ animationDelay: '.08s' }}
                        >
                            {STEPS.map((step, index) => (
                                <div
                                    key={step.label}
                                    className="flex items-center gap-5"
                                >
                                    <div className="flex flex-col items-center gap-1.5">
                                        <div className="flex h-9 w-9 items-center justify-center rounded-full bg-tint text-[15px] font-bold text-primary">
                                            {step.icon}
                                        </div>
                                        <div className="text-[10.5px] font-semibold text-muted">
                                            {step.label}
                                        </div>
                                    </div>
                                    {index < STEPS.length - 1 && (
                                        <div className="text-faint">→</div>
                                    )}
                                </div>
                            ))}
                        </div>

                        <div
                            className="order-3 my-7 flex animate-screenin justify-center lg:order-2 lg:my-8 lg:justify-start"
                            style={{ animationDelay: '.12s' }}
                        >
                            <HeroIllustration />
                        </div>

                        <div
                            className="order-4 mb-6 max-w-md animate-screenin text-[13.5px] leading-relaxed text-body lg:hidden"
                            style={{ animationDelay: '.16s' }}
                        >
                            Result statements, score sheets and the master
                            mark sheet — computed on the 5.0 CGPA scale the
                            moment scores land. No notice boards, no waiting.
                        </div>

                        <div
                            className="order-2 flex animate-screenin flex-col gap-2.5 sm:flex-row lg:order-4 lg:mt-2"
                            style={{ animationDelay: '.2s' }}
                        >
                            <Link
                                href={route('public.check')}
                                className="flex h-[52px] flex-1 items-center justify-center rounded-[14px] bg-accent px-6 text-[15px] font-extrabold text-white hover:bg-accent-hover sm:flex-none"
                            >
                                Check My Result
                            </Link>
                        </div>
                    </div>

                    <div className="mt-10 lg:mt-0">
                        <FeatureList />
                    </div>
                </div>

                <div className="mt-10 text-center lg:mt-14">
                    <Link
                        href={route('login')}
                        className="text-[12.5px] font-semibold text-muted underline-offset-2 hover:text-primary hover:underline"
                    >
                        Staff Portal login →
                    </Link>
                </div>
            </main>

            <footer className="border-t border-border bg-white px-5 py-5 text-center text-[11px] text-faint2">
                Access restricted to the HoD and Exam Officer · Results are
                provisional until approved by Senate
            </footer>
        </div>
    );
}

function FeatureList() {
    const scrollerRef = useRef(null);
    const [activeIndex, setActiveIndex] = useState(0);

    const onScroll = () => {
        const el = scrollerRef.current;
        if (!el || !el.firstChild) return;
        const cardWidth = el.firstChild.offsetWidth + 12;
        const index = Math.round(el.scrollLeft / cardWidth);
        setActiveIndex(Math.min(FEATURES.length - 1, Math.max(0, index)));
    };

    const scrollToIndex = (index) => {
        scrollerRef.current?.children[index]?.scrollIntoView({
            behavior: 'smooth',
            inline: 'center',
            block: 'nearest',
        });
    };

    return (
        <>
            <div className="hidden flex-col gap-3 lg:flex">
                {FEATURES.map((feature) => (
                    <FeatureCard key={feature.title} feature={feature} />
                ))}
            </div>

            <div className="lg:hidden">
                <div
                    ref={scrollerRef}
                    onScroll={onScroll}
                    className="flex snap-x snap-mandatory gap-3 overflow-x-auto pb-1 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                >
                    {FEATURES.map((feature) => (
                        <div
                            key={feature.title}
                            className="w-[78%] flex-none snap-center"
                        >
                            <FeatureCard feature={feature} />
                        </div>
                    ))}
                </div>
                <div className="mt-3 flex justify-center gap-1.5">
                    {FEATURES.map((feature, index) => (
                        <button
                            key={feature.title}
                            type="button"
                            onClick={() => scrollToIndex(index)}
                            aria-label={`Show ${feature.title}`}
                            className={
                                'h-1.5 rounded-full transition-all ' +
                                (index === activeIndex
                                    ? 'w-5 bg-accent'
                                    : 'w-1.5 bg-border-input')
                            }
                        />
                    ))}
                </div>
            </div>
        </>
    );
}

function FeatureCard({ feature }) {
    return (
        <div className="h-full rounded-2xl border border-border bg-white p-4 shadow-[0_1px_3px_rgba(23,35,63,.05)]">
            <div className="flex h-10 w-10 items-center justify-center rounded-[12px] bg-tint text-[17px] font-extrabold text-accent">
                {feature.icon}
            </div>
            <div className="mt-3 text-[13.5px] font-bold text-ink">
                {feature.title}
            </div>
            <div className="mt-1 text-[12px] leading-relaxed text-muted">
                {feature.detail}
            </div>
        </div>
    );
}

function HeroIllustration() {
    return (
        <svg
            viewBox="0 0 380 210"
            className="w-full max-w-[300px] lg:max-w-[360px]"
            aria-hidden="true"
        >
            <defs>
                <linearGradient id="landingCloudGrad" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stopColor="#8a72e0" />
                    <stop offset="100%" stopColor="#6c4fd1" />
                </linearGradient>
                <linearGradient id="landingBuildingGrad" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stopColor="#2c4a86" />
                    <stop offset="100%" stopColor="#16294f" />
                </linearGradient>
            </defs>

            <path
                d="M126 82 C 160 108, 178 118, 204 128"
                stroke="#b9a9f2"
                strokeWidth="2"
                strokeDasharray="4 6"
                fill="none"
            />

            <ellipse cx="92" cy="70" rx="68" ry="40" fill="url(#landingCloudGrad)" />
            <circle cx="54" cy="56" r="24" fill="url(#landingCloudGrad)" />
            <circle cx="122" cy="52" r="28" fill="url(#landingCloudGrad)" />
            <rect x="64" y="66" width="9" height="22" rx="3" fill="#fff" opacity=".85" />
            <rect x="80" y="56" width="9" height="32" rx="3" fill="#fff" opacity=".95" />
            <rect x="96" y="70" width="9" height="18" rx="3" fill="#fff" opacity=".85" />

            <rect x="200" y="88" width="118" height="108" rx="8" fill="url(#landingBuildingGrad)" />
            <rect x="216" y="106" width="15" height="15" rx="3" fill="#8fb4ff" opacity=".85" />
            <rect x="243" y="106" width="15" height="15" rx="3" fill="#8fb4ff" opacity=".55" />
            <rect x="270" y="106" width="15" height="15" rx="3" fill="#8fb4ff" opacity=".85" />
            <rect x="216" y="131" width="15" height="15" rx="3" fill="#8fb4ff" opacity=".55" />
            <rect x="243" y="131" width="15" height="15" rx="3" fill="#8fb4ff" opacity=".85" />
            <rect x="270" y="131" width="15" height="15" rx="3" fill="#8fb4ff" opacity=".55" />
            <rect x="242" y="160" width="34" height="36" rx="4" fill="#0f1e3c" />
        </svg>
    );
}
