import { getExampleNumber } from 'libphonenumber-js';
import examples from 'libphonenumber-js/examples.mobile.json';
import { ChevronDownIcon } from 'lucide-react';
import { forwardRef, useEffect, useRef, type ComponentProps } from 'react';
import * as RPNInput from 'react-phone-number-input';
import flags from 'react-phone-number-input/flags';

import { useCountries } from '@/hooks/use-countries';
import { usePhoneCountries } from '@/hooks/use-phone-countries';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

interface PhoneFieldProps {
    id: string;
    label: string;
    value: string;
    onChange: (value: string) => void;
    defaultCountry?: string;
    error?: string;
    autoComplete?: string;
    className?: string;
}

function isDialableCountry(country: string): country is RPNInput.Country {
    return (RPNInput.getCountries() as string[]).includes(country);
}

function resolveInitialCountry(value: string, fallback: RPNInput.Country, defaultCountry?: string): RPNInput.Country {
    const parsed = value ? RPNInput.parsePhoneNumber(value)?.country : undefined;

    if (parsed) {
        return parsed;
    }

    return defaultCountry && isDialableCountry(defaultCountry) ? defaultCountry : fallback;
}

export function PhoneField({
    id,
    label,
    value,
    onChange,
    defaultCountry,
    error,
    autoComplete,
    className,
}: PhoneFieldProps) {
    const phoneCountries = usePhoneCountries();
    const parsed = value ? RPNInput.parsePhoneNumber(value) : undefined;
    const fallbackCountry = phoneCountries[0] ?? 'US';
    const country = parsed?.country ?? resolveInitialCountry(value, fallbackCountry, defaultCountry);
    const placeholder = getExampleNumber(country, examples)?.formatInternational();

    const syncedAddressCountry = useRef(defaultCountry);
    useEffect(() => {
        const previousCountry = syncedAddressCountry.current;
        if (defaultCountry === previousCountry) {
            return;
        }
        syncedAddressCountry.current = defaultCountry;

        const isDialCodeOnly =
            !!previousCountry &&
            isDialableCountry(previousCountry) &&
            value === `+${RPNInput.getCountryCallingCode(previousCountry)}`;

        if (value && !isDialCodeOnly) {
            return;
        }

        if (defaultCountry && isDialableCountry(defaultCountry)) {
            onChange(`+${RPNInput.getCountryCallingCode(defaultCountry)}`);
        }
    }, [defaultCountry, value, onChange]);

    return (
        <div className={className}>
            <label htmlFor={id} className="mb-2 block text-sm font-semibold text-ink">
                {label}
            </label>
            <div dir="ltr">
                <RPNInput.default
                    id={id}
                    countries={phoneCountries}
                    autoComplete={autoComplete}
                    countrySelectComponent={CountrySelect}
                    inputComponent={InputComponent}
                    international
                    smartCaret={false}
                    defaultCountry={
                        defaultCountry && isDialableCountry(defaultCountry) ? defaultCountry : fallbackCountry
                    }
                    value={(value || undefined) as RPNInput.Value | undefined}
                    onChange={(next) => onChange(next ?? '')}
                    placeholder={placeholder}
                    aria-invalid={error ? true : undefined}
                    aria-describedby={error ? `${id}-error` : undefined}
                    className={cn(
                        'flex h-11 items-stretch overflow-hidden rounded-md border bg-surface-2 transition',
                        error
                            ? 'border-error ring-1 ring-error'
                            : 'border-line-strong has-[:focus-visible]:border-primary has-[:focus-visible]:ring-1 has-[:focus-visible]:ring-primary',
                    )}
                />
            </div>
            {error && (
                <p id={`${id}-error`} className="mt-1.5 mb-0 text-sm text-error">
                    {error}
                </p>
            )}
        </div>
    );
}

const InputComponent = forwardRef<HTMLInputElement, ComponentProps<'input'>>(({ className, ...props }, ref) => (
    <input
        ref={ref}
        type="tel"
        className={cn(
            'min-w-0 flex-1 bg-transparent px-4 text-ink placeholder:text-muted focus:outline-hidden',
            className,
        )}
        {...props}
    />
));
InputComponent.displayName = 'PhoneFieldInput';

interface CountrySelectProps {
    value: RPNInput.Country;
    onChange: (value: RPNInput.Country) => void;
    options: { label: string; value: RPNInput.Country }[];
    disabled?: boolean;
}

function CountrySelect({ value, onChange, options, disabled }: CountrySelectProps) {
    const { countryNames } = useCountries();

    return (
        <div className="relative flex items-center after:pointer-events-none after:absolute after:end-0 after:top-2 after:bottom-2 after:w-px after:bg-line-strong">
            <span
                aria-hidden="true"
                className="pointer-events-none flex items-center gap-1 ps-3 pe-2 whitespace-nowrap text-muted"
            >
                <FlagComponent country={value} countryName={value} />
                <ChevronDownIcon size={14} />
            </span>
            <select
                aria-label={__('Country calling code')}
                value={value}
                disabled={disabled}
                onChange={(event) => onChange(event.target.value as RPNInput.Country)}
                className="absolute inset-0 opacity-0 focus-visible:outline-hidden"
            >
                {options
                    .filter((option) => option.value)
                    .map((option) => (
                        <option key={option.value} value={option.value}>
                            {countryNames[option.value] ?? option.label} (+
                            {RPNInput.getCountryCallingCode(option.value)})
                        </option>
                    ))}
            </select>
        </div>
    );
}

function FlagComponent({ country, countryName }: RPNInput.FlagProps) {
    const Flag = flags[country];

    return (
        <span className="flex h-4 w-6 shrink-0 items-center justify-center overflow-hidden rounded-xs [&>svg]:h-full [&>svg]:w-auto">
            {Flag ? <Flag title={countryName} /> : null}
        </span>
    );
}
