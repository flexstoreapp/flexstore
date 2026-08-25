import { CheckIcon, CopyIcon } from 'lucide-react';

import { useClipboard } from '@/hooks/use-clipboard';
import { __ } from '@/lib/i18n';

export function ErrorDetails({ error }: { error: Error }) {
    const [copiedText, copy] = useClipboard();
    const details = error.stack ?? error.message;
    const isCopied = copiedText === details;

    return (
        <details open className="text-start">
            <summary className="cursor-default text-sm font-medium text-red-600 dark:text-red-500">
                Error Details
            </summary>
            <div className="relative mt-2 overflow-auto rounded-lg border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-800 dark:bg-neutral-900">
                <button
                    type="button"
                    aria-label={__('Copy error')}
                    onClick={() => copy(details)}
                    className="absolute end-2 top-2 flex size-7 items-center justify-center rounded-md text-neutral-500 transition-colors hover:bg-neutral-200 hover:text-neutral-900 dark:hover:bg-neutral-800 dark:hover:text-neutral-100"
                >
                    {isCopied ? <CheckIcon className="size-3.5" /> : <CopyIcon className="size-3.5" />}
                </button>
                <p className="pe-8 text-xs font-semibold">{error.message}</p>
                {error.stack && (
                    <pre className="mt-2 text-xs text-neutral-500 dark:text-neutral-400">{error.stack}</pre>
                )}
            </div>
        </details>
    );
}
