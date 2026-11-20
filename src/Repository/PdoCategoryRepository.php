<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database\Connection;
use App\Core\Support\Slug;
use App\Model\Category;

final class PdoCategoryRepository implements CategoryRepositoryInterface
{
    public function __construct(private Connection $connection)
    {
    }

    public function all(): array
    {
        $rows = $this->connection->select(
            'SELECT id, name, slug FROM categories ORDER BY name ASC',
        );

        return array_map($this->map(...), $rows);
    }

    public function names(): array
    {
        return array_map(static fn (Category $category): string => $category->name, $this->all());
    }

    public function findById(int $id): ?Category
    {
        $row = $this->connection->selectOne(
            'SELECT id, name, slug FROM categories WHERE id = ?',
            [$id],
        );

        return $row === null ? null : $this->map($row);
    }

    public function findByName(string $name): ?Category
    {
        $row = $this->connection->selectOne(
            'SELECT id, name, slug FROM categories WHERE name = ?',
            [$name],
        );

        return $row === null ? null : $this->map($row);
    }

    public function create(string $name): Category
    {
        $name = trim($name);
        $slug = $this->uniqueSlug(Slug::from($name));
        $this->connection->execute(
            'INSERT INTO categories (name, slug) VALUES (?, ?)',
            [$name, $slug],
        );

        return new Category($this->connection->lastInsertId(), $name, $slug);
    }

    public function update(Category $category, string $previousName): void
    {
        $category->name = trim($category->name);
        $category->slug = $this->uniqueSlug(Slug::from($category->name), (int) $category->id);
        $this->connection->execute(
            'UPDATE categories SET name = ?, slug = ? WHERE id = ?',
            [$category->name, $category->slug, $category->id],
        );

        if ($previousName !== $category->name) {
            $this->connection->execute(
                'UPDATE products SET category = ? WHERE category = ?',
                [$category->name, $previousName],
            );
        }
    }

    public function delete(int $id): void
    {
        $this->connection->execute('DELETE FROM categories WHERE id = ?', [$id]);
    }

    public function countProductsUsing(string $name): int
    {
        $row = $this->connection->selectOne(
            'SELECT COUNT(*) AS total FROM products WHERE category = ?',
            [$name],
        );

        return (int) ($row['total'] ?? 0);
    }

    public function countProductsWithPurchases(string $name): int
    {
        $row = $this->connection->selectOne(
            'SELECT COUNT(DISTINCT p.id) AS total
             FROM products p
             INNER JOIN transactions t ON t.product_id = p.id
             WHERE p.category = ?',
            [$name],
        );

        return (int) ($row['total'] ?? 0);
    }

    public function deleteWithProducts(int $id, string $name): array
    {
        return $this->connection->transaction(function () use ($id, $name): array {
            if ($this->countProductsWithPurchases($name) > 0) {
                throw new \RuntimeException(
                    'This category has products with purchase history. Move or keep those products; sales history is not deleted.',
                );
            }

            $rows = $this->connection->select(
                'SELECT id, slug FROM products WHERE category = ?',
                [$name],
            );
            $slugs = [];
            foreach ($rows as $row) {
                $slugs[] = (string) $row['slug'];
            }

            if ($slugs !== []) {
                $this->connection->execute(
                    'DELETE FROM products WHERE category = ?',
                    [$name],
                );
            }

            $this->connection->execute('DELETE FROM categories WHERE id = ?', [$id]);

            return $slugs;
        });
    }

    private function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = $base === '' || $base === 'product' ? 'category' : $base;
        $candidate = $slug;
        $suffix = 2;

        while ($this->slugExists($candidate, $ignoreId)) {
            $candidate = $slug . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function slugExists(string $slug, ?int $ignoreId): bool
    {
        if ($ignoreId === null) {
            $row = $this->connection->selectOne('SELECT id FROM categories WHERE slug = ?', [$slug]);
        } else {
            $row = $this->connection->selectOne(
                'SELECT id FROM categories WHERE slug = ? AND id <> ?',
                [$slug, $ignoreId],
            );
        }

        return $row !== null;
    }

    /** @param array<string, mixed> $row */
    private function map(array $row): Category
    {
        return new Category(
            (int) $row['id'],
            (string) $row['name'],
            (string) $row['slug'],
        );
    }
}
