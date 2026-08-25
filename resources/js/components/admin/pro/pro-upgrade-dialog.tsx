import { SparklesIcon } from 'lucide-react';

import { PRO_UPGRADE_URL } from '@/components/admin/pro/pro-upgrade-url';
import {
    AdaptiveDialog,
    AdaptiveDialogClose,
    AdaptiveDialogContent,
    AdaptiveDialogDescription,
    AdaptiveDialogFooter,
    AdaptiveDialogHeader,
    AdaptiveDialogTitle,
} from '@/components/ui/adaptive-dialog';
import { Button, buttonVariants } from '@/components/ui/button';
import { __ } from '@/lib/i18n';

const FEATURES = [
    __('Abandoned checkout recovery and shared payment links'),
    __('Flash sales, blog, product compare and buy again'),
    __('Reports, CSV import and export'),
    __('Returns, staff members and custom roles'),
    __('Multi-currency and multi-language storefronts'),
    __('Live shipping rates, newsletters and catalog feeds'),
    __('Search synonyms and social login'),
    __('Set download limits and link expiry on digital files'),
];
interface ProUpgradeDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    feature: string | null;
}

export function ProUpgradeDialog({ open, onOpenChange, feature }: ProUpgradeDialogProps) {
    return (
        <AdaptiveDialog open={open} onOpenChange={onOpenChange}>
            <AdaptiveDialogContent className="sm:max-w-lg">
                <AdaptiveDialogHeader>
                    <AdaptiveDialogTitle>
                        {feature ? __(':feature is a Pro feature', { feature }) : __('This is a Pro feature')}
                    </AdaptiveDialogTitle>
                    <AdaptiveDialogDescription>
                        {__('Upgrade to FlexStore Pro to unlock it. Your store, data and settings carry over.')}
                    </AdaptiveDialogDescription>
                </AdaptiveDialogHeader>

                <ul className="space-y-2 px-4 text-sm text-muted-foreground sm:px-0">
                    {FEATURES.map((item) => (
                        <li key={item} className="flex items-start gap-2">
                            <SparklesIcon
                                className="mt-0.5 size-3.5 shrink-0 text-amber-600 dark:text-amber-400"
                                aria-hidden="true"
                            />
                            <span>{item}</span>
                        </li>
                    ))}
                </ul>

                <AdaptiveDialogFooter>
                    <AdaptiveDialogClose asChild>
                        <Button variant="outline">{__('Not now')}</Button>
                    </AdaptiveDialogClose>
                    <a className={buttonVariants()} href={PRO_UPGRADE_URL} target="_blank" rel="noreferrer noopener">
                        {__('Upgrade to Pro')}
                    </a>
                </AdaptiveDialogFooter>
            </AdaptiveDialogContent>
        </AdaptiveDialog>
    );
}
