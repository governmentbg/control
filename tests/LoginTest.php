<?php

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;

final class LoginTest extends TestCase
{
    use HTTPTrait;

    public function testLogin(): void
    {
        $this
            ->clear()
            ->get('/')
                ->assertStatus(303)
                ->assertHeader('Location', '/login')
            ->follow()
                ->assertStatus(200)
            ->post(['username' => 'admin', 'password' => 'admin1'])
                ->assertStatus(303)
                ->assertHeader('Location', '/login?error=wrong')
            ->follow()
                ->assertStatus(200)
            ->post(['username' => 'admin', 'password' => 'admin'])
                ->assertStatus(303)
                ->assertHeader('Location', '/')
            ->follow()
                ->assertStatus(200)
                ->assertLocation('/')
                ->assertBodyContains('Иван Божанов')
            ;
    }
}
