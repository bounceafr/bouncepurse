import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type Props = {
    children: ReactNode;
    className?: string;
    /** Constrain the card to a form-friendly width. */
    narrow?: boolean;
    /** Stretch the card across the full content area. */
    fullWidth?: boolean;
};

export function ListPageShell({
    children,
    className,
    narrow = false,
    fullWidth = false,
}: Props) {
    return (
        <div className="flex flex-1 flex-col bg-muted/40">
            <div
                className={cn(
                    'mx-auto py-8',
                    narrow && 'w-full max-w-2xl px-4',
                    fullWidth && 'w-full px-6',
                    !narrow && !fullWidth && 'w-[80%]',
                )}
            >
                <div
                    className={cn(
                        'flex flex-col gap-6 rounded-xl bg-card p-8 text-card-foreground shadow-sm ring-1 ring-border',
                        className,
                    )}
                >
                    {children}
                </div>
            </div>
        </div>
    );
}
