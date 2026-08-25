import { SectionFrame } from '@/components/storefront/section-frame';
import { SectionHeader } from '@/components/storefront/section-header';
import { Section } from '@/components/storefront/section-shell';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import type { TestimonialsSectionData } from '@/types';

export function TestimonialsSection({ section }: { section: TestimonialsSectionData }) {
    const { testimonials } = section.settings;
    const title = getTranslation(section.title);

    if (testimonials.length === 0) {
        return null;
    }

    return (
        <Section>
            <SectionHeader title={title} />

            <SectionFrame cols="grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
                {testimonials.map((testimonial, index) => {
                    const rating = Math.max(0, Math.min(5, testimonial.rating));
                    const quote = getTranslation(testimonial.quote);
                    const authorName = getTranslation(testimonial.author_name);

                    return (
                        <figure key={index} className="flex flex-col border-e border-b border-line p-8">
                            <div
                                className="tracking-star text-amber"
                                role="img"
                                aria-label={__('Rated :rating out of 5', { rating })}
                            >
                                {'★'.repeat(rating)}
                                {'☆'.repeat(5 - rating)}
                            </div>
                            <blockquote className="m-0 mt-4 flex-1 text-lg leading-relaxed font-medium text-ink">
                                “{quote}”
                            </blockquote>
                            <figcaption className="mt-6 text-sm font-semibold tracking-wide text-muted uppercase">
                                {authorName}
                            </figcaption>
                        </figure>
                    );
                })}
            </SectionFrame>
        </Section>
    );
}
