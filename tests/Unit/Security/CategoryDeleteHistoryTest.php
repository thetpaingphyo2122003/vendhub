<?php

declare(strict_types=1);

namespace Tests\Unit\Security;

use App\Core\Database\Connection;
use App\Repository\PdoCategoryRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class CategoryDeleteHistoryTest extends TestCase
{
    public function testDeleteIsBlockedWhenProductsHavePurchaseHistory(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE categories (id INTEGER PRIMARY KEY, name TEXT, slug TEXT)');
        $pdo->exec('CREATE TABLE products (id INTEGER PRIMARY KEY, name TEXT, slug TEXT, category TEXT)');
        $pdo->exec('CREATE TABLE transactions (id INTEGER PRIMARY KEY, product_id INTEGER)');
        $pdo->exec("INSERT INTO categories (id, name, slug) VALUES (1, 'Drink', 'drink')");
        $pdo->exec("INSERT INTO products (id, name, slug, category) VALUES (1, 'Coke', 'coke', 'Drink')");
        $pdo->exec('INSERT INTO transactions (id, product_id) VALUES (1, 1)');

        $repo = new PdoCategoryRepository(new Connection($pdo));

        $this->assertSame(1, $repo->countProductsWithPurchases('Drink'));
        $this->expectException(\RuntimeException::class);
        $repo->deleteWithProducts(1, 'Drink');
    }

    public function testDeleteRemovesUnusedProductsWithoutTouchingTransactionsTable(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE categories (id INTEGER PRIMARY KEY, name TEXT, slug TEXT)');
        $pdo->exec('CREATE TABLE products (id INTEGER PRIMARY KEY, name TEXT, slug TEXT, category TEXT)');
        $pdo->exec('CREATE TABLE transactions (id INTEGER PRIMARY KEY, product_id INTEGER)');
        $pdo->exec("INSERT INTO categories (id, name, slug) VALUES (1, 'Snack', 'snack')");
        $pdo->exec("INSERT INTO products (id, name, slug, category) VALUES (1, 'Lays', 'lays', 'Snack')");

        $repo = new PdoCategoryRepository(new Connection($pdo));
        $slugs = $repo->deleteWithProducts(1, 'Snack');

        $this->assertSame(['lays'], $slugs);
        $this->assertSame(0, (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn());
        $this->assertSame(0, (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn());
        $this->assertSame(0, (int) $pdo->query('SELECT COUNT(*) FROM transactions')->fetchColumn());
    }
}
