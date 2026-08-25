interface EmptyPlaceholderProps {
    title: string;
    description: string;
    action?: React.ReactNode;
}

export function EmptyPlaceholder({ title, description, action }: EmptyPlaceholderProps) {
    return (
        <div className="rounded-lg border border-dashed border-muted p-8">
            <div className="space-y-2 text-center">
                <p className="text-sm font-medium">{title}</p>
                <p className="text-xs text-muted-foreground">{description}</p>
                {action && <div className="mt-4">{action}</div>}
            </div>
        </div>
    );
}
