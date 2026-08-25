import { cn } from '@/lib/utils';

const proseClass = cn(
    'prose max-w-none prose-neutral',
    'prose-headings:font-semibold prose-headings:tracking-tight prose-headings:text-ink prose-h2:mt-10 prose-h3:mt-8',
    'prose-p:leading-relaxed prose-p:text-body',
    'prose-a:font-medium prose-a:text-primary prose-a:no-underline prose-a:underline-offset-2 prose-a:hover:underline',
    'prose-strong:text-ink prose-li:text-body prose-li:marker:text-muted',
    'prose-blockquote:border-s-primary prose-blockquote:text-body prose-hr:border-line',
);

export function ArticleBody({ html, className }: { html: string; className?: string }) {
    return <div className={cn(proseClass, className)} dangerouslySetInnerHTML={{ __html: html }} />;
}
