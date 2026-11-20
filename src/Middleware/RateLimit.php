<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Config;
use App\Core\Http\MiddlewareInterface;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Exception\TooManyRequestsException;
use App\Security\ClientIp;
use App\Security\RateLimiter;

final class RateLimit implements MiddlewareInterface
{
    public function __construct(
        private RateLimiter $limiter,
        private Config $config,
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        $rule = $this->ruleFor($request);
        if ($rule === null) {
            return $next($request);
        }

        [$maxAttempts, $decaySeconds] = $rule;
        $trusted = $this->config->get('security.trusted_proxies', []);
        $trusted = is_array($trusted) ? array_values(array_filter(array_map('strval', $trusted))) : [];
        $ip = ClientIp::resolve($request, $trusted);
        $username = strtolower(trim((string) $request->input('username', '')));

        $keys = ['auth:ip:' . $ip . ':' . $request->method . ':' . $request->path];
        if ($username !== '') {
            $keys[] = 'auth:username:' . $username;
        }

        foreach ($keys as $key) {
            $attempt = $this->limiter->attempt($key, $maxAttempts, $decaySeconds);
            if (!$attempt['allowed']) {
                throw new TooManyRequestsException(
                    'Too many attempts. Please try again later.',
                    $attempt['retry_after'],
                );
            }
        }

        return $next($request);
    }

    /** @return array{0: int, 1: int}|null */
    private function ruleFor(Request $request): ?array
    {
        $map = [
            'POST /login' => [5, 900],
            'POST /admin/login' => [5, 900],
            'POST /register' => [3, 3600],
            'POST /api/auth/token' => [5, 900],
        ];

        $key = $request->method . ' ' . $request->path;
        if (isset($map[$key])) {
            return $map[$key];
        }

        if (
            $request->method === 'POST'
            && preg_match('#^(/api)?/products/[a-z0-9\-]+/purchase$#', $request->path) === 1
        ) {
            return [30, 60];
        }

        return null;
    }
}
