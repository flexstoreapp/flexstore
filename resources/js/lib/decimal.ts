/**
 * Precision-safe decimal arithmetic for monetary values.
 *
 * All functions accept string or number inputs and return string results
 * rounded to the specified number of decimal places (default: 4, matching
 * the backend's BigDecimal storage precision).
 *
 * Internally, values are scaled to integers before arithmetic to avoid
 * IEEE 754 floating-point drift.
 */

const DEFAULT_PRECISION = 4;

function toInt(value: string | number, factor: number): number {
    return Math.round(Number(value) * factor);
}

function fmt(intValue: number, factor: number, precision: number): string {
    return (intValue / factor).toFixed(precision);
}

export function add(a: string | number, b: string | number, precision = DEFAULT_PRECISION): string {
    const factor = 10 ** precision;
    return fmt(toInt(a, factor) + toInt(b, factor), factor, precision);
}

export function subtract(a: string | number, b: string | number, precision = DEFAULT_PRECISION): string {
    const factor = 10 ** precision;
    return fmt(toInt(a, factor) - toInt(b, factor), factor, precision);
}

export function multiply(a: string | number, b: string | number, precision = DEFAULT_PRECISION): string {
    const factor = 10 ** precision;
    return fmt(Math.round(Number(a) * Number(b) * factor), factor, precision);
}

export function divide(a: string | number, b: string | number, precision = DEFAULT_PRECISION): string {
    const divisor = Number(b);
    if (divisor === 0) return (0).toFixed(precision);
    const factor = 10 ** precision;
    return fmt(Math.round((Number(a) / divisor) * factor), factor, precision);
}

export function sum(values: (string | number)[], precision = DEFAULT_PRECISION): string {
    const factor = 10 ** precision;
    let total = 0;
    for (const v of values) {
        total += toInt(v, factor);
    }
    return fmt(total, factor, precision);
}

export function gt(a: string | number, b: string | number, precision = DEFAULT_PRECISION): boolean {
    const factor = 10 ** precision;
    return toInt(a, factor) > toInt(b, factor);
}

export function isPositive(value: string | number, precision = DEFAULT_PRECISION): boolean {
    return toInt(value, 10 ** precision) > 0;
}

export function isZero(value: string | number, precision = DEFAULT_PRECISION): boolean {
    return toInt(value, 10 ** precision) === 0;
}
