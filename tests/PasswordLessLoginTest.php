<?php

use PHPUnit\Framework\TestCase;

final class PasswordLessLoginTest extends TestCase
{
    /**
     * just for testing the unit test... :)
     *
     * @return void
     */
    public function testRouteEP(): void {
        PasswordLessLogin::$route = rex_extension::registerPoint(new rex_extension_point('PLL_ROUTE', 'test-route'));
        PasswordLessLogin::$loginRoute = rex_extension::registerPoint(new rex_extension_point('PLL_LOGIN_ROUTE', 'test-login-route'));

        $this->assertSame('test-route', PasswordLessLogin::$route);
        $this->assertSame('test-login-route', PasswordLessLogin::$loginRoute);
    }
}