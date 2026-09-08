<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\BulkDestroyReviewAction;
use App\Actions\StoreReviewAction;
use App\Actions\UpdateReviewAction;
use App\Http\Requests\Admin\IndexReviewRequest;
use App\Http\Requests\Admin\StoreReviewRequest;
use App\Http\Requests\Admin\UpdateReviewRequest;
use App\Http\Resources\Api\V1\Admin\ReviewResource;
use App\Models\Media;
use App\Models\Review;
use App\Queries\ReviewListQuery;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

final readonly class ReviewController
{
    public function index(IndexReviewRequest $request, ReviewListQuery $query): AnonymousResourceCollection
    {
        return ReviewResource::collection(
            $query->execute($request->validated(), $request->safe()->integer('per_page', 15)),
        );
    }

    public function store(StoreReviewRequest $request, StoreReviewAction $action): JsonResponse
    {
        $review = $action->handle($request->toDto());

        return (new ReviewResource($this->presentable($review)))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Review $review): ReviewResource
    {
        return new ReviewResource($this->presentable($review));
    }

    public function update(UpdateReviewRequest $request, Review $review, UpdateReviewAction $action): ReviewResource
    {
        $action->handle($review, $request->toDto());

        return new ReviewResource($this->presentable($review->refresh()));
    }

    public function destroy(Review $review, BulkDestroyReviewAction $action): Response
    {
        $action->handle([$review->id]);

        return response()->noContent();
    }

    private function presentable(Review $review): Review
    {
        $review->load([
            'product:id,title',
            'product.mediaGallery' => fn (Relation $query): Relation => $query->select(Media::displayColumns())->limit(1),
            'user:id,name,email',
        ]);

        $review->product->append('featured_media');
        $review->product->makeHidden(['mediaGallery']);

        return $review;
    }
}
