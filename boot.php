<?php

/** @var rex_addon $this */
rex_extension::register(
    'PACKAGES_INCLUDED',
    static function () {
        /**
         * set default routes...
         */
        PasswordLessLogin::$route = rex_extension::registerPoint(new rex_extension_point('PLL_ROUTE', 'pll'));
        PasswordLessLogin::$loginRoute = rex_extension::registerPoint(new rex_extension_point('PLL_LOGIN_ROUTE', 'pll-login'));

        if (!\rex::isBackend()) {
            PasswordLessLogin::handleLogin();
            PasswordLessLogin::handleSubmit();
        }
    }
);
