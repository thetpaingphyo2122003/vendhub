<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Core\Config;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Exception\AuthenticationException;
use App\Middleware\RequireJwt;
use App\Model\User;
use App\Repository\UserRepositoryInterface;
use App\Service\JwtService;
use PHPUnit\Framework\TestCase;

final class RequireJwtTest extends TestCase
{
    public function testAMissingTokenIsRejected(): void
    {
        $middleware = new RequireJwt($this->jwt($this->createMock(UserRepositoryInterface::class)));
        $request = new Request('GET', '/api/products', [], [], [], []);

        $this->expectException(AuthenticationException::class);
        $middleware->handle($request, static fn () => Response::html('ok'));
    }

    public function testABadTokenIsRejected(): void
    {
        $middleware = new RequireJwt($this->jwt($this->createMock(UserRepositoryInterface::class)));
        $request = new Request('GET', '/api/products', [], [], ['authorization' => 'Bearer not-a-token'], []);

        $this->expectException(AuthenticationException::class);
        $middleware->handle($request, static fn () => Response::html('ok'));
    }

    public function testAValidTokenReachesTheNextAction(): void
    {
        $user = new User(2, 'Riley', 'riley', 'hash', 'user');
        $users = $this->createMock(UserRepositoryInterface::class);
        $users->method('findById')->with(2)->willReturn($user);
        $jwt = $this->jwt($users);
        $token = $jwt->issue($user);
        $request = new Request('POST', '/api/products/coke/purchase', [], [], ['authorization' => 'Bearer ' . $token], []);

        $response = (new RequireJwt($jwt))->handle(
            $request,
            static fn (Request $request): Response => Response::html($request->user()?->username ?? ''),
        );

        $this->assertSame(200, $response->status);
        $this->assertSame('riley', $response->content);
    }

    private function jwt(UserRepositoryInterface $users): JwtService
    {
        return new JwtService(new Config([
            'app' => ['url' => 'http://localhost:8080'],
            'jwt' => ['secret' => 'test-secret-at-least-32-characters!!', 'ttl' => 3600],
        ]), $users);
    }
}
