import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useCountries } from '@/hooks/use-countries';
import { __ } from '@/lib/i18n';

interface DisplayableAddress {
    first_name?: string | null;
    last_name?: string | null;
    address_line_1?: string | null;
    address_line_2?: string | null;
    city?: string | null;
    state?: string | null;
    state_name?: string | null;
    postal_code?: string | null;
    country_code?: string | null;
    phone?: string | null;
}

interface AddressSummaryCardProps {
    title: string;
    description?: string;
    address: DisplayableAddress | null | undefined;
}

export function AddressSummaryCard({ title, description, address }: AddressSummaryCardProps) {
    const { countryNames } = useCountries();

    return (
        <Card>
            <CardHeader>
                <CardTitle>{title}</CardTitle>
                {description && <CardDescription>{description}</CardDescription>}
            </CardHeader>

            <CardContent className="space-y-1 text-sm tracking-wide">
                {address ? (
                    <>
                        <p className="mb-2 font-medium">
                            {address.first_name} {address.last_name}
                        </p>
                        {address.address_line_1 && <p className="text-muted-foreground">{address.address_line_1}</p>}
                        {address.address_line_2 && <p className="text-muted-foreground">{address.address_line_2}</p>}
                        {(address.city || address.state || address.postal_code) && (
                            <p className="text-muted-foreground">
                                {[address.city, address.state_name ?? address.state, address.postal_code]
                                    .filter(Boolean)
                                    .join(', ')}
                            </p>
                        )}
                        {address.country_code && (
                            <p className="text-muted-foreground">
                                {countryNames[address.country_code] ?? address.country_code}
                            </p>
                        )}
                        {address.phone && (
                            <p className="mt-2 text-muted-foreground">
                                <a href={`tel:${address.phone}`} className="hover:underline">
                                    <bdi>{address.phone}</bdi>
                                </a>
                            </p>
                        )}
                    </>
                ) : (
                    <p className="text-muted-foreground">{__('Not provided')}</p>
                )}
            </CardContent>
        </Card>
    );
}
