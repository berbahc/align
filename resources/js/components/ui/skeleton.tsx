import { cn } from '@/lib/utils';

/**
 * Die Platzhalterfläche, solange etwas lädt.
 *
 * `as` erlaubt ein `<span>`: Ein Skeleton steht auch mitten in einem Satz —
 * dort, wo gleich die Antwort der KI steht — und ein `<div>` in einem `<p>`
 * ist ungültiges HTML, das React zur Laufzeit anmahnt.
 */
function Skeleton({
    className,
    as: Tag = 'div',
    ...props
}: React.ComponentProps<'div'> & { as?: 'div' | 'span' }) {
    return (
        <Tag
            data-slot="skeleton"
            className={cn('bg-primary/10 animate-pulse rounded-md', className)}
            {...props}
        />
    );
}

export { Skeleton };
