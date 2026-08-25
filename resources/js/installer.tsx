import { type ResolvedComponent } from '@inertiajs/react';

import { DirectionProvider } from '@/components/ui/direction';

import { createApp } from './create-app';
import { InstallerLayout } from './layouts/installer/installer-layout';

createApp({
    resolve: (name) => {
        const pages = import.meta.glob<ResolvedComponent>('./pages/installer/**/*.tsx');
        return pages[`./pages/${name}.tsx`]();
    },
    layout: () => InstallerLayout,
    wrap: (children, direction) => <DirectionProvider dir={direction}>{children}</DirectionProvider>,
});
