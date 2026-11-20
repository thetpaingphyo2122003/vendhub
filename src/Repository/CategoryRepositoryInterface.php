<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Category;

interface CategoryRepositoryInterface
{
    /** @return list<Category> */
    public function all(): array;

    /** @return list<string> */
    public function names(): array;

    public function findById(int $id): ?Category;

    public function findByName(string $name): ?Category;

    public function create(string $name): Category;

    public function update(Category $category, string $previousName): void;

    public function delete(int $id): void;

    public function countProductsUsing(string $name): int;

    /** How many products in this category already appear in purchase history. */
    public function countProductsWithPurchases(string $name): int;

    /**
     * Deletes the category and its products that have no purchase history.
     * Refuses when any product in the category has transactions (history preserved).
     *
     * @return list<string> Deleted product slugs (for image cleanup)
     */
    public function deleteWithProducts(int $id, string $name): array;
}
