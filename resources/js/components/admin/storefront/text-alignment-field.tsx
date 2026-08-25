import { AlignCenterIcon, AlignLeftIcon, AlignRightIcon, type LucideIcon } from 'lucide-react';

import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { SectionTextAlign } from '@/types';

interface TextAlignmentFieldProps {
    name: string;
    value?: SectionTextAlign;
    onChange: (value: SectionTextAlign) => void;
    label?: string;
    error?: string;
}

const POSITIONS: SectionTextAlign[] = [
    'top-left',
    'top-center',
    'top-right',
    'left',
    'center',
    'right',
    'bottom-left',
    'bottom-center',
    'bottom-right',
];

function positionLabel(position: SectionTextAlign): string {
    return {
        'top-left': __('Top left'),
        'top-center': __('Top center'),
        'top-right': __('Top right'),
        left: __('Left'),
        center: __('Center'),
        right: __('Right'),
        'bottom-left': __('Bottom left'),
        'bottom-center': __('Bottom center'),
        'bottom-right': __('Bottom right'),
    }[position];
}

function glyphClasses(position: SectionTextAlign): { justify: string; items: string; Icon: LucideIcon } {
    const [vertical, horizontal] = position.includes('-')
        ? (position.split('-') as [string, string])
        : ['middle', position];

    return {
        justify: vertical === 'top' ? 'justify-start' : vertical === 'bottom' ? 'justify-end' : 'justify-center',
        items: horizontal === 'center' ? 'items-center' : horizontal === 'right' ? 'items-end' : 'items-start',
        Icon: horizontal === 'center' ? AlignCenterIcon : horizontal === 'right' ? AlignRightIcon : AlignLeftIcon,
    };
}

export function TextAlignmentField({ name, value = 'left', onChange, label, error }: TextAlignmentFieldProps) {
    return (
        <Field>
            <FieldLabel htmlFor={`${name.replaceAll('.', '-')}-value`}>{label ?? __('Text alignment')}</FieldLabel>
            <input id={`${name.replaceAll('.', '-')}-value`} type="hidden" name={name} value={value} />
            <div>
                <div
                    role="radiogroup"
                    aria-label={label ?? 'Text alignment'}
                    className="grid w-fit grid-cols-3 overflow-hidden rounded-md border border-input"
                >
                    {POSITIONS.map((position) => {
                        const selected = value === position;
                        const { justify, items, Icon } = glyphClasses(position);

                        return (
                            <button
                                key={position}
                                type="button"
                                role="radio"
                                aria-checked={selected}
                                aria-label={positionLabel(position)}
                                title={positionLabel(position)}
                                onClick={() => onChange(position)}
                                className={cn(
                                    'group/align flex h-12 w-[68px] flex-col border-input p-2 transition-colors focus-visible:outline-2 focus-visible:-outline-offset-2 [&:not(:nth-child(3n))]:border-e [&:nth-child(-n+6)]:border-b',
                                    justify,
                                    items,
                                    selected ? 'bg-primary' : 'hover:bg-accent',
                                )}
                            >
                                <Icon
                                    size={16}
                                    strokeWidth={2}
                                    aria-hidden="true"
                                    className={cn(
                                        'transition-colors',
                                        selected
                                            ? 'text-primary-foreground'
                                            : 'text-muted-foreground group-hover/align:text-foreground',
                                    )}
                                />
                            </button>
                        );
                    })}
                </div>
            </div>
            {error && <FieldError>{error}</FieldError>}
        </Field>
    );
}
