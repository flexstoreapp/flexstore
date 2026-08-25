import { CheckCircle2Icon, XCircleIcon } from 'lucide-react';

export function StatusIcon({ passed }: { passed: boolean }) {
    return passed ? (
        <CheckCircle2Icon className="size-4 shrink-0 text-emerald-700" />
    ) : (
        <XCircleIcon className="size-4 shrink-0 text-destructive" />
    );
}
