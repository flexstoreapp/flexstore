import { PlusIcon, XIcon } from 'lucide-react';
import { useState } from 'react';

import { ExpandableCard } from '@/components/admin/expandable-card';
import { FormDirtySignal } from '@/components/admin/form-dirty-signal';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldLabel, FieldLegend, FieldSet } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { StarRating } from '@/components/ui/star-rating';
import { Textarea } from '@/components/ui/textarea';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import type { TestimonialsSettings } from '@/types';

interface TestimonialsFieldsProps {
    settings?: TestimonialsSettings;
    errors: Record<string, string>;
}

type TestimonialItemLocal = {
    quote: string;
    author_name: string;
    rating: number;
    _id: string;
};

export function TestimonialsFields({ settings, errors }: TestimonialsFieldsProps) {
    const [testimonials, setTestimonials] = useState<TestimonialItemLocal[]>(
        settings?.testimonials.map((testimonial) => ({
            quote: getTranslation(testimonial.quote),
            author_name: getTranslation(testimonial.author_name),
            rating: testimonial.rating,
            _id: crypto.randomUUID(),
        })) ?? [{ quote: '', author_name: '', rating: 5, _id: crypto.randomUUID() }],
    );
    const [expandedIndex, setExpandedIndex] = useState<number | null>(settings ? null : 0);

    const addTestimonial = () => {
        if (testimonials.length < 6) {
            setTestimonials([...testimonials, { quote: '', author_name: '', rating: 5, _id: crypto.randomUUID() }]);
            setExpandedIndex(testimonials.length);
        }
    };

    const removeTestimonial = (id: string, index: number) => {
        if (testimonials.length > 1) {
            setTestimonials(testimonials.filter((testimonial) => testimonial._id !== id));
            if (expandedIndex === index) {
                setExpandedIndex(null);
            }
        }
    };

    const updateTestimonial = (id: string, updates: Partial<Omit<TestimonialItemLocal, '_id'>>) => {
        setTestimonials(testimonials.map((t) => (t._id === id ? { ...t, ...updates } : t)));
    };

    return (
        <>
            <FormDirtySignal signal={testimonials.map((t) => t._id).join(',')} />
            <FieldSet className="gap-2">
                <FieldLegend variant="label">
                    {__('Testimonials')} ({testimonials.length})
                </FieldLegend>

                {testimonials.map((testimonial, index) => (
                    <div key={testimonial._id}>
                        <input type="hidden" name={`settings.testimonials.${index}.quote`} value={testimonial.quote} />
                        <input
                            type="hidden"
                            name={`settings.testimonials.${index}.author_name`}
                            value={testimonial.author_name}
                        />
                        <input
                            type="hidden"
                            name={`settings.testimonials.${index}.rating`}
                            value={testimonial.rating}
                        />

                        <ExpandableCard
                            isExpanded={expandedIndex === index}
                            onToggle={() => setExpandedIndex(expandedIndex === index ? null : index)}
                            title={testimonial.author_name || __('Testimonial :number', { number: index + 1 })}
                            action={
                                testimonials.length > 1 && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon-sm"
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            removeTestimonial(testimonial._id, index);
                                        }}
                                    >
                                        <XIcon />
                                    </Button>
                                )
                            }
                        >
                            <Field>
                                <FieldLabel htmlFor={`settings-testimonials-${index}-quote`}>{__('Quote')}</FieldLabel>
                                <Textarea
                                    id={`settings-testimonials-${index}-quote`}
                                    value={testimonial.quote}
                                    onChange={(e) => updateTestimonial(testimonial._id, { quote: e.target.value })}
                                    placeholder={__('Easily the smoothest online shopping I have had this year.')}
                                    rows={3}
                                    className="max-h-32"
                                />
                                <FieldError>{errors[`settings.testimonials.${index}.quote`]}</FieldError>
                            </Field>

                            <Field>
                                <FieldLabel htmlFor={`settings-testimonials-${index}-author-name`}>
                                    {__('Author name')}
                                </FieldLabel>
                                <Input
                                    id={`settings-testimonials-${index}-author-name`}
                                    value={testimonial.author_name}
                                    onChange={(e) =>
                                        updateTestimonial(testimonial._id, { author_name: e.target.value })
                                    }
                                    placeholder={__('Sarah M.')}
                                />
                                <FieldError>{errors[`settings.testimonials.${index}.author_name`]}</FieldError>
                            </Field>

                            <Field>
                                <FieldLabel htmlFor={`settings-testimonials-${index}-rating`}>
                                    {__('Rating')}
                                </FieldLabel>
                                <StarRating
                                    id={`settings-testimonials-${index}-rating`}
                                    value={testimonial.rating}
                                    onChange={(rating) => updateTestimonial(testimonial._id, { rating })}
                                />
                                <FieldError>{errors[`settings.testimonials.${index}.rating`]}</FieldError>
                            </Field>
                        </ExpandableCard>
                    </div>
                ))}

                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    className="w-fit"
                    onClick={addTestimonial}
                    disabled={testimonials.length >= 6}
                >
                    <PlusIcon />
                    {__('Add testimonial')}
                </Button>
            </FieldSet>
        </>
    );
}
