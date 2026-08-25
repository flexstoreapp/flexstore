export type ListLoadingMethod = 'infinite_scroll' | 'pagination' | 'load_more';
export type ProductSortOption = 'relevance' | 'latest' | 'price_asc' | 'price_desc' | 'name_asc' | 'name_desc';

export interface ProductListSettings {
    loading_method: ListLoadingMethod;
    default_per_page: number;
    default_sort: ProductSortOption;
}
