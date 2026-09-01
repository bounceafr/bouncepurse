import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type Props = {
    children: ReactNode;
    className?: string;
};

export function BasketballSurface({ children, className }: Props) {
    return (
        <div
            className={cn(
                'basketball-surface relative flex min-h-full flex-1 flex-col',
                className,
            )}
        >
            {children}
        </div>
    );
}
