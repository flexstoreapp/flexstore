import { HelpBlock } from '@/components/ui/help-block';

export function HeadingSmall({ title, description }: { title: string; description?: string }) {
    return (
        <header>
            <h3 className="mb-0.5 text-base font-medium">{title}</h3>
            <HelpBlock>{description}</HelpBlock>
        </header>
    );
}
