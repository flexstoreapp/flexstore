import { useEffect, useRef } from 'react';

interface ReactiveHiddenInputProps {
    name?: string;
    value: string | number;
    id?: string;
}

export function ReactiveHiddenInput({ name, value, id }: ReactiveHiddenInputProps) {
    const ref = useRef<HTMLInputElement>(null);
    const mounted = useRef(false);

    useEffect(() => {
        if (!mounted.current) {
            mounted.current = true;
            return;
        }
        ref.current?.dispatchEvent(new Event('input', { bubbles: true }));
    }, [value]);

    return <input ref={ref} type="hidden" id={id} name={name} value={value} readOnly />;
}
