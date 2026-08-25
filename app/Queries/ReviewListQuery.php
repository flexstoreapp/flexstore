<?php

declare(strict_types=1);

namespace App\Queries;

use App\Filters\Criteria\ExactMatchCriterion;
use App\Filters\Criteria\RelationshipCriterion;
use App\Filters\Criteria\SortCriterion;
use App\Filters\Criteria\TextSearchCriterion;
use App\Filters\CriteriaCollection;
use App\Filters\FilterManager;
use App\Models\Media;
use App\Models\Review;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class ReviewListQuery
{
    /**
     * @param  FilterManager<Review>  $filterManager
     */
    public function __construct(private FilterManager $filterManager)
    {
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Review>
     */
    public function execute(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        /** @var CriteriaCollection<Review> $criteria */
        $criteria = new CriteriaCollection();

        /** @var TextSearchCriterion<Review> $textSearch */
        $textSearch = new TextSearchCriterion(['title', 'content']);
        $criteria->add('query', $textSearch);

        /** @var RelationshipCriterion<Review> $productRelation */
        $productRelation = new RelationshipCriterion('product', 'id');
        $criteria->add('product', $productRelation);

        /** @var ExactMatchCriterion<Review> $statusMatch */
        $statusMatch = new ExactMatchCriterion('status');
        $criteria->add('status', $statusMatch);

        /** @var ExactMatchCriterion<Review> $rating */
        $rating = new ExactMatchCriterion('rating');
        $criteria->add('rating', $rating);

        /** @var SortCriterion<Review> $sortCriterion */
        $sortCriterion = new SortCriterion(['rating', 'created_at'], direction: $filters['direction'] ?? null);
        $criteria->add('sort', $sortCriterion);

        $this->filterManager->setCriteria($criteria);

        return Review::query()
            ->with([
                'product:id,title',
                'product.mediaGallery' => fn (Relation $q): Relation => $q->select(Media::displayColumns())->limit(1),
                'user:id,name,email',
            ])
            ->tap(fn (Builder $query): Builder => $this->filterManager->apply($query, $filters))
            ->paginate($perPage)
            ->through(function (Review $review): Review {
                $review->product->append('featured_media');
                $review->product->makeHidden(['mediaGallery']);

                return $review;
            })
            ->withQueryString();
    }
}
