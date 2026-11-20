<?php

declare(strict_types=1);

namespace Tests\Controller;

use App\Controller\AuthController;
use App\Core\Http\Request;
use App\Core\Http\Url;
use App\Core\Http\View;
use App\Model\User;
use App\Security\RateLimiter;
use App\Service\AuthServiceInterface;
use App\Validation\RegisterValidator;
use PHPUnit\Framework\TestCase;

final class AuthControllerTest extends TestCase
{
    private View $view;
    private string $rateDir;

    protected function setUp(): void
    {
        new Url('');
        $this->view = new View(dirname(__DIR__, 2) . '/views');
        $this->rateDir = sys_get_temp_dir() . '/vendhub-auth-rate-' . bin2hex(random_bytes(4));
        $_SESSION = ['csrf' => 'test-csrf-token'];
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->rateDir)) {
            return;
        }
        foreach (glob($this->rateDir . '/*.json') ?: [] as $file) {
            unlink($file);
        }
        @rmdir($this->rateDir);
    }

    public function testLoginRejectsBlankCredentials(): void
    {
        $auth = $this->createMock(AuthServiceInterface::class);
        $auth->expects($this->never())->method('attempt');

        $response = $this->controller($auth)->login(new Request(
            'POST',
            '/login',
            [],
            ['username' => '', 'password' => ''],
            [],
            [],
        ));

        $this->assertSame(422, $response->status);
        $this->assertStringContainsString('Username and password are required.', $response->content);
    }

    public function testLoginRejectsBadCredentials(): void
    {
        $auth = $this->createMock(AuthServiceInterface::class);
        $auth->method('attempt')->willReturn(false);

        $response = $this->controller($auth)->login(new Request(
            'POST',
            '/login',
            [],
            ['username' => 'user', 'password' => 'nope'],
            [],
            [],
        ));

        $this->assertSame(422, $response->status);
        $this->assertStringContainsString('incorrect', $response->content);
    }

    public function testUserLoginBlocksAdminAccounts(): void
    {
        $admin = new User(1, 'Avery', 'admin', 'hash', 'admin');
        $auth = $this->createMock(AuthServiceInterface::class);
        $auth->method('attempt')->willReturn(true);
        $auth->method('user')->willReturn($admin);
        $auth->expects($this->once())->method('logout');

        $response = $this->controller($auth)->login(new Request(
            'POST',
            '/login',
            [],
            ['username' => 'admin', 'password' => 'Admin12345!'],
            [],
            [],
        ));

        $this->assertSame(422, $response->status);
        $this->assertStringContainsString('Admin Login', $response->content);
    }

    public function testAdminLoginRejectsNonAdmin(): void
    {
        $user = new User(2, 'Riley', 'user', 'hash', 'user');
        $auth = $this->createMock(AuthServiceInterface::class);
        $auth->method('attempt')->willReturn(true);
        $auth->method('user')->willReturn($user);
        $auth->expects($this->once())->method('logout');

        $response = $this->controller($auth)->adminLogin(new Request(
            'POST',
            '/admin/login',
            [],
            ['username' => 'user', 'password' => 'User12345!'],
            [],
            [],
        ));

        $this->assertSame(422, $response->status);
        $this->assertStringContainsString('admin access', $response->content);
    }

    public function testSuccessfulUserLoginRedirectsToProducts(): void
    {
        $user = new User(2, 'Riley', 'user', 'hash', 'user');
        $auth = $this->createMock(AuthServiceInterface::class);
        $auth->method('attempt')->willReturn(true);
        $auth->method('user')->willReturn($user);

        $response = $this->controller($auth)->login(new Request(
            'POST',
            '/login',
            [],
            ['username' => 'user', 'password' => 'User12345!'],
            [],
            [],
        ));

        $this->assertSame(302, $response->status);
        $this->assertSame('/products', $response->headers['Location']);
    }

    private function controller(AuthServiceInterface $auth): AuthController
    {
        return new AuthController(
            $auth,
            new RegisterValidator(),
            new RateLimiter($this->rateDir),
            $this->view,
            new Url(''),
        );
    }
}
