<?php

use PHPUnit\Framework\TestCase;

final class PasswordLessLoginTest extends TestCase
{
    /**
     * just for testing the unit test... :)
     *
     * @return void
     */
    public function testRouteEP(): void
    {
        PasswordLessLogin::$route = \rex_extension::registerPoint(new \rex_extension_point('PLL_ROUTE', 'test-route'));
        PasswordLessLogin::$loginRoute = \rex_extension::registerPoint(new \rex_extension_point('PLL_LOGIN_ROUTE', 'test-login-route'));

        \rex_config::set('testns', 'testeky', 'test');

        $this::assertSame('test-route', PasswordLessLogin::$route);
        $this::assertSame('test-login-route', PasswordLessLogin::$loginRoute);
    }

    /**
     * @return void
     * @throws rex_exception
     * @throws rex_http_exception
     * @throws rex_sql_exception
     */
    public function testWrongLoginRequestMethod(): void
    {
        $this->expectException(\rex_exception::class);
        $this->expectExceptionMessage('Request-Method not allowed');

        PasswordLessLogin::$loginRoute = 'pll';
        $_SERVER['REQUEST_URI'] = '/pll/';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        PasswordLessLogin::handleLogin();
    }

    /**
     * @return void
     * @throws rex_exception
     * @throws rex_http_exception
     * @throws rex_sql_exception
     */
    public function testMissingLoginHash(): void
    {
        $this->expectException(\rex_exception::class);
        $this->expectExceptionMessage('Hash missing');

        PasswordLessLogin::$loginRoute = 'pll';
        $_SERVER['REQUEST_URI'] = '/pll/';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        PasswordLessLogin::handleLogin();
    }

    /**
     * @return void
     * @throws rex_exception
     * @throws rex_http_exception
     * @throws rex_sql_exception
     */
    public function testMissingLoginSecret(): void
    {
        $this->expectException(\rex_exception::class);
        $this->expectExceptionMessage('Secret missing');

        PasswordLessLogin::$loginRoute = 'pll';
        $_SERVER['REQUEST_URI'] = '/pll/';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET['hash'] = uniqid('', false);

        PasswordLessLogin::handleLogin();
    }

    /**
     * @return void
     * @throws rex_exception
     * @throws rex_http_exception
     * @throws rex_sql_exception
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function testWrongRequestMethod(): void
    {
        $this->expectException(\rex_http_exception::class);
        $this->expectExceptionMessage('Request-Method not allowed');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = 'test-route';

        PasswordLessLogin::handleSubmit();
    }

    /**
     * @return void
     * @throws rex_exception
     * @throws rex_http_exception
     * @throws rex_sql_exception
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function testMissingEmail(): void
    {
        $this->expectException(\rex_http_exception::class);
        $this->expectExceptionMessage('E-Mail missing');

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = 'test-route';

        PasswordLessLogin::handleSubmit();
    }

    /**
     * @return void
     */
    public function testGetCurrentPath(): void
    {
        $_SERVER['REQUEST_URI'] = 'test-route';
        
        $this::assertSame('test-route', str_replace('/', '', PasswordLessLogin::getCurrentPath()));
    }
}
