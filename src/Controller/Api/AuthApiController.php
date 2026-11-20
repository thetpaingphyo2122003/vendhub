<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Core\Attribute\Route;
use App\Core\Config;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Middleware\RateLimit;
use App\Security\RateLimiter;
use App\Service\AuthServiceInterface;
use App\Service\JwtService;

final class AuthApiController
{
    public function __construct(
        private AuthServiceInterface $auth,
        private JwtService $jwt,
        private RateLimiter $rateLimiter,
        private Config $config,
    ) {
    }

    #[Route('/api/auth/token', methods: 'POST', middleware: [RateLimit::class])]
    public function token(Request $request): Response
    {
        $username = trim((string) $request->input('username', ''));
        $password = (string) $request->input('password', '');
        $user = $this->auth->verify($username, $password);
        if ($user === null) {
            return Response::json(['message' => 'The username or password is incorrect.'], 401);
        }

        $normalized = strtolower($username);
        if ($normalized !== '') {
            $this->rateLimiter->clear('auth:username:' . $normalized);
        }

        return Response::json([
            'token' => $this->jwt->issue($user),
            'token_type' => 'Bearer',
            'expires_in' => max(300, (int) $this->config->get('jwt.ttl', 3600)),
            'user' => [
                'name' => $user->name,
                'username' => $user->username,
                'role' => $user->role,
            ],
        ]);
    }
}
