import type { ReactNode } from 'react';

import { SubmitButton } from '@/components/admin/submit-button';
import { Checkbox } from '@/components/ui/checkbox';
import { HelpBlock } from '@/components/ui/help-block';
import { Label } from '@/components/ui/label';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

interface FormSubmitProps {
    processing: boolean;
    recentlySuccessful: boolean;
    showAddMore?: boolean;
    className?: string;
    children: ReactNode;
}

function FormSubmit({ processing, recentlySuccessful, showAddMore = false, className, children }: FormSubmitProps) {
    return (
        <div className={cn('flex items-center justify-end gap-4', className)}>
            <div className="relative flex items-center">
                {showAddMore && (
                    <div className={cn('flex items-center gap-2', recentlySuccessful ? 'invisible' : '')}>
                        <Checkbox id="add-more" name="add_more" defaultChecked={false} />
                        <Label htmlFor="add-more">{__('Add more')}</Label>
                    </div>
                )}

                {showAddMore && (
                    <div className={cn('absolute end-0', !recentlySuccessful && 'pointer-events-none')}>
                        <HelpBlock
                            className={cn(
                                'transition-opacity duration-300',
                                recentlySuccessful ? 'opacity-100' : 'opacity-0',
                            )}
                        >
                            {__('Saved')}
                        </HelpBlock>
                    </div>
                )}

                {!showAddMore && (
                    <HelpBlock
                        className={cn(
                            'transition-opacity duration-300',
                            recentlySuccessful ? 'opacity-100' : 'opacity-0',
                        )}
                    >
                        {__('Saved')}
                    </HelpBlock>
                )}
            </div>

            <SubmitButton processing={processing}>{children}</SubmitButton>
        </div>
    );
}

export { FormSubmit };
