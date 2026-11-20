<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Core\Database\Connection;
use App\Exception\InsufficientStockException;
use App\Model\Product;
use App\Model\Transaction;
use App\Model\User;
use App\Repository\ProductRepositoryInterface;
use App\Repository\TransactionRepositoryInterface;
use App\Service\PurchaseService;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Documents stock concurrency control: FOR UPDATE + transactional check.
 * Second buyer must fail when remaining stock is insufficient.
 */
final class PurchaseConcurrencyTest extends TestCase
{
    public function testSecondBuyerFailsWhenStockIsConsumedInsideTransaction(): void
    {
        $stock = 1;
        $products = $this->createMock(ProductRepositoryInterface::class);
        $products->method('findBySlugForUpdate')->willReturnCallback(
            static function () use (&$stock): Product {
                return new Product(1, 'Coke', 'coke', '3.990', $stock, 'Drink', 5);
            },
        );
        $products->expects($this->once())->method('adjustQuantity')->with(1, -1)->willReturnCallback(
            static function () use (&$stock): void {
                $stock -= 1;
            },
        );

        $transactions = $this->createMock(TransactionRepositoryInterface::class);
        $transactions->expects($this->once())->method('create')->willReturnCallback(
            static fn (Transaction $tx): Transaction => new Transaction(
                1,
                $tx->userId,
                $tx->productId,
                $tx->quantity,
                $tx->unitPrice,
                $tx->total,
            ),
        );

        $service = new PurchaseService($this->connection(), $products, $transactions);
        $buyerA = new User(2, 'A', 'buyer_a', 'hash', 'user');
        $buyerB = new User(3, 'B', 'buyer_b', 'hash', 'user');

        $first = $service->purchase($buyerA, 'coke', 1);
        $this->assertSame(1, $first->quantity);
        $this->assertSame(0, $stock);

        $this->expectException(InsufficientStockException::class);
        $service->purchase($buyerB, 'coke', 1);
    }

    public function testPurchaseUsesRowLockQuery(): void
    {
        $products = $this->createMock(ProductRepositoryInterface::class);
        $products->expects($this->once())->method('findBySlugForUpdate')->with('pepsi')
            ->willReturn(new Product(2, 'Pepsi', 'pepsi', '6.885', 2));
        $products->expects($this->once())->method('adjustQuantity');
        $transactions = $this->createMock(TransactionRepositoryInterface::class);
        $transactions->method('create')->willReturnCallback(
            static fn (Transaction $tx): Transaction => new Transaction(2, 2, 2, 1, '6.885', '6.885'),
        );

        (new PurchaseService($this->connection(), $products, $transactions))->purchase(
            new User(2, 'Riley', 'user', 'hash', 'user'),
            'pepsi',
            1,
        );
    }

    private function connection(): Connection
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return new Connection($pdo);
    }
}
