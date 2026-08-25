import { Link } from '@inertiajs/react';
import { MailWarningIcon } from 'lucide-react';

import EmailVerificationNoticeController from '@/actions/App/Http/Controllers/Storefront/EmailVerificationNoticeController';
import { __ } from '@/lib/i18n';

export function EmailVerificationBanner() {
    return (
        <div className="flex items-start gap-3 rounded-md border border-orange/30 bg-orange/10 p-4">
            <MailWarningIcon size={20} strokeWidth={1.8} aria-hidden="true" className="mt-0.5 shrink-0 text-orange" />
            <p className="mt-0 mb-0 text-sm leading-relaxed text-body">
                {__('Please verify your email address to receive order updates and account notifications.')}{' '}
                <Link
                    href={EmailVerificationNoticeController()}
                    className="font-semibold text-primary underline-offset-2 hover:underline"
                >
                    {__('Verify now')}
                </Link>
            </p>
        </div>
    );
}
