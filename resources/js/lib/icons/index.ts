import { type IconComponent, LUCIDE_ICONS } from './lucide';
import { ICON_NAMES, type IconName } from './names';

export type { IconComponent, IconName };
export { ICON_NAMES };

const ICON_LIBRARY = LUCIDE_ICONS;

export const ICONS: { name: IconName; component: IconComponent }[] = ICON_NAMES.map((name) => ({
    name,
    component: ICON_LIBRARY[name],
}));

export function getIconComponent(iconName: string): IconComponent | null {
    return ICON_LIBRARY[iconName.toLowerCase() as IconName] ?? null;
}

export function getIconLabel(iconName: string): string {
    return iconName.replace(/-/g, ' ').replace(/^./, (character) => character.toUpperCase());
}
