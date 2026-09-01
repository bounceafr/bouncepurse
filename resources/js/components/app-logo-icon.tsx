import { usePage } from '@inertiajs/react';
import type { ImgHTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

export default function AppLogoIcon({
    className,
    alt = 'Bounce',
    ...props
}: ImgHTMLAttributes<HTMLImageElement>) {
    const { assets } = usePage().props;

    return (
        <>
            <img
                src={assets.logo}
                alt={alt}
                className={cn(className, 'dark:hidden')}
                {...props}
            />
            <img
                src={assets.logoDark}
                alt={alt}
                className={cn(className, 'hidden dark:block')}
                {...props}
            />
        </>
    );
}
