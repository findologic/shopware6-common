<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Tests\Traits;

use Vin\ShopwareSdk\Data\Entity\Category\CategoryCollection;
use Vin\ShopwareSdk\Data\Entity\Category\CategoryEntity;
use Vin\ShopwareSdk\Data\Entity\Entity;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;
use Vin\ShopwareSdk\Data\Uuid\Uuid;

trait CategoryHelper
{
    use Constants;

    public function createTestCategory(?array $overrideData = []): CategoryEntity
    {
        $defaultData = [
            'id' => Uuid::randomHex(),
            'name' => Uuid::randomHex(),
            'active' => true,
        ];
        $categoryData = array_merge($defaultData, $overrideData);

        /** @var CategoryEntity $category */
        $category = Entity::createFromArray(CategoryEntity::class, $categoryData);

        $category->path = sprintf('|%s|', $this->generateCategoryPath($category));
        $category->breadcrumb = explode('|', $this->generateBreadcrumbs($category));

        return $category;
    }

    public function buildNavigationCategory(): CategoryEntity
    {
        return $this->createTestCategory([
            'id' => $this->navigationCategoryId,
            'breadcrumb' => [$this->navigationCategoryId],
        ]);
    }
    public function generateProductCategoriesWithRelations(
        CategoryCollection $categories,
        ?array $productCatIds = null
    ): CategoryCollection {
        $productCategories = new CategoryCollection();

        foreach ($categories as $category) {
            $productCategories = $this->handleCategory(
                $category->parentId === $this->navigationCategoryId ? $this->buildNavigationCategory() : null,
                $category,
                $productCategories,
                $productCatIds,
            );
        }

        return $productCategories;
    }

    public function handleCategory(
        ?CategoryEntity $parentCategory,
        CategoryEntity $category,
        CategoryCollection $productCategories,
        ?array $productCatIds = null
    ): CategoryCollection {
        foreach ($category->children ?? [] as $child) {
            $productCategories = $this->handleCategory($category, $child, $productCategories, $productCatIds);
        }

        if ($parentCategory) {
            $category->parent = $parentCategory;
            $category->parentId = $parentCategory->id;
        }

        if (!$productCatIds) {
            if (!$category->children) {
                $productCategories->add($category);
            }

            return $productCategories;
        }

        if (in_array($category->id, $productCatIds)) {
            $productCategories->add($category);
        }

        return $productCategories;
    }

    public function generateCategoryPathsForProduct(ProductEntity $product): ProductEntity
    {
        /** @var CategoryEntity $category */
        $categories = $product->categories->map(function (CategoryEntity $category) {
            $category->path = sprintf('|%s|', $this->generateCategoryPath($category));
            $category->breadcrumb = explode('|', $this->generateBreadcrumbs($category));

            return $category;
        });
        $product->categories = new CategoryCollection($categories);

        return $product;
    }

    public function generateCategoryPath(CategoryEntity $category): string
    {
        if ($category->parent) {
            return $this->generateCategoryPath($category->parent) . '|' . $category->id;
        } else {
            return $category->id;
        }
    }

    public function generateBreadcrumbs(CategoryEntity $category): string
    {
        if ($category->parent) {
            return $this->generateBreadcrumbs($category->parent) . '|' . $category->name;
        } else {
            return $category->name;
        }
    }
}
