<?php

namespace FINDOLOGIC\Shopware6Common\Tests\Traits;

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

        return $category;
    }

    public function generateCategoryPaths(ProductEntity $product): ProductEntity
    {
        $categories = $product->categories;

        /** @var CategoryEntity $category */
        $categories->map(function (CategoryEntity $category) {
            $category->path = sprintf('|%s|', $this->generateCategoryPath($category));
            $category->breadcrumb = explode('|', $this->generateBreadcrumbs($category));
        });

        $product->categories = $categories;

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
