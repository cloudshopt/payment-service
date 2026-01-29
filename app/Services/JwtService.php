<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtService
{
    public function decode(string $token): array
    {
        $secret = (string) config('jwt.secret');
        if ($secret === '') {
            throw new \RuntimeException('JWT_SECRET not configured.');
        }

        $decoded = JWT::decode($token, new Key($secret, 'HS256'));
        return (array) $decoded;
    }
}