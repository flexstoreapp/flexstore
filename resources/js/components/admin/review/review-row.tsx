import { memo } from 'react';

import { StatusBadge } from '@/components/admin/status-badge';
import { Thumbnail } from '@/components/admin/thumbnail';
import { Can } from '@/components/ui/can';
import { Checkbox } from '@/components/ui/checkbox';
import { StarRating } from '@/components/ui/star-rating';
import { TableCell, TableRow } from '@/components/ui/table';
import { useFormatTime } from '@/hooks/admin/use-format-time';
import { useFormatDate } from '@/hooks/use-format-date';
import { __ } from '@/lib/i18n';
import { mediaAlt, mediaSmallThumb } from '@/lib/media';
import { Permission } from '@/lib/permissions';
import { getTranslation } from '@/lib/utils';
import type { Review } from '@/types';

interface ReviewRowProps {
    review: Review;
    isSelected: boolean;
    onSelectReview: (reviewId: number, shiftKey?: boolean) => void;
    onEdit?: (review: Review) => void;
}

export const ReviewRow = memo(({ review, isSelected, onSelectReview, onEdit }: ReviewRowProps) => {
    const formatDate = useFormatDate();
    const formatTime = useFormatTime();

    const handleSelectReview = (e: React.MouseEvent) => {
        e.stopPropagation();
        onSelectReview(review.id, e.shiftKey);
    };

    const handleRowClick = () => {
        if (onEdit) {
            onEdit(review);
        }
    };

    const getStatusLabel = (status: Review['status']) => {
        switch (status) {
            case 'approved':
                return __('Approved');
            case 'rejected':
                return __('Rejected');
            case 'pending':
            default:
                return __('Pending');
        }
    };

    return (
        <TableRow
            key={review.id}
            data-state={isSelected && 'selected'}
            onClick={onEdit ? handleRowClick : undefined}
            className={onEdit ? 'cursor-pointer' : ''}
        >
            <Can permissions={[Permission.ReviewsManage, Permission.ReviewsDelete]}>
                <TableCell onClick={handleSelectReview} className="w-10">
                    <Checkbox checked={isSelected} aria-label={__('Select review :id', { id: review.id })} />
                </TableCell>
            </Can>

            <TableCell>
                <StarRating rating={review.rating} readOnly />
            </TableCell>

            <TableCell className="max-w-md">
                <div className="flex flex-col gap-0.5">
                    <span className="line-clamp-1 font-medium">{review.title}</span>
                    <span className="text-sm wrap-break-word whitespace-normal text-muted-foreground">
                        {review.content}
                    </span>
                </div>
            </TableCell>

            <TableCell>
                <div className="flex items-center gap-3">
                    <Thumbnail
                        src={mediaSmallThumb(review.product?.featured_media)}
                        alt={mediaAlt(review.product?.featured_media, getTranslation(review.product?.title))}
                    />
                    <span className="line-clamp-1">{getTranslation(review.product?.title)}</span>
                </div>
            </TableCell>

            <TableCell>
                <div className="flex flex-col gap-0.5">
                    <span className="line-clamp-1">{review.user?.name}</span>
                    <span className="line-clamp-1 text-muted-foreground">{review.user?.email}</span>
                </div>
            </TableCell>

            <TableCell>
                <StatusBadge status={review.status}>{getStatusLabel(review.status)}</StatusBadge>
            </TableCell>

            <TableCell>
                <div className="flex flex-col gap-0.5">
                    <span>{formatDate(review.created_at)}</span>
                    <span className="text-muted-foreground">{formatTime(review.created_at)}</span>
                </div>
            </TableCell>
        </TableRow>
    );
});
ReviewRow.displayName = 'ReviewRow';
