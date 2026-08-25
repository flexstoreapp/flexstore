import { ProBadge } from '@/components/admin/pro/pro-badge';
import { useProUpgrade } from '@/components/admin/pro/pro-upgrade-context';
import { Card, CardContent } from '@/components/ui/card';
import { Switch } from '@/components/ui/switch';
import { __ } from '@/lib/i18n';

export function Kit() {
    const { open: openProUpgrade } = useProUpgrade();

    return (
        <Card
            className="group/item h-38 cursor-pointer shadow-xs md:h-45"
            onClick={() => openProUpgrade(__('Newsletter providers'))}
        >
            <CardContent className="flex h-full flex-col">
                <div className="flex flex-1 items-center justify-center opacity-60">
                    <KitLogo className="h-10" />
                </div>

                <div className="flex items-center justify-between">
                    <ProBadge />
                    <Switch
                        checked={false}
                        disabled
                        aria-label={__('Available in Pro')}
                        onClick={(event) => event.stopPropagation()}
                    />
                </div>
            </CardContent>
        </Card>
    );
}

function KitLogo(props: React.SVGAttributes<SVGElement>) {
    return (
        <svg {...props} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 574 259" fill="currentColor">
            <path d="M157.146 107.772c70.593 13.663 92.504 79.014 93.082 144.743a2.66 2.66 0 0 1-2.66 2.685h-88.863a2.667 2.667 0 0 1-2.668-2.652c-.267-50.994-8.529-95.995-59.057-97.938a2.663 2.663 0 0 0-2.764 2.66v95.271a2.66 2.66 0 0 1-2.66 2.659H2.677a2.658 2.658 0 0 1-2.66-2.659V9.788a2.66 2.66 0 0 1 2.66-2.66h88.88a2.66 2.66 0 0 1 2.66 2.66v90.935a2.442 2.442 0 0 0 4.774.72c22.895-74.989 65.654-93.844 135.086-94.308a2.665 2.665 0 0 1 2.681 2.66v90.71a2.66 2.66 0 0 1-2.66 2.66h-76.506a2.326 2.326 0 0 0-.445 4.607Zm243.325 56.846v-58.793a2.66 2.66 0 0 1 2.66-2.66h65.405a2.331 2.331 0 0 0 2.331-2.331 2.335 2.335 0 0 0-1.89-2.287c-51.174-10.15-74.797-39.609-75.613-88.757a2.616 2.616 0 0 1 2.608-2.662h96.04a2.66 2.66 0 0 1 2.66 2.66V51.19a2.66 2.66 0 0 0 2.66 2.66H552.4a2.66 2.66 0 0 1 2.66 2.66v43.994a2.66 2.66 0 0 1-2.66 2.66h-55.068a2.66 2.66 0 0 0-2.66 2.66v47.423c0 16.754 10.272 22.279 23.932 22.279 21.406 0 42.522-9.645 50.991-14.017 1.772-.914 3.878.373 3.878 2.364V237.7a5.312 5.312 0 0 1-2.834 4.703c-8.36 4.398-34.209 16.592-63.779 16.592-60.813.005-106.389-24.786-106.389-94.377Zm-125.019 87.923V105.82a2.66 2.66 0 0 1 2.66-2.66h88.881a2.66 2.66 0 0 1 2.66 2.66v146.721a2.66 2.66 0 0 1-2.66 2.659h-88.881a2.658 2.658 0 0 1-2.66-2.659Zm-5.033-207.322c0 24.973 17.636 45.219 51.518 45.219 33.882 0 51.518-20.246 51.518-45.22C373.455 20.247 355.817 0 321.937 0c-33.882 0-51.518 20.246-51.518 45.219Z" />
        </svg>
    );
}
