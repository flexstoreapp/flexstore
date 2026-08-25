<?php

declare(strict_types=1);

namespace App\Enums;

enum ListLoadingMethod: string
{
    case InfiniteScroll = 'infinite_scroll';
    case Pagination = 'pagination';
    case LoadMore = 'load_more';
}
