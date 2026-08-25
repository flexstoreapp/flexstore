<?php

declare(strict_types=1);

namespace App\Filters\Criteria;

use App\Enums\ProductSortOption;
use App\Filters\Contracts\Criterion;
use App\Filters\Strategies\PriceColumnSortStrategy;
use App\Models\Product;
use App\Utilities\LikePattern;
use App\Utilities\SubstringPosition;
use App\Utilities\TranslatableJsonExtract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * @template TModel of Product
 *
 * @implements Criterion<TModel>
 */
final readonly class StorefrontSortCriterion implements Criterion
{
    public function __construct(private ?string $search = null)
    {
    }

    public function canApply(mixed $value): bool
    {
        return true;
    }

    /**
     * @param  Builder<TModel>  $builder
     * @return Builder<TModel>
     */
    public function apply(Builder $builder, mixed $value): Builder
    {
        $sortOption = ProductSortOption::tryFrom((string) $value) ?? ProductSortOption::Latest;

        $locale = app()->getLocale();

        $builder = match ($sortOption) {
            ProductSortOption::Relevance => $this->applyRelevanceSort($builder),
            ProductSortOption::Latest => $builder->latest(),
            ProductSortOption::PriceLowHigh => $this->applyPriceSort($builder, 'asc'),
            ProductSortOption::PriceHighLow => $this->applyPriceSort($builder, 'desc'),
            ProductSortOption::NameAZ => $builder->orderByRaw(...$this->getNameSortExpression('asc', $locale)),
            ProductSortOption::NameZA => $builder->orderByRaw(...$this->getNameSortExpression('desc', $locale)),
        };

        return $builder->orderBy('id');
    }

    /**
     * @param  Builder<TModel>  $builder
     * @return Builder<TModel>
     */
    private function applyRelevanceSort(Builder $builder): Builder
    {
        $phrases = $this->rankPhrases();

        if ($phrases === []) {
            return $builder->latest();
        }

        [$titleSql, $titleBindings] = TranslatableJsonExtract::expressionWithFallback('title');
        [$descriptionSql, $descriptionBindings] = TranslatableJsonExtract::expressionWithFallback('description');

        $title = "lower({$titleSql})";
        $description = "lower({$descriptionSql})";
        $escape = LikePattern::ESCAPE_CLAUSE;

        $exact = [];
        $prefix = [];
        $wordStart = [];
        $contains = [];
        $inDescription = [];
        $rankBindings = [];

        foreach ($phrases as $phrase) {
            $pattern = LikePattern::escape($phrase);

            $exact[] = "{$title} = ?";
            $rankBindings = [...$rankBindings, ...$titleBindings, $phrase];

            $prefix[] = "{$title} like ? {$escape}";
            $rankBindings = [...$rankBindings, ...$titleBindings, "{$pattern}%"];

            $wordStart[] = "{$title} like ? {$escape}";
            $rankBindings = [...$rankBindings, ...$titleBindings, "% {$pattern}%"];

            $contains[] = "{$title} like ? {$escape}";
            $rankBindings = [...$rankBindings, ...$titleBindings, "%{$pattern}%"];

            $inDescription[] = "{$description} like ? {$escape}";
            $rankBindings = [...$rankBindings, ...$descriptionBindings, "%{$pattern}%"];
        }

        $builder->orderByRaw(
            'case when ' . implode(' or ', $exact) . ' then 0 when ' . implode(' or ', $prefix) . ' then 1 when ' . implode(' or ', $wordStart) . ' then 2 when ' . implode(' or ', $contains) . ' then 3 when ' . implode(' or ', $inDescription) . ' then 4 else 5 end',
            $rankBindings,
        );

        $titlePosition = SubstringPosition::expression($title);
        $descriptionPosition = SubstringPosition::expression($description);
        $missingPosition = '999999';
        $titlePositions = [];
        $descriptionPositions = [];
        $titlePositionBindings = [];
        $descriptionPositionBindings = [];

        foreach ($phrases as $phrase) {
            $titlePositions[] = "case when {$titlePosition} = 0 then {$missingPosition} else {$titlePosition} end";
            $titlePositionBindings = [...$titlePositionBindings, ...$titleBindings, $phrase, ...$titleBindings, $phrase];

            $descriptionPositions[] = "case when {$descriptionPosition} = 0 then {$missingPosition} else {$descriptionPosition} end";
            $descriptionPositionBindings = [...$descriptionPositionBindings, ...$descriptionBindings, $phrase, ...$descriptionBindings, $phrase];
        }

        $least = count($phrases) === 1
            ? '%s'
            : (DB::getDriverName() === 'sqlite' ? 'min(%s)' : 'least(%s)');

        return $builder
            ->orderByRaw(sprintf($least, implode(', ', $titlePositions)) . ' asc', $titlePositionBindings) // @phpstan-ignore argument.type
            ->orderByRaw(sprintf($least, implode(', ', $descriptionPositions)) . ' asc', $descriptionPositionBindings) // @phpstan-ignore argument.type
            ->latest();
    }

    /**
     * @return list<string>
     */
    private function rankPhrases(): array
    {
        $term = mb_strtolower(mb_trim((string) $this->search));

        if ($term === '') {
            return [];
        }

        return [$term];
    }

    /**
     * @param  Builder<TModel>  $builder
     * @param  'asc'|'desc'  $direction
     * @return Builder<TModel>
     */
    private function applyPriceSort(Builder $builder, string $direction): Builder
    {
        /** @var PriceColumnSortStrategy<TModel> $strategy */
        $strategy = new PriceColumnSortStrategy();

        return $strategy->apply($builder, $direction);
    }

    /**
     * @return array{literal-string, list<string>}
     */
    private function getNameSortExpression(string $direction, string $locale): array
    {
        return TranslatableJsonExtract::orderByExpression('title', $direction, $locale);
    }
}
