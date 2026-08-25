export interface AddressFieldRule {
    label: string;
    required: boolean;
    hidden: boolean;
    options: { value: string; label: string }[] | null;
}

export interface AddressFieldRules {
    country_code: string;
    city: AddressFieldRule;
    state: AddressFieldRule;
    postal_code: AddressFieldRule;
}
