<?php

declare(strict_types=1);

namespace tests;

use helpers\AuthManager;
use vakata\authentication\Credentials;

class MockAuthManager extends AuthManager
{
    public function supports(array $data = []): bool
    {
        if (isset($data['username']) && isset($data['password'])) {
            return true;
        }
        return parent::supports($data);
    }
    public function authenticate(array $data = []): Credentials
    {
        if (
            isset($data['username']) &&
            isset($data['password']) &&
            $data['username'] === 'admin' &&
            $data['password'] === 'admin'
        ) {
            return new Credentials('mockauth', 'mockauth');
        }
        return parent::authenticate($data);
    }
}
