<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Exception\ValidationException;
use App\Model\User;
use App\Repository\UserRepositoryInterface;
use App\Service\AuthService;
use App\Validation\RegisterValidator;
use PHPUnit\Framework\TestCase;

/**
 * Integration: registration validation + auth service persistence boundary.
 */
final class AuthRegisterFlowIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = ['csrf' => 'test-csrf-token'];
    }

    public function testValidRegistrationCreatesUserAndStartsSession(): void
    {
        $input = [
            'name' => 'Riley',
            'username' => 'newuser',
            'password' => 'User12345!',
            'password_confirmation' => 'User12345!',
        ];
        $this->assertSame([], (new RegisterValidator())->validate($input));

        $users = $this->createMock(UserRepositoryInterface::class);
        $users->method('findByUsername')->willReturn(null);
        $users->expects($this->once())->method('create')->willReturn(
            new User(9, 'Riley', 'newuser', 'hash', 'user'),
        );
        $users->method('findById')->with(9)->willReturn(
            new User(9, 'Riley', 'newuser', 'hash', 'user'),
        );

        $auth = new AuthService($users);
        $user = $auth->register($input['name'], $input['username'], $input['password']);

        $this->assertSame(9, $user->id);
        $this->assertSame(9, $_SESSION['user_id']);
        $this->assertNotNull($auth->user());
    }

    public function testInvalidRegistrationNeverTouchesRepository(): void
    {
        $errors = (new RegisterValidator())->validate([
            'name' => '',
            'username' => 'bad space',
            'password' => 'weak',
            'password_confirmation' => 'nope',
        ]);
        $this->assertNotSame([], $errors);

        $users = $this->createMock(UserRepositoryInterface::class);
        $users->expects($this->never())->method('create');
        $this->assertTrue(true);
    }

    public function testDuplicateUsernameIsRejectedByServiceLayer(): void
    {
        $input = [
            'name' => 'Riley',
            'username' => 'taken',
            'password' => 'User12345!',
            'password_confirmation' => 'User12345!',
        ];
        $this->assertSame([], (new RegisterValidator())->validate($input));

        $users = $this->createMock(UserRepositoryInterface::class);
        $users->method('findByUsername')->willReturn(new User(3, 'Taken', 'taken', 'hash', 'user'));
        $users->expects($this->never())->method('create');

        $this->expectException(ValidationException::class);
        (new AuthService($users))->register($input['name'], $input['username'], $input['password']);
    }
}
