import { usePage } from '@inertiajs/react';

import type { Direction } from '@/types';

type DirectionPageProps = {
    direction?: Direction;
    [key: string]: unknown;
};

export function useDirection(): Direction {
    return usePage<DirectionPageProps>().props.direction ?? 'ltr';
}
