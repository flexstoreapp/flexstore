interface TrackingLinkProps {
    trackingNumber: string;
    trackingUrl?: string | null;
}

export function TrackingLink({ trackingNumber, trackingUrl }: TrackingLinkProps) {
    if (!trackingUrl) {
        return <bdi className="text-primary">{trackingNumber}</bdi>;
    }

    return (
        <a
            href={trackingUrl}
            target="_blank"
            rel="noopener noreferrer"
            className="text-primary underline-offset-2 hover:underline"
        >
            <bdi>{trackingNumber}</bdi>
        </a>
    );
}
