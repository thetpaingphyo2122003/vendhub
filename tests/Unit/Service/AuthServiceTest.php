<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Exception\ValidationException;
use App\Model\User;
use App\Repository\UserRepositoryInterface;
use App\Service\AuthService;
use PHPUnit\Framework\TestCase;

final class AuthServiceTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = ['csrf' => 'test-csrf-token'];
    }

    public function testVerifyAcceptsCorrectPasswordAndRejectsWrongOne(): void
    {
        $hash = password_hash('User12345!', PASSWORD_DEFAULT);
        $user = new User(2, 'Riley', 'riley', $hash, 'user');
        $users = $this->createMock(UserRepositoryInterface::class);
        $users->method('findByUsername')->willReturnCallback(
            static fn (string $username): ?User => $username === 'riley' ? $user : null,
        );

        $auth = new AuthService($users);

        $this->assertNotNull($auth->verify('riley', 'User12345!'));
        $this->assertNull($auth->verify('riley', 'wrong-password'));
        $this->assertNull($auth->verify('missing', 'User12345!'));
    }

    public function testAttemptLogsInAndLogoutClearsSession(): void
    {
        $hash = password_hash('Admin12345!', PASSWORD_DEFAULT);
        $user = new User(1, 'Avery', 'admin', $hash, 'admin');
        $users = $this->createMock(UserRepositoryInterface::class);
        $users->method('findByUsername')->willReturn($user);
        $users->method('findById')->with(1)->willReturn($user);

        $auth = new AuthService($users);
        $this->assertTrue($auth->attempt('admin', 'Admin12345!'));
        $this->assertSame(1, $_SESSION['user_id']);
        $this->assertNotNull($auth->user());

        $auth->logout();
        $this->assertArrayNotHasKey('user_id', $_SESSION);
        $this->assertNull($auth->user());
    }

    public function testRegisterRejectsDuplicateUsername(): void
    {
        $existing = new User(3, 'Taken', 'taken', 'hash', 'user');
        $users = $this->createMock(UserRepositoryInterface::class);
        $users->method('findByUsername')->with('taken')->willReturn($existing);

        $this->expectException(ValidationException::class);
        (new AuthService($users))->register('New', 'taken', 'User12345!');
    }
}
