import { ReactiveHiddenInput } from '@/components/admin/reactive-hidden-input';

interface FormDirtySignalProps {
    signal: string | number;
}

export function FormDirtySignal({ signal }: FormDirtySignalProps) {
    return <ReactiveHiddenInput value={signal} />;
}
