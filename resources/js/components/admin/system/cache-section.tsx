import { router } from '@inertiajs/react';
import { useState } from 'react';

import RebuildCacheController from '@/actions/App/Http/Controllers/Admin/RebuildCacheController';
import { useConfirm } from '@/components/admin/confirm';
import { Button } from '@/components/ui/button';
import { HelpBlock } from '@/components/ui/help-block';
import { Label } from '@/components/ui/label';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

export function CacheSection() {
    const { confirm } = useConfirm();
    const [cacheCleared, setCacheCleared] = useState(false);

    const handleClearCache = () => {
        confirm({
            title: __('Rebuild cache'),
            description: __(
                'This will clear and rebuild all cached data including configs, routes, and views. The site may be temporarily slower.',
            ),
            confirmLabel: __('Rebuild cache'),
            action: () =>
                new Promise<void>((resolve) => {
                    setCacheCleared(false);

                    router.post(
                        RebuildCacheController(),
                        {},
                        {
                            preserveScroll: true,
                            onSuccess: () => {
                                setCacheCleared(true);
                                setTimeout(() => setCacheCleared(false), 2000);
                            },
                            onFinish: () => resolve(),
                        },
                    );
                }),
        });
    };

    return (
        <div className="grid grid-cols-12 gap-6">
            <div className="col-span-12 space-y-1 md:col-span-6">
                <Label className="text-sm font-medium">{__('Cache')}</Label>
                <HelpBlock>
                    {__('Clear and rebuild all cached data to apply recent changes or resolve issues')}
                </HelpBlock>
            </div>
            <div className="col-span-12 flex items-center gap-4 md:col-span-6">
                <Button type="button" variant="outline" onClick={handleClearCache}>
                    {__('Rebuild cache')}
                </Button>
                <HelpBlock
                    className={cn('transition-opacity duration-300', cacheCleared ? 'opacity-100' : 'opacity-0')}
                >
                    {__('Cache rebuilt')}
                </HelpBlock>
            </div>
        </div>
    );
}
