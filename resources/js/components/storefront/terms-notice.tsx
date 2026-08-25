import { Link } from '@inertiajs/react';

import * as PrivacyPolicyController from '@/actions/App/Http/Controllers/Storefront/PrivacyPolicyController';
import * as TermsOfServiceController from '@/actions/App/Http/Controllers/Storefront/TermsOfServiceController';
import { __, __nodes } from '@/lib/i18n';

const linkClass = 'text-primary font-semibold underline-offset-2 hover:underline';

export function TermsNotice() {
    return (
        <p className="mt-3 mb-0 text-center text-sm leading-relaxed text-muted">
            {__nodes('By placing the order you agree to our :terms and :privacy.', {
                terms: (
                    <Link href={TermsOfServiceController.show()} className={linkClass}>
                        {__('Terms')}
                    </Link>
                ),
                privacy: (
                    <Link href={PrivacyPolicyController.show()} className={linkClass}>
                        {__('Privacy policy')}
                    </Link>
                ),
            })}
        </p>
    );
}
