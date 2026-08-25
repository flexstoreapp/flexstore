import { HelpBlock } from '@/components/ui/help-block';
import { __ } from '@/lib/i18n';

interface SelectedItemActionsProps {
    selectedItems: (number | string)[];
}

export function SelectedItemActions({ selectedItems, children }: React.PropsWithChildren<SelectedItemActionsProps>) {
    return (
        selectedItems.length > 0 && (
            <div className="flex items-center gap-2">
                <HelpBlock className="whitespace-nowrap">
                    {__(':count selected', { count: selectedItems.length })}
                </HelpBlock>
                {children}
            </div>
        )
    );
}
