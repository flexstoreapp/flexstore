<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\StoreReviewAction;
use App\Actions\UpdateReviewAction;
use App\Http\Requests\Admin\IndexReviewRequest;
use App\Http\Requests\Admin\StoreReviewRequest;
use App\Http\Requests\Admin\UpdateReviewRequest;
use App\Models\Review;
use App\Queries\ReviewListQuery;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ReviewController
{
    public function index(IndexReviewRequest $request, ReviewListQuery $query): Response
    {
        return Inertia::render('admin/reviews/list', [
            'reviews' => $query->execute($request->query(), $request->safe()->integer('per_page', 15)),
            'filters' => Inertia::always([
                'query' => $request->validated('query'),
                'product' => $request->validated('product'),
                'status' => $request->validated('status'),
                'rating' => $request->validated('rating'),
                'sort' => $request->validated('sort', 'created_at'),
                'direction' => $request->validated('direction', 'desc'),
            ]),
        ]);
    }

    public function store(StoreReviewRequest $request, StoreReviewAction $action): RedirectResponse
    {
        $action->handle($request->toDto());

        return back();
    }

    public function update(UpdateReviewRequest $request, Review $review, UpdateReviewAction $action): RedirectResponse
    {
        $action->handle($review, $request->toDto());

        return back();
    }
}
