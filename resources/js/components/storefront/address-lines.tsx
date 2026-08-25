import { useCountries } from '@/hooks/use-countries';

interface AddressParts {
    first_name?: string | null;
    last_name?: string | null;
    address_line_1?: string | null;
    address_line_2?: string | null;
    city?: string | null;
    state?: string | null;
    postal_code?: string | null;
    country_code?: string | null;
    phone?: string | null;
}

function joinTruthy(parts: (string | null | undefined)[], separator: string): string {
    return parts.filter(Boolean).join(separator);
}

export function AddressLines({ address, className }: { address: AddressParts; className?: string }) {
    const { countryNames } = useCountries();

    const name = joinTruthy([address.first_name, address.last_name], ' ');
    const locality = joinTruthy([address.city, joinTruthy([address.state, address.postal_code], ' ')], ', ');
    const country = address.country_code ? countryNames[address.country_code] : null;

    return (
        <div className={className}>
            {name && <div className="font-semibold text-ink">{name}</div>}
            <address className="mt-2 text-sm leading-relaxed text-muted not-italic">
                {address.address_line_1 && <div>{address.address_line_1}</div>}
                {address.address_line_2 && <div>{address.address_line_2}</div>}
                {locality && <div>{locality}</div>}
                {country && <div>{country}</div>}
            </address>
            {address.phone && (
                <p className="mt-2 mb-0 text-sm text-muted">
                    <bdi>{address.phone}</bdi>
                </p>
            )}
        </div>
    );
}
