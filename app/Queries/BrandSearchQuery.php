<?php

declare(strict_types=1);

namespace App\Queries;

use App\Filters\Criteria\TextSearchCriterion;
use App\Filters\CriteriaCollection;
use App\Filters\FilterManager;
use App\Models\Brand;
use App\Models\Media;
use App\Utilities\TranslatableJsonExtract;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;

final readonly class BrandSearchQuery
{
    /**
     * @param  FilterManager<Brand>  $filterManager
     */
    public function __construct(private FilterManager $filterManager)
    {
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Paginator<int, Brand>
     */
    public function execute(array $filters = []): Paginator
    {
        /** @var CriteriaCollection<Brand> $criteria */
        $criteria = new CriteriaCollection();

        /** @var TextSearchCriterion<Brand> $textSearch */
        $textSearch = new TextSearchCriterion(['name', 'url_handle']);
        $criteria->add('query', $textSearch);

        $this->filterManager->setCriteria($criteria);

        return Brand::query()
            ->with(['image:' . Media::displaySelect()])
            ->select(['id', 'name', 'url_handle', 'image_id'])
            ->where('is_active', true)
            ->tap(fn (Builder $query): Builder => $this->filterManager->apply($query, $filters))
            ->orderByRaw(...TranslatableJsonExtract::orderByExpression('name'))
            ->simplePaginate();
    }
}
