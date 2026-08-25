import { clampChroma, contrastRatio, formatOklch, solveLightness, type Oklch } from './color.ts';
import { type AdminThemeColor, type ThemeColors, type ThemeDefinition } from '../types/index.ts';

const AA_TEXT = 4.6;
const DESTRUCTIVE_SEPARATION = 1.6;

const DESTRUCTIVE_HUE = 25.331;
const ACHROMATIC_CHART_HUE = 259.815;

const CHART_HUE_OFFSETS = [0, 45, -45, 90, -90];
const CHART_RATIOS = [3.1, 4.3, 6, 8.5, 12];
const CHART_CHROMA = 0.14;

interface ThemeSeed {
    hue: number;
    chromaScale: number;
}

const seeds: Record<AdminThemeColor, ThemeSeed> = {
    neutral: { hue: 0, chromaScale: 0 },
    gray: { hue: 264.36, chromaScale: 0.09 },
    red: { hue: 25.33, chromaScale: 1 },
    orange: { hue: 47.6, chromaScale: 1 },
    yellow: { hue: 86.05, chromaScale: 1 },
    lime: { hue: 130.85, chromaScale: 1 },
    emerald: { hue: 162.48, chromaScale: 1 },
    teal: { hue: 182.5, chromaScale: 1 },
    cyan: { hue: 215.22, chromaScale: 1 },
    sky: { hue: 237.32, chromaScale: 1 },
    blue: { hue: 259.82, chromaScale: 1 },
    indigo: { hue: 277.12, chromaScale: 1 },
    violet: { hue: 292.72, chromaScale: 1 },
    purple: { hue: 303.9, chromaScale: 1 },
    fuchsia: { hue: 322.15, chromaScale: 1 },
    pink: { hue: 354.31, chromaScale: 1 },
};

function buildMode(seed: ThemeSeed, mode: 'light' | 'dark'): ThemeColors {
    const { hue, chromaScale } = seed;
    const isLight = mode === 'light';
    const direction = isLight ? 'darker' : 'lighter';

    const tint = chromaScale * 0.012;
    const brandChroma = clampChroma({ l: isLight ? 0.55 : 0.72, c: chromaScale * 0.4, h: hue }).c;

    const surface = (l: number, chromaMultiplier: number): Oklch => ({ l, c: tint * chromaMultiplier, h: hue });

    const background = isLight ? surface(1, 0) : surface(0.145, 1);
    const card = isLight ? surface(1, 0) : surface(0.185, 1.15);
    const sidebar = isLight ? surface(0.985, 0.4) : surface(0.185, 1.15);
    const muted = isLight ? surface(0.968, 0.6) : surface(0.24, 1.3);
    const secondary = isLight ? surface(0.955, 1) : surface(0.255, 1.4);
    const accent = isLight ? surface(0.945, 1.6) : surface(0.275, 1.5);
    const border = isLight ? surface(0.905, 0.8) : surface(0.3, 1.2);

    const textReference = accent;

    const foreground: Oklch = isLight ? { l: 0.16, c: tint * 0.9, h: hue } : { l: 0.97, c: tint * 0.7, h: hue };

    const mutedForeground = solveLightness({
        against: textReference,
        ratio: AA_TEXT,
        chroma: tint * 1.4,
        hue,
        direction,
    });

    const brandFloor = solveLightness({
        against: textReference,
        ratio: AA_TEXT,
        chroma: brandChroma,
        hue,
        direction,
    });

    const brandTarget = isLight ? 0.5 - (1 - chromaScale) * 0.28 : 0.72 + (1 - chromaScale) * 0.2;

    const primary = clampChroma({
        l: isLight ? Math.min(brandTarget, brandFloor.l) : Math.max(brandTarget, brandFloor.l),
        c: chromaScale * 0.4,
        h: hue,
    });

    const primaryForeground: Oklch = isLight ? { l: 0.99, c: tint * 0.3, h: hue } : { l: 0.15, c: tint, h: hue };

    const destructiveBase = solveLightness({
        against: textReference,
        ratio: AA_TEXT,
        chroma: clampChroma({ l: isLight ? 0.55 : 0.72, c: 0.4, h: DESTRUCTIVE_HUE }).c,
        hue: DESTRUCTIVE_HUE,
        direction,
    });

    const hueDistance = Math.abs(((hue - DESTRUCTIVE_HUE + 540) % 360) - 180);
    const sharesDestructiveHue = chromaScale > 0 && hueDistance < 45;

    let destructive = destructiveBase;
    const separationStep = isLight ? -0.02 : 0.02;

    while (
        sharesDestructiveHue &&
        contrastRatio(destructive, primary) < DESTRUCTIVE_SEPARATION &&
        destructive.l > 0.02 &&
        destructive.l < 0.98
    ) {
        destructive = clampChroma({ ...destructive, l: destructive.l + separationStep });
    }

    const input = isLight ? surface(0.905, 0.8) : surface(0.33, 1.2);

    const ring = clampChroma({ l: isLight ? 0.708 : 0.556, c: brandChroma * 0.6, h: hue });

    const chartHue = chromaScale === 0 ? ACHROMATIC_CHART_HUE : hue;
    const charts = CHART_HUE_OFFSETS.map((offset, index) =>
        solveLightness({
            against: background,
            ratio: CHART_RATIOS[index],
            chroma: CHART_CHROMA,
            hue: (chartHue + offset + 360) % 360,
            direction,
        }),
    );

    return {
        background: formatOklch(background),
        foreground: formatOklch(foreground),
        card: formatOklch(card),
        cardForeground: formatOklch(foreground),
        popover: formatOklch(card),
        popoverForeground: formatOklch(foreground),

        primary: formatOklch(primary),
        primaryForeground: formatOklch(primaryForeground),
        secondary: formatOklch(secondary),
        secondaryForeground: formatOklch(foreground),
        muted: formatOklch(muted),
        mutedForeground: formatOklch(mutedForeground),
        accent: formatOklch(accent),
        accentForeground: formatOklch(foreground),
        destructive: formatOklch(destructive),

        border: formatOklch(border),
        input: formatOklch(input),
        ring: formatOklch(ring),

        chart1: formatOklch(charts[0]),
        chart2: formatOklch(charts[1]),
        chart3: formatOklch(charts[2]),
        chart4: formatOklch(charts[3]),
        chart5: formatOklch(charts[4]),

        sidebar: formatOklch(sidebar),
        sidebarForeground: formatOklch(foreground),
        sidebarPrimary: formatOklch(primary),
        sidebarPrimaryForeground: formatOklch(primaryForeground),
        sidebarAccent: formatOklch(accent),
        sidebarAccentForeground: formatOklch(foreground),
        sidebarBorder: formatOklch(border),
        sidebarRing: formatOklch(ring),
    };
}

const cache = new Map<AdminThemeColor, ThemeDefinition>();

export function getTheme(theme: AdminThemeColor): ThemeDefinition {
    const cached = cache.get(theme);

    if (cached) {
        return cached;
    }

    const seed = seeds[theme];
    const definition: ThemeDefinition = {
        name: theme,
        light: buildMode(seed, 'light'),
        dark: buildMode(seed, 'dark'),
    };

    cache.set(theme, definition);

    return definition;
}

export const adminThemes: { name: AdminThemeColor }[] = (Object.keys(seeds) as AdminThemeColor[]).map((name) => ({
    name,
}));
