import type { AdminSharedData, StorefrontSharedData } from '@/types';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: AdminSharedData | StorefrontSharedData;
    }
}
