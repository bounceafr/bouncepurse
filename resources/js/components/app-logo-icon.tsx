import { usePage } from '@inertiajs/react';
import type { ImgHTMLAttributes } from 'react';

export default function AppLogoIcon(
    props: ImgHTMLAttributes<HTMLImageElement>,
) {
    const { assets } = usePage().props;

    return <img src={assets.logo} alt="Bounce" {...props} />;
}
