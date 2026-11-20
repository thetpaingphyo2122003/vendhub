<?php

declare(strict_types=1);

namespace Tests\Unit\Validation;

use App\Validation\RegisterValidator;
use PHPUnit\Framework\TestCase;

final class RegisterValidatorTest extends TestCase
{
    public function testStrongPasswordAndValidUsernamePass(): void
    {
        $errors = (new RegisterValidator())->validate([
            'name' => 'Riley',
            'username' => 'riley',
            'password' => 'User12345!',
            'password_confirmation' => 'User12345!',
        ]);

        $this->assertSame([], $errors);
    }

    public function testWeakPasswordAndMismatchFail(): void
    {
        $errors = (new RegisterValidator())->validate([
            'name' => 'Riley',
            'username' => 'bad space',
            'password' => 'weak',
            'password_confirmation' => 'other',
        ]);

        $this->assertArrayHasKey('username', $errors);
        $this->assertArrayHasKey('password', $errors);
        $this->assertArrayHasKey('password_confirmation', $errors);
    }

    public function testHtmlNameIsRejected(): void
    {
        $errors = (new RegisterValidator())->validate([
            'name' => '<script>x</script>',
            'username' => 'riley',
            'password' => 'User12345!',
            'password_confirmation' => 'User12345!',
        ]);

        $this->assertArrayHasKey('name', $errors);
    }
}
