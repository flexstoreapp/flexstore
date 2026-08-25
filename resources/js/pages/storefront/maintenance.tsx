import { __ } from '@/lib/i18n';

export default function Maintenance() {
    return (
        <>
            <div className="flex min-h-screen flex-col items-center justify-center text-center">
                <h1 className="font-head text-8xl font-semibold text-ink">{__('We will be back soon')}</h1>
                <p className="mt-3 max-w-md text-md text-muted">
                    {__('Our store is undergoing scheduled maintenance. Please check back shortly.')}
                </p>
            </div>
        </>
    );
}
