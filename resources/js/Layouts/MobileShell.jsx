import PinReveal from '@/Components/PinReveal';
import Toast from '@/Components/Toast';
import { SHELL_WIDTH_NARROW, SHELL_WIDTH_WIDE } from '@/lib/layout';

export default function MobileShell({ children, width = 'narrow' }) {
    const widthClass = width === 'wide' ? SHELL_WIDTH_WIDE : SHELL_WIDTH_NARROW;

    return (
        <div className="flex min-h-screen justify-center">
            <div
                className={`relative flex min-h-screen w-full ${widthClass} flex-col bg-shell shadow-[0_0_44px_rgba(18,32,66,.10)]`}
            >
                {children}
            </div>
            <Toast />
            <PinReveal />
        </div>
    );
}
