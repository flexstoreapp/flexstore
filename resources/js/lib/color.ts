export interface Oklch {
    l: number;
    c: number;
    h: number;
}

const GAMUT_EPSILON = 0.0001;

function oklchToLinearRgb({ l, c, h }: Oklch): [number, number, number] {
    const hRad = (h * Math.PI) / 180;
    const a = c * Math.cos(hRad);
    const b = c * Math.sin(hRad);

    const lCone = (l + 0.3963377774 * a + 0.2158037573 * b) ** 3;
    const mCone = (l - 0.1055613458 * a - 0.0638541728 * b) ** 3;
    const sCone = (l - 0.0894841775 * a - 1.291485548 * b) ** 3;

    return [
        4.0767416621 * lCone - 3.3077115913 * mCone + 0.2309699292 * sCone,
        -1.2684380046 * lCone + 2.6097574011 * mCone - 0.3413193965 * sCone,
        -0.0041960863 * lCone - 0.7034186147 * mCone + 1.707614701 * sCone,
    ];
}

function isInGamut(color: Oklch): boolean {
    return oklchToLinearRgb(color).every((channel) => channel >= -GAMUT_EPSILON && channel <= 1 + GAMUT_EPSILON);
}

export function clampChroma(color: Oklch): Oklch {
    if (color.c === 0 || isInGamut(color)) {
        return color;
    }

    let low = 0;
    let high = color.c;

    for (let i = 0; i < 24; i++) {
        const mid = (low + high) / 2;

        if (isInGamut({ ...color, c: mid })) {
            low = mid;
        } else {
            high = mid;
        }
    }

    return { ...color, c: low };
}

function relativeLuminance(color: Oklch): number {
    const [r, g, b] = oklchToLinearRgb(clampChroma(color)).map((channel) => Math.min(1, Math.max(0, channel)));

    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

export function contrastRatio(a: Oklch, b: Oklch): number {
    const luminanceA = relativeLuminance(a);
    const luminanceB = relativeLuminance(b);
    const lighter = Math.max(luminanceA, luminanceB);
    const darker = Math.min(luminanceA, luminanceB);

    return (lighter + 0.05) / (darker + 0.05);
}

export function solveLightness(options: {
    against: Oklch;
    ratio: number;
    chroma: number;
    hue: number;
    direction: 'darker' | 'lighter';
}): Oklch {
    const { against, ratio, chroma, hue, direction } = options;
    const meets = (l: number) => contrastRatio({ l, c: chroma, h: hue }, against) >= ratio;

    let low = direction === 'darker' ? 0 : against.l;
    let high = direction === 'darker' ? against.l : 1;

    if (!meets(direction === 'darker' ? low : high)) {
        return clampChroma({ l: direction === 'darker' ? 0 : 1, c: chroma, h: hue });
    }

    for (let i = 0; i < 24; i++) {
        const mid = (low + high) / 2;

        if (direction === 'darker') {
            if (meets(mid)) {
                low = mid;
            } else {
                high = mid;
            }
        } else {
            if (meets(mid)) {
                high = mid;
            } else {
                low = mid;
            }
        }
    }

    return clampChroma({ l: direction === 'darker' ? low : high, c: chroma, h: hue });
}

export function formatOklch(color: Oklch): string {
    const { l, c, h } = clampChroma(color);
    const round = (value: number, places: number) => Number(value.toFixed(places));

    if (round(c, 4) === 0) {
        return `oklch(${round(l, 4)} 0 0)`;
    }

    return `oklch(${round(l, 4)} ${round(c, 4)} ${round(h, 2)})`;
}
