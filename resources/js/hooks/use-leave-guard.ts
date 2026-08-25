import { useEffect, useRef } from 'react';

type LeaveGuard = () => Promise<boolean>;

let currentGuard: LeaveGuard | null = null;
let leaveConfirmed = false;

export function runLeaveGuard(): Promise<boolean> {
    if (!currentGuard) return Promise.resolve(true);

    return currentGuard().then((confirmed) => {
        leaveConfirmed = confirmed;
        return confirmed;
    });
}

export function consumeLeaveConfirmation(): boolean {
    const confirmed = leaveConfirmed;
    leaveConfirmed = false;
    return confirmed;
}

export function useLeaveGuard(active: boolean, guard: LeaveGuard) {
    const guardRef = useRef(guard);
    useEffect(() => {
        guardRef.current = guard;
    });

    useEffect(() => {
        if (!active) return;

        const fn: LeaveGuard = () => guardRef.current();
        currentGuard = fn;

        return () => {
            if (currentGuard === fn) {
                currentGuard = null;
            }
            leaveConfirmed = false;
        };
    }, [active]);
}
