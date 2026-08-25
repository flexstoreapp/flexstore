import { useEffect, useState } from 'react';

import * as AddressFieldRulesController from '@/actions/App/Http/Controllers/AddressFieldRulesController';
import { httpGet } from '@/lib/http';
import type { AddressFieldRules } from '@/types';

export type StateOption = { value: string; label: string };

export const DEFAULT_ADDRESS_FIELD_RULES: AddressFieldRules = {
    country_code: '',
    city: { label: 'City', required: true, hidden: false, options: null },
    state: { label: 'State', required: false, hidden: false, options: null },
    postal_code: { label: 'Postal code', required: true, hidden: false, options: null },
};

export function isAddressReadyForRates(
    address: {
        country_code?: string | null;
        city?: string | null;
        state?: string | null;
        postal_code?: string | null;
    },
    rules: AddressFieldRules,
): boolean {
    if (!address.country_code) return false;
    if (rules.city.required && !rules.city.hidden && !(address.city ?? '').trim()) return false;
    if (rules.state.required && !rules.state.hidden && !(address.state ?? '').trim()) return false;
    if (rules.postal_code.required && !rules.postal_code.hidden && !(address.postal_code ?? '').trim()) return false;
    return true;
}

export function rateRelevantAddressKey(
    address:
        | {
              country_code?: string | null;
              state?: string | null;
              postal_code?: string | null;
              city?: string | null;
          }
        | null
        | undefined,
): string {
    if (!address?.country_code) return '';
    return `${address.country_code}|${address.state ?? ''}|${address.postal_code ?? ''}|${address.city ?? ''}`;
}

const cache = new Map<string, AddressFieldRules>();
const inflight = new Map<string, Promise<AddressFieldRules>>();

function writeCache(code: string, fieldRules: AddressFieldRules): void {
    const key = code.toUpperCase();
    if (!key) return;
    cache.set(key, fieldRules);
}

function fetchRules(code: string): Promise<AddressFieldRules> {
    const key = code.toUpperCase();

    const cached = cache.get(key);
    if (cached) return Promise.resolve(cached);

    const existing = inflight.get(key);
    if (existing) return existing;

    const request = httpGet<AddressFieldRules>(AddressFieldRulesController.show(key).url)
        .then((data) => {
            const rules: AddressFieldRules = {
                ...DEFAULT_ADDRESS_FIELD_RULES,
                ...data,
                country_code: data.country_code || key,
            };
            writeCache(key, rules);
            inflight.delete(key);
            return rules;
        })
        .catch((error) => {
            inflight.delete(key);
            throw error;
        });

    inflight.set(key, request);
    return request;
}

function resolve(code: string, initial?: AddressFieldRules): AddressFieldRules {
    if (!code) return DEFAULT_ADDRESS_FIELD_RULES;
    const cached = cache.get(code);
    if (cached) return cached;
    if (initial?.country_code && initial.country_code.toUpperCase() === code) return initial;
    return { ...DEFAULT_ADDRESS_FIELD_RULES, country_code: code };
}

function isPending(code: string, initial?: AddressFieldRules): boolean {
    return code !== '' && !cache.has(code) && initial?.country_code?.toUpperCase() !== code;
}

export function useAddressFieldRules(
    countryCode: string,
    initial?: AddressFieldRules,
): { fieldRules: AddressFieldRules; loading: boolean } {
    const code = (countryCode ?? '').toUpperCase();

    const [trackedCode, setTrackedCode] = useState(code);
    const [fieldRules, setFieldRules] = useState<AddressFieldRules>(() => resolve(code, initial));
    const [loading, setLoading] = useState(() => isPending(code, initial));

    if (code !== trackedCode) {
        setTrackedCode(code);
        setFieldRules(resolve(code, initial));
        setLoading(isPending(code, initial));
    }

    useEffect(() => {
        if (initial?.country_code) {
            writeCache(initial.country_code, initial);
        }
    }, [initial]);

    useEffect(() => {
        if (!code || cache.has(code)) {
            return;
        }

        let active = true;

        fetchRules(code)
            .then((rules) => {
                if (!active) return;
                setFieldRules(rules);
                setLoading(false);
            })
            .catch(() => {
                if (active) setLoading(false);
            });

        return () => {
            active = false;
        };
    }, [code]);

    return { fieldRules, loading };
}

function getOptionsFromCache(codes: string[]): StateOption[] | null {
    if (codes.length === 0) return null;
    const opts: StateOption[] = [];
    for (const code of codes) {
        const fieldRules = cache.get(code);
        if (fieldRules?.state.options) {
            opts.push(...fieldRules.state.options);
        }
    }
    return opts.length > 0 ? opts : null;
}

export function useStateOptions(countryCodes: string[]): { options: StateOption[] | null; loading: boolean } {
    const codes = countryCodes.map((c) => c.toUpperCase()).filter(Boolean);
    const codesKey = codes.join(',');

    const [trackedKey, setTrackedKey] = useState(codesKey);
    const [options, setOptions] = useState<StateOption[] | null>(() => getOptionsFromCache(codes));
    const [loading, setLoading] = useState(() => codes.length > 0 && codes.some((c) => !cache.has(c)));

    if (codesKey !== trackedKey) {
        setTrackedKey(codesKey);
        setOptions(getOptionsFromCache(codes));
        setLoading(codes.length > 0 && codes.some((c) => !cache.has(c)));
    }

    useEffect(() => {
        const currentCodes = codesKey ? codesKey.split(',') : [];
        const toFetch = currentCodes.filter((c) => !cache.has(c));
        if (toFetch.length === 0) return;

        Promise.all(toFetch.map((code) => fetchRules(code).catch(() => {}))).then(() => {
            setOptions(getOptionsFromCache(currentCodes));
            setLoading(false);
        });
    }, [codesKey]);

    return { options, loading };
}
