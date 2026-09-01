import {
    type HTMLMotionProps,
    motion,
    type Transition,
    type Variants,
} from 'framer-motion';
import {
    type ComponentProps,
    type ReactNode,
    useEffect,
    useState,
} from 'react';

export const sportEase = [0.22, 1, 0.36, 1] as const;

export const sportSpring: Transition = {
    type: 'spring',
    stiffness: 380,
    damping: 28,
};

export const pageEnterVariants: Variants = {
    hidden: { opacity: 0, y: 16 },
    visible: { opacity: 1, y: 0 },
};

export const staggerContainerVariants: Variants = {
    hidden: { opacity: 0 },
    visible: {
        opacity: 1,
        transition: { staggerChildren: 0.08, delayChildren: 0.04 },
    },
};

export const staggerItemVariants: Variants = {
    hidden: { opacity: 0, y: 12 },
    visible: {
        opacity: 1,
        y: 0,
        transition: { duration: 0.4, ease: sportEase },
    },
};

export function usePrefersReducedMotion(): boolean {
    const [prefersReducedMotion, setPrefersReducedMotion] = useState(false);

    useEffect(() => {
        const mediaQuery = window.matchMedia(
            '(prefers-reduced-motion: reduce)',
        );
        const updatePreference = () =>
            setPrefersReducedMotion(mediaQuery.matches);

        updatePreference();
        mediaQuery.addEventListener('change', updatePreference);

        return () => mediaQuery.removeEventListener('change', updatePreference);
    }, []);

    return prefersReducedMotion;
}

type MotionDivProps = Omit<HTMLMotionProps<'div'>, 'children'> & {
    children?: ReactNode;
};

export function PageEnter({ children, className, ...props }: MotionDivProps) {
    const prefersReducedMotion = usePrefersReducedMotion();

    if (prefersReducedMotion) {
        return (
            <div className={className} {...(props as ComponentProps<'div'>)}>
                {children}
            </div>
        );
    }

    return (
        <motion.div
            className={className}
            initial="hidden"
            animate="visible"
            variants={pageEnterVariants}
            transition={{ duration: 0.45, ease: sportEase }}
            {...props}
        >
            {children}
        </motion.div>
    );
}

export function StaggerChildren({
    children,
    className,
    ...props
}: MotionDivProps) {
    const prefersReducedMotion = usePrefersReducedMotion();

    if (prefersReducedMotion) {
        return (
            <div className={className} {...(props as ComponentProps<'div'>)}>
                {children}
            </div>
        );
    }

    return (
        <motion.div
            className={className}
            initial="hidden"
            animate="visible"
            variants={staggerContainerVariants}
            {...props}
        >
            {children}
        </motion.div>
    );
}

export function StaggerItem({ children, className, ...props }: MotionDivProps) {
    const prefersReducedMotion = usePrefersReducedMotion();

    if (prefersReducedMotion) {
        return (
            <div className={className} {...(props as ComponentProps<'div'>)}>
                {children}
            </div>
        );
    }

    return (
        <motion.div
            className={className}
            variants={staggerItemVariants}
            {...props}
        >
            {children}
        </motion.div>
    );
}
