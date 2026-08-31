import { motion } from 'framer-motion';
import { useEffect, useState } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { sportEase } from '@/components/motion';
import type { AuthLayoutProps } from '@/types';

export default function AuthGlassLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const [prefersReducedMotion, setPrefersReducedMotion] = useState(false);

    useEffect(() => {
        const mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
        const updatePreference = () => setPrefersReducedMotion(mediaQuery.matches);

        updatePreference();
        mediaQuery.addEventListener('change', updatePreference);

        return () => mediaQuery.removeEventListener('change', updatePreference);
    }, []);

    return (
        <div className="relative min-h-dvh overflow-hidden">
            {prefersReducedMotion ? (
                <img
                    src="/videos/login-bg-poster.jpg"
                    alt=""
                    aria-hidden="true"
                    className="absolute inset-0 size-full object-cover"
                />
            ) : (
                <video
                    autoPlay
                    muted
                    loop
                    playsInline
                    poster="/videos/login-bg-poster.jpg"
                    aria-hidden="true"
                    className="absolute inset-0 size-full object-cover"
                >
                    <source src="/videos/login-bg.mp4" type="video/mp4" />
                </video>
            )}

            <div
                className="absolute inset-0 bg-gradient-to-br from-black/50 via-[#f86808]/25 to-black/60"
                aria-hidden="true"
            />

            <div className="relative z-10 flex min-h-dvh items-center justify-center p-4 sm:p-6">
                <motion.div
                    className="w-full max-w-[420px] rounded-[28px] border border-white/40 bg-white/80 px-8 py-10 shadow-[0_24px_64px_rgba(248,104,8,0.15)] backdrop-blur-2xl backdrop-saturate-150 sm:px-10"
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.5, ease: sportEase }}
                >
                    <div className="flex flex-col items-center gap-6 text-center">
                        <motion.div
                            initial={{ scale: 0.9, opacity: 0 }}
                            animate={{ scale: 1, opacity: 1 }}
                            transition={{ duration: 0.5, delay: 0.1, ease: sportEase }}
                        >
                            <AppLogoIcon className="h-16 w-auto" />
                        </motion.div>

                        <div className="flex flex-col gap-2">
                            <h1 className="font-heading text-2xl font-bold tracking-tight text-foreground">
                                {title}
                            </h1>
                            {description && (
                                <p className="text-sm leading-relaxed text-muted-foreground">
                                    {description}
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="mt-8 [&_button[type=submit]]:sport-glow">
                        {children}
                    </div>
                </motion.div>
            </div>
        </div>
    );
}
