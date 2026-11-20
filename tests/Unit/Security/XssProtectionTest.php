<?php

declare(strict_types=1);

namespace Tests\Unit\Security;

use App\Validation\CategoryValidator;
use App\Validation\ProductValidator;
use App\Validation\RegisterValidator;
use App\Repository\CategoryRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class XssProtectionTest extends TestCase
{
    public function testHelpersEscapeAndDetectHtml(): void
    {
        $this->assertSame('&lt;script&gt;', e('<script>'));
        $this->assertTrue(contains_html('<img src=x onerror=alert(1)>'));
        $this->assertFalse(contains_html('Coke'));
        $this->assertStringContainsString('\u003C', json_for_script(['x' => '</script><script>alert(1)']));
    }

    public function testValidatorsRejectHtmlNames(): void
    {
        $categories = $this->createMock(CategoryRepositoryInterface::class);
        $categories->method('names')->willReturn(['Drink']);

        $product = (new ProductValidator($categories))->validate([
            'name' => '<script>x</script>',
            'price' => '1.000',
            'quantity_available' => '1',
            'reorder_point' => '1',
            'category' => 'Drink',
        ]);
        $this->assertArrayHasKey('name', $product);

        $register = (new RegisterValidator())->validate([
            'name' => '<b>Admin</b>',
            'username' => 'admin',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ]);
        $this->assertArrayHasKey('name', $register);

        $category = (new CategoryValidator())->validate(['name' => '<svg/onload=1>']);
        $this->assertArrayHasKey('name', $category);
    }
}
