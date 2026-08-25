import type { TranslatableField } from './';
import type { SymbolPosition } from './setting';

export interface Currency {
    id: number;
    code: string;
    name: TranslatableField;
    symbol: string;
    exchange_rate: string;
    symbol_position: SymbolPosition;
    thousands_separator: string;
    decimal_separator: string;
    decimal_places: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}
