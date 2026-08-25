import { PlusIcon } from 'lucide-react';

import { ActivityTimeline } from '@/components/admin/order/activity-timeline';
import { AddOrderActivity } from '@/components/admin/order/add-order-activity';
import { SectionHeading } from '@/components/admin/section-heading';
import { Can } from '@/components/ui/can';
import { __ } from '@/lib/i18n';
import { Permission } from '@/lib/permissions';
import type { Order } from '@/types';

export function OrderActivities({ order }: { order: Order }) {
    const activities = order.activities ?? [];

    return (
        <div className="space-y-6">
            <SectionHeading>{__('Timeline')}</SectionHeading>
            <div>
                <Can permission={Permission.OrdersManage}>
                    <div className="flex gap-3">
                        <div className="flex flex-col items-center">
                            <div className="flex size-6 shrink-0 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                <PlusIcon className="size-3" />
                            </div>
                            {activities.length > 0 && <div className="my-1.5 w-px flex-1 bg-border" />}
                        </div>
                        <div className="min-w-0 flex-1 pb-6">
                            <AddOrderActivity order={order} />
                        </div>
                    </div>
                </Can>
                <ActivityTimeline activities={activities} order={order} />
            </div>
        </div>
    );
}
