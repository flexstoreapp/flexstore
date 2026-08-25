import { __ } from '@/lib/i18n';

import { useCheckout } from './checkout-context';

export function NotesSection() {
    const { notes, setNotes, errors } = useCheckout();

    return (
        <section aria-labelledby="co-notes-h" className="rounded-md border border-line bg-surface p-6 lg:p-7">
            <h2 id="co-notes-h" className="m-0 text-xl font-semibold text-ink">
                {__('Order notes')}
                <span className="ms-2 text-sm font-normal text-muted">({__('optional')})</span>
            </h2>
            <div className="mt-5">
                <textarea
                    id="co-notes"
                    aria-labelledby="co-notes-h"
                    rows={3}
                    value={notes}
                    onChange={(event) => setNotes(event.target.value)}
                    placeholder={__('Special instructions for your order')}
                    className="w-full resize-none rounded-md border border-line-strong bg-surface-2 px-4 py-3 text-ink transition placeholder:text-muted focus-visible:border-primary focus-visible:ring-1 focus-visible:ring-primary focus-visible:outline-hidden"
                />
                {errors.notes && <p className="mt-1.5 mb-0 text-sm text-error">{errors.notes}</p>}
            </div>
        </section>
    );
}
