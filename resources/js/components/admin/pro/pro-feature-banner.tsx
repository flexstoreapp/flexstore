import { ProBadge } from '@/components/admin/pro/pro-badge';
import { useProUpgrade } from '@/components/admin/pro/pro-upgrade-context';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { __ } from '@/lib/i18n';

export function ProFeatureBanner({ title, description }: { title: string; description: string }) {
    const { open: openProUpgrade } = useProUpgrade();

    return (
        <Card className="border-dashed shadow-none">
            <CardContent className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div className="space-y-1">
                    <div className="flex items-center gap-2">
                        <h3 className="text-sm font-medium">{title}</h3>
                        <ProBadge />
                    </div>
                    <p className="text-sm text-muted-foreground">{description}</p>
                </div>

                <Button variant="outline" size="sm" onClick={() => openProUpgrade(title)}>
                    {__('Learn more')}
                </Button>
            </CardContent>
        </Card>
    );
}
