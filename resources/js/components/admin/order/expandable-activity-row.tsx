import { ChevronDownIcon } from 'lucide-react';
import { type ReactNode, useState } from 'react';

import { ActivityTimestamp } from '@/components/admin/activity-timestamp';
import { cn } from '@/lib/utils';

import { CollapsibleSection } from './collapsible-section';

interface ExpandableActivityRowProps {
    label: string;
    date: string;
    expandable: boolean;
    children?: ReactNode;
}

export function ExpandableActivityRow({ label, date, expandable, children }: ExpandableActivityRowProps) {
    const [expanded, setExpanded] = useState(false);

    if (!expandable) {
        return (
            <div className="flex items-start justify-between gap-3">
                <p className="pt-0.5 text-sm text-foreground">{label}</p>
                <ActivityTimestamp date={date} />
            </div>
        );
    }

    return (
        <div>
            <div className="flex items-start justify-between gap-3">
                <button
                    type="button"
                    className="flex cursor-pointer items-center gap-2 pt-0.5 text-sm"
                    onClick={() => setExpanded((value) => !value)}
                >
                    <span>{label}</span>
                    <ChevronDownIcon
                        className={cn(
                            'size-3.5 text-muted-foreground transition-transform duration-200',
                            expanded && 'rotate-180',
                        )}
                    />
                </button>
                <ActivityTimestamp date={date} />
            </div>

            <CollapsibleSection open={expanded}>
                <div className="pt-2">{children}</div>
            </CollapsibleSection>
        </div>
    );
}
