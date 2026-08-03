import type { HTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

export default function InputError({
    message,
    className = '',
    ...props
}: HTMLAttributes<HTMLParagraphElement> & { message?: string }) {
    return message ? (
        <p
            {...props}
            // Designsprache §1.4: kein Signalrot. `destructive` ist der
            // gedämpfte warme Ton aus dem Token-Set.
            className={cn('text-sm text-destructive', className)}
        >
            {message}
        </p>
    ) : null;
}
