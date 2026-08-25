import { Link, usePage } from '@inertiajs/react';
import type { ReactElement } from 'react';

import {
    AmexIcon,
    ApplePayIcon,
    DiscoverIcon,
    FacebookIcon,
    GooglePayIcon,
    IdealIcon,
    InstagramIcon,
    JcbIcon,
    MadaIcon,
    MastercardIcon,
    PaypalIcon,
    UnionPayIcon,
    UpiIcon,
    VisaIcon,
    XIcon,
    YoutubeIcon,
} from '@/components/brand-icons';
import { __ } from '@/lib/i18n';
import type { PaymentMethodName, StorefrontSharedData } from '@/types';

const socialClass =
    'w-9 h-9 rounded-full border border-inverse-line flex items-center justify-center transition-colors hover:border-white hover:text-white';

type BrandIcon = (props: { className?: string }) => ReactElement;

const socialPlatforms = ['facebook', 'instagram', 'x', 'youtube'] as const;

const socials: Record<(typeof socialPlatforms)[number], { icon: BrandIcon; label: string }> = {
    facebook: { icon: FacebookIcon, label: 'Facebook' },
    instagram: { icon: InstagramIcon, label: 'Instagram' },
    x: { icon: XIcon, label: 'X' },
    youtube: { icon: YoutubeIcon, label: 'YouTube' },
};

const payments: Record<PaymentMethodName, { icon: BrandIcon; label: string }> = {
    visa: { icon: VisaIcon, label: 'Visa' },
    mastercard: { icon: MastercardIcon, label: 'Mastercard' },
    amex: { icon: AmexIcon, label: 'Amex' },
    discover: { icon: DiscoverIcon, label: 'Discover' },
    jcb: { icon: JcbIcon, label: 'JCB' },
    unionpay: { icon: UnionPayIcon, label: 'UnionPay' },
    mada: { icon: MadaIcon, label: 'mada' },
    paypal: { icon: PaypalIcon, label: 'PayPal' },
    apple_pay: { icon: ApplePayIcon, label: 'Apple Pay' },
    google_pay: { icon: GooglePayIcon, label: 'Google Pay' },
    upi: { icon: UpiIcon, label: 'UPI' },
    ideal: { icon: IdealIcon, label: 'iDEAL' },
};

export function SiteFooter() {
    const { storeName, storeLogoDark, storefront } = usePage<StorefrontSharedData>().props;
    const footer = storefront.footer;

    const socialEntries = socialPlatforms.filter((platform) => footer.socialLinks[platform]);
    const showSocial = footer.showSocialLinks && socialEntries.length > 0;
    const showBadges = footer.showPaymentBadges && footer.paymentMethods.length > 0;
    const showBottomBar = footer.showCopyright || showBadges || footer.showPoweredBy;

    return (
        <footer className="bg-ink text-inverse-muted **:focus-visible:outline-white">
            <div className="mx-auto flex w-full max-w-page flex-col gap-10 px-6 pt-14 pb-10 md:flex-row md:gap-12 lg:gap-16">
                <div className="md:w-56 md:shrink-0 lg:w-80">
                    {storeLogoDark?.url ? (
                        <img src={storeLogoDark.url} alt={storeName} className="h-9 w-auto" />
                    ) : (
                        <div className="font-head text-4xl font-bold text-white">{storeName}</div>
                    )}
                    {footer.description && (
                        <p className="mt-4 max-w-sm leading-relaxed" dir="auto">
                            {footer.description}
                        </p>
                    )}
                    {(storefront.store_phone || storefront.store_email) && (
                        <div className="mt-4 flex flex-col gap-2">
                            {storefront.store_phone && (
                                <a href={`tel:${storefront.store_phone}`} className="font-semibold text-white">
                                    <bdi>{storefront.store_phone}</bdi>
                                </a>
                            )}
                            {storefront.store_email && (
                                <a
                                    href={`mailto:${storefront.store_email}`}
                                    className="transition-colors hover:text-white"
                                >
                                    {storefront.store_email}
                                </a>
                            )}
                        </div>
                    )}
                </div>

                <div className="grid grow grid-cols-2 gap-10 md:grid-cols-3 lg:grid-cols-4">
                    {footer.menu.map((column) => (
                        <div key={column.label}>
                            <div className="mb-4 font-head font-semibold text-white">{column.label}</div>
                            <nav className="flex flex-col gap-2.5">
                                {column.children?.map((link) => (
                                    <Link
                                        key={link.label}
                                        href={link.url}
                                        target={link.target}
                                        className="transition-colors hover:text-white"
                                    >
                                        {link.label}
                                    </Link>
                                ))}
                            </nav>
                        </div>
                    ))}

                    {showSocial && (
                        <div>
                            <div className="mb-4 font-head font-semibold text-white">{__('Follow us')}</div>
                            <div className="mt-4 flex gap-3">
                                {socialEntries.map((platform) => {
                                    const { icon: Icon, label } = socials[platform];

                                    return (
                                        <a
                                            key={platform}
                                            href={footer.socialLinks[platform]}
                                            target="_blank"
                                            rel="noreferrer noopener"
                                            aria-label={label}
                                            className={socialClass}
                                        >
                                            <Icon className="size-[17px]" />
                                        </a>
                                    );
                                })}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {showBottomBar && (
                <div className="border-t border-inverse-line">
                    <div className="mx-auto flex w-full max-w-page flex-col items-center justify-between gap-4 px-6 py-5 text-sm sm:h-16 sm:flex-row sm:py-0">
                        {footer.showCopyright && (
                            <span className="order-2 text-center sm:order-1">
                                {footer.copyrightText ?? (
                                    <>
                                        © {new Date().getFullYear()} {storeName}. {__('All rights reserved.')}
                                    </>
                                )}
                            </span>
                        )}
                        {showBadges && (
                            <div className="order-1 flex flex-wrap items-center justify-center gap-3 sm:order-2">
                                {footer.paymentMethods.map((method) => {
                                    const { icon: Icon, label } = payments[method];

                                    return (
                                        <span key={method} role="img" aria-label={label}>
                                            <Icon className="h-6 w-auto" />
                                        </span>
                                    );
                                })}
                            </div>
                        )}
                        {footer.showPoweredBy && (
                            <span className="order-3 text-center text-xs">{__('Powered by FlexStore')}</span>
                        )}
                    </div>
                </div>
            )}
        </footer>
    );
}
