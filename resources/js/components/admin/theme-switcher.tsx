import { CheckIcon, MonitorIcon, MoonIcon, PaletteIcon, SunIcon } from 'lucide-react';
import { type ReactNode } from 'react';

import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import { useAdminTheme } from '@/contexts/admin-theme-context';
import { adminThemes } from '@/lib/admin-theme-config';
import { getThemePreview } from '@/lib/admin-theme-utils';
import { __ } from '@/lib/i18n';
import type { AdminThemeColor, Appearance } from '@/types';

const appearanceIcons: Record<string, ReactNode> = {
    light: <SunIcon />,
    dark: <MoonIcon />,
    system: <MonitorIcon />,
};

function themeLabel(name: AdminThemeColor): string {
    switch (name) {
        case 'neutral':
            return __('Neutral');
        case 'gray':
            return __('Gray');
        case 'red':
            return __('Red');
        case 'orange':
            return __('Orange');
        case 'yellow':
            return __('Yellow');
        case 'lime':
            return __('Lime');
        case 'emerald':
            return __('Emerald');
        case 'teal':
            return __('Teal');
        case 'cyan':
            return __('Cyan');
        case 'sky':
            return __('Sky');
        case 'blue':
            return __('Blue');
        case 'indigo':
            return __('Indigo');
        case 'violet':
            return __('Violet');
        case 'purple':
            return __('Purple');
        case 'fuchsia':
            return __('Fuchsia');
        case 'pink':
            return __('Pink');
    }
}

export function ThemeSwitcher() {
    const { appearance, themeColor, setAppearance, setThemeColor, isDark } = useAdminTheme();

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon-md">
                    <PaletteIcon />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-80">
                <Tabs defaultValue="appearance" className="w-full">
                    <TabsList className="grid w-full grid-cols-2">
                        <TabsTrigger value="appearance">{__('Appearance')}</TabsTrigger>
                        <TabsTrigger value="themes">{__('Theme color')}</TabsTrigger>
                    </TabsList>

                    <TabsContent value="appearance" className="mt-2">
                        <div className="space-y-1">
                            {(['light', 'dark', 'system'] as Appearance[]).map((mode) => (
                                <DropdownMenuItem key={mode} onClick={() => setAppearance(mode)} className="gap-2">
                                    {appearanceIcons[mode]}
                                    {mode === 'light' && __('Light')}
                                    {mode === 'dark' && __('Dark')}
                                    {mode === 'system' && __('System')}
                                    {appearance === mode && <CheckIcon className="ms-auto" />}
                                </DropdownMenuItem>
                            ))}
                        </div>
                    </TabsContent>

                    <TabsContent value="themes" className="mt-2">
                        <ScrollArea className="h-64">
                            <div className="grid grid-cols-2 gap-1">
                                {adminThemes.map((themeOption) => (
                                    <DropdownMenuItem
                                        key={themeOption.name}
                                        onClick={() => setThemeColor(themeOption.name)}
                                        className="justify-start gap-2"
                                    >
                                        <div
                                            className="size-4 rounded-full border"
                                            style={{ backgroundColor: getThemePreview(themeOption.name, isDark) }}
                                        />
                                        <span className="text-sm">{themeLabel(themeOption.name)}</span>
                                        {themeColor === themeOption.name && <CheckIcon className="ms-auto" />}
                                    </DropdownMenuItem>
                                ))}
                            </div>
                        </ScrollArea>
                    </TabsContent>
                </Tabs>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
