import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type Props = {
    children: ReactNode;
    className?: string;
};

const PEBBLE_STYLE = {
    backgroundColor: '#faf8f6',
    backgroundImage:
        'radial-gradient(circle, rgba(248, 104, 8, 0.08) 1px, transparent 1px)',
    backgroundSize: '8px 8px',
} as const;

function CornerCurves() {
    return (
        <>
            <svg
                className="pointer-events-none absolute top-0 left-0 h-36 w-36 text-[#f86808]/20"
                viewBox="0 0 120 120"
                fill="none"
                aria-hidden="true"
            >
                <path
                    d="M0 90 C0 25 25 0 90 0"
                    stroke="currentColor"
                    strokeWidth="1.5"
                />
            </svg>
            <svg
                className="pointer-events-none absolute top-0 right-0 h-36 w-36 text-[#f86808]/20"
                viewBox="0 0 120 120"
                fill="none"
                aria-hidden="true"
            >
                <path
                    d="M30 0 C95 0 120 25 120 90"
                    stroke="currentColor"
                    strokeWidth="1.5"
                />
            </svg>
            <svg
                className="pointer-events-none absolute bottom-0 left-0 h-36 w-36 text-[#f86808]/20"
                viewBox="0 0 120 120"
                fill="none"
                aria-hidden="true"
            >
                <path
                    d="M0 30 C0 95 25 120 90 120"
                    stroke="currentColor"
                    strokeWidth="1.5"
                />
            </svg>
            <svg
                className="pointer-events-none absolute right-0 bottom-0 h-36 w-36 text-[#f86808]/20"
                viewBox="0 0 120 120"
                fill="none"
                aria-hidden="true"
            >
                <path
                    d="M30 120 C95 120 120 95 120 30"
                    stroke="currentColor"
                    strokeWidth="1.5"
                />
            </svg>
        </>
    );
}

export function BasketballSurface({ children, className }: Props) {
    return (
        <div
            className={cn('relative isolate flex min-h-full flex-1 flex-col', className)}
            style={PEBBLE_STYLE}
        >
            <CornerCurves />
            <div className="relative z-10 flex min-h-full flex-1 flex-col">
                {children}
            </div>
        </div>
    );
}
