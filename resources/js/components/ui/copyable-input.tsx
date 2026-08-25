import { CheckIcon, CopyIcon } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useClipboard } from '@/hooks/use-clipboard';
import { __ } from '@/lib/i18n';

export function CopyableInput({ value }: { value: string }) {
    const [copiedText, copy] = useClipboard();
    const isCopied = copiedText === value;

    return (
        <div className="flex items-center gap-2">
            <Input
                dir="ltr"
                value={value}
                onFocus={(event) => event.target.select()}
                className="min-w-0 flex-1 bg-muted/50 font-mono text-xs"
                readOnly
            />
            <Button type="button" variant="outline" size="icon-md" onClick={() => copy(value)} aria-label={__('Copy')}>
                {isCopied ? <CheckIcon /> : <CopyIcon />}
            </Button>
        </div>
    );
}
