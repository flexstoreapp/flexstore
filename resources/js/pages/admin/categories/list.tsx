import { Head, router } from '@inertiajs/react';
import { TagsIcon } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

import * as CategoryController from '@/actions/App/Http/Controllers/Admin/CategoryController';
import * as CategoryReorderController from '@/actions/App/Http/Controllers/Admin/CategoryReorderController';
import { CategoryDialog } from '@/components/admin/category/category-dialog';
import { CategoryTree } from '@/components/admin/category/category-tree';
import { useConfirm } from '@/components/admin/confirm';
import { Heading } from '@/components/admin/heading';
import { Button } from '@/components/ui/button';
import { Can } from '@/components/ui/can';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { SearchInput } from '@/components/ui/search-input';
import { __ } from '@/lib/i18n';
import { Permission } from '@/lib/permissions';
import { getTranslation } from '@/lib/utils';
import type { Category } from '@/types';

export default function CategoryList({ categories: initialCategories }: { categories: Category[] }) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [selectedCategory, setSelectedCategory] = useState<Category | undefined>(undefined);
    const [parentCategory, setParentCategory] = useState<Category | undefined>(undefined);
    const [searchQuery, setSearchQuery] = useState('');
    const [categories, setCategories] = useState<Category[]>(initialCategories);
    const [prevInitial, setPrevInitial] = useState(initialCategories);
    const [isDeleting, setIsDeleting] = useState(false);
    const { confirm } = useConfirm();

    if (initialCategories !== prevInitial) {
        setPrevInitial(initialCategories);
        setCategories(initialCategories);
    }

    const filterCategories = (categories: Category[], query: string): Category[] => {
        if (!query.trim()) {
            return categories;
        }

        const lowerQuery = query.toLowerCase();

        return categories.reduce<Category[]>((filtered, category) => {
            const nameMatch = getTranslation(category.name).toLowerCase().includes(lowerQuery);
            const urlHandleMatch = category.url_handle.toLowerCase().includes(lowerQuery);

            const filteredChildren = category.children ? filterCategories(category.children, query) : [];

            if (nameMatch || urlHandleMatch || filteredChildren.length > 0) {
                filtered.push({
                    ...category,
                    children: filteredChildren.length > 0 ? filteredChildren : category.children,
                });
            }

            return filtered;
        }, []);
    };

    const filteredCategories = filterCategories(categories, searchQuery);

    const handleSearchQueryChange = (value: string) => {
        setSearchQuery(value);
    };

    const addCategory = () => {
        setSelectedCategory(undefined);
        setParentCategory(undefined);
        setDialogOpen(true);
    };

    const editCategory = (category: Category) => {
        setSelectedCategory(category);
        setParentCategory(undefined);
        setDialogOpen(true);
    };

    const addChildCategory = (parent: Category) => {
        setSelectedCategory(undefined);
        setParentCategory(parent);
        setDialogOpen(true);
    };

    const reorderCategory = (categoryId: number, newParentId: number | null, newPosition: number) => {
        const previousCategories = categories;

        const updateCategoryTree = (
            categories: Category[],
            categoryId: number,
            newParentId: number | null,
            newPosition: number,
        ): Category[] => {
            let movedCategory: Category | null = null;

            const removeCategory = (categories: Category[]): Category[] => {
                return categories
                    .map((cat) => {
                        if (cat.id === categoryId) {
                            movedCategory = { ...cat, parent_id: newParentId };
                            return null;
                        }
                        if (cat.children && cat.children.length > 0) {
                            return { ...cat, children: removeCategory(cat.children) };
                        }
                        return cat;
                    })
                    .filter((cat): cat is Category => cat !== null);
            };

            const insertCategory = (categories: Category[], parentId: number | null): Category[] => {
                if (parentId === null) {
                    if (!movedCategory) return categories;
                    const newCategories = [...categories];
                    newCategories.splice(newPosition, 0, movedCategory);
                    return newCategories;
                }

                return categories.map((cat) => {
                    if (cat.id === parentId) {
                        const children = cat.children || [];
                        const newChildren = [...children];
                        if (movedCategory) {
                            newChildren.splice(newPosition, 0, movedCategory);
                        }
                        return { ...cat, children: newChildren };
                    }
                    if (cat.children && cat.children.length > 0) {
                        return { ...cat, children: insertCategory(cat.children, parentId) };
                    }
                    return cat;
                });
            };

            const withoutMoved = removeCategory(categories);
            return insertCategory(withoutMoved, newParentId);
        };

        setCategories((prev) => updateCategoryTree(prev, categoryId, newParentId, newPosition));

        router.patch(
            CategoryReorderController.update(categoryId),
            {
                parent_id: newParentId,
                position: newPosition,
            },
            {
                preserveScroll: true,
                only: ['categories'],
                onError: (errors) => {
                    console.error('Failed to reorder category:', errors);
                    toast.error(__('Something went wrong.'));
                    setCategories(previousCategories);
                },
            },
        );
    };

    const deleteCategory = (category: Category) => {
        confirm({
            variant: 'delete',
            title: __('Are you absolutely sure?'),
            description: __('This will permanently delete this category and all associated subcategories.'),
            action: () =>
                new Promise<void>((resolve) => {
                    setIsDeleting(true);
                    router.delete(CategoryController.destroy(category), {
                        preserveScroll: true,
                        only: ['categories'],
                        onFinish: () => {
                            setIsDeleting(false);
                            resolve();
                        },
                    });
                }),
        });
    };

    return (
        <>
            <Head title={__('Categories')} />

            <div className="mx-auto max-w-3xl space-y-6">
                <Heading title={__('Categories')} description={__('Manage categories and hierarchy')}>
                    {categories.length > 0 && (
                        <Can permission={Permission.CategoriesManage}>
                            <Button onClick={addCategory}>{__('Add category')}</Button>
                        </Can>
                    )}
                </Heading>

                {categories.length > 0 ? (
                    <>
                        <SearchInput value={searchQuery} onChange={handleSearchQueryChange} className="w-xs" />
                        <CategoryTree
                            categories={filteredCategories}
                            shouldAnimate={!searchQuery.trim() && !isDeleting}
                            onEdit={editCategory}
                            onAddChild={addChildCategory}
                            onReorder={reorderCategory}
                            onDelete={deleteCategory}
                        />
                    </>
                ) : (
                    <Empty>
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <TagsIcon />
                            </EmptyMedia>
                            <EmptyTitle>{__('No categories')}</EmptyTitle>
                            <EmptyDescription>{__('Start by adding your first category.')}</EmptyDescription>
                        </EmptyHeader>
                        <EmptyContent>
                            <Button onClick={addCategory}>{__('Add category')}</Button>
                        </EmptyContent>
                    </Empty>
                )}

                {searchQuery && filteredCategories.length === 0 && (
                    <p className="text-sm text-muted-foreground">{__('No items found.')}</p>
                )}

                <Can permission={Permission.CategoriesManage}>
                    <CategoryDialog
                        open={dialogOpen}
                        onOpenChange={setDialogOpen}
                        category={selectedCategory}
                        parentCategory={parentCategory}
                    />
                </Can>
            </div>
        </>
    );
}

CategoryList.layout = {
    breadcrumbs: [{ title: __('Categories'), href: CategoryController.index() }],
};
