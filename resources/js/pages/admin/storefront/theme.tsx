import { router } from '@inertiajs/react';
import { useState } from 'react';

import * as StorefrontController from '@/actions/App/Http/Controllers/Admin/StorefrontController';
import * as StorefrontThemeController from '@/actions/App/Http/Controllers/Admin/StorefrontThemeController';
import { Can } from '@/components/ui/can';
import { useStorefrontBuilder } from '@/layouts/admin/storefront-builder-layout';
import { __ } from '@/lib/i18n';
import { Permission } from '@/lib/permissions';
import { cn } from '@/lib/utils';

type StorefrontAccent =
    | 'blue'
    | 'green'
    | 'rose'
    | 'gold'
    | 'teal'
    | 'magenta'
    | 'brown'
    | 'orange'
    | 'red'
    | 'pink'
    | 'terracotta'
    | 'violet';

const accents: { value: StorefrontAccent; label: string; color: string }[] = [
    { value: 'blue', label: __('Blue'), color: 'oklch(0.48 0.16 250)' },
    { value: 'green', label: __('Green'), color: 'oklch(0.53 0.14 150)' },
    { value: 'rose', label: __('Rose'), color: 'oklch(0.62 0.1 20)' },
    { value: 'gold', label: __('Gold'), color: 'oklch(0.6 0.11 85)' },
    { value: 'teal', label: __('Teal'), color: 'oklch(0.54 0.11 185)' },
    { value: 'magenta', label: __('Magenta'), color: 'oklch(0.48 0.17 325)' },
    { value: 'brown', label: __('Brown'), color: 'oklch(0.5 0.09 55)' },
    { value: 'orange', label: __('Orange'), color: 'oklch(0.62 0.16 45)' },
    { value: 'red', label: __('Red'), color: 'oklch(0.52 0.19 25)' },
    { value: 'pink', label: __('Pink'), color: 'oklch(0.55 0.17 350)' },
    { value: 'terracotta', label: __('Terracotta'), color: 'oklch(0.55 0.12 40)' },
    { value: 'violet', label: __('Violet'), color: 'oklch(0.52 0.15 300)' },
];

interface ThemePageProps {
    storefrontThemeColor: StorefrontAccent;
}

function AccentSwatch({
    label,
    color,
    selected,
    disabled,
    onSelect,
}: {
    label: string;
    color: string;
    selected: boolean;
    disabled?: boolean;
    onSelect?: () => void;
}) {
    return (
        <button
            type="button"
            onClick={onSelect}
            disabled={disabled}
            className={cn(
                'flex items-center gap-2 rounded-md border p-2 text-sm transition-colors',
                disabled ? 'cursor-not-allowed' : 'hover:bg-muted',
                selected && 'border-primary bg-muted',
            )}
        >
            <div className="size-4 rounded-full border" style={{ backgroundColor: color }} />
            <span>{label}</span>
        </button>
    );
}

export default function ThemePage({ storefrontThemeColor }: ThemePageProps) {
    const [selectedAccent, setSelectedAccent] = useState(storefrontThemeColor);
    const { reloadIframe } = useStorefrontBuilder();

    const handleSelectAccent = (accent: StorefrontAccent) => {
        setSelectedAccent(accent);
        router.patch(
            StorefrontThemeController.update(),
            { storefront_theme_color: accent },
            {
                preserveScroll: true,
                only: ['storefrontThemeColor'],
                onSuccess: () => reloadIframe(),
            },
        );
    };

    return (
        <div className="mb-6 space-y-6 p-4 text-sm">
            <div className="space-y-4">
                <div className="space-y-1">
                    <h3 className="font-medium">{__('Accent color')}</h3>
                    <p className="text-muted-foreground">{__('Choose the accent color for your storefront')}</p>
                </div>

                <Can
                    permission={Permission.StorefrontUpdate}
                    fallback={
                        <div className="grid grid-cols-2 gap-2 opacity-60">
                            {accents.map((accent) => (
                                <AccentSwatch
                                    key={accent.value}
                                    label={accent.label}
                                    color={accent.color}
                                    selected={selectedAccent === accent.value}
                                    disabled
                                />
                            ))}
                        </div>
                    }
                >
                    <div className="grid grid-cols-2 gap-2">
                        {accents.map((accent) => (
                            <AccentSwatch
                                key={accent.value}
                                label={accent.label}
                                color={accent.color}
                                selected={selectedAccent === accent.value}
                                onSelect={() => handleSelectAccent(accent.value)}
                            />
                        ))}
                    </div>
                </Can>
            </div>
        </div>
    );
}

ThemePage.layout = {
    title: __('Theme'),
    backHref: StorefrontController.index(),
};
