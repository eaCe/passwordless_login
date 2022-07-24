<?php

class PasswordLessLogin extends rex_backend_login
{
    public static string $table = 'passwordless_login';
    public static string $route = '';
    public static string $loginRoute = '';

    /**
     * @throws rex_exception
     */
    private static function getHash(): string
    {
        return rex_login::passwordHash(time() . 2) . rex_addon::get('passwordless_login')->getConfig('key');
    }

    /**
     * get current url path
     *
     * @return string
     */
    public static function getCurrentPath(): string
    {
        $requestUri = filter_var($_SERVER["REQUEST_URI"], FILTER_SANITIZE_STRING);

        if ($requestUri !== false) {
            /** @var string[] */
            $parts = parse_url($requestUri);

            if (count($parts) !== 0) {
                return $parts['path'];
            }
        }

        return '';
    }

    /**
     * check if route current path is a route
     *
     * @param string $route
     * @return bool
     */
    private static function isRoute(string $route): bool
    {
        return str_replace('/', '', self::getCurrentPath()) === $route;
    }

    /**
     * handle form submit
     *
     * @return void
     * @throws \PHPMailer\PHPMailer\Exception
     * @throws rex_exception
     * @throws rex_http_exception
     * @throws rex_sql_exception
     */
    public static function handleSubmit(): void
    {
        if (self::isRoute(self::$route)) {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new rex_http_exception(new rex_exception('Request-Method not allowed'), '405');
            }

            $email = rex_post('email', 'string');

            if ('' === $email) {
                throw new rex_http_exception(new rex_exception('E-Mail missing'), '400');
            }

            $sql = rex_sql::factory();
            $sql->setTable(rex::getTable('user'));
            $sql->setWhere('email = :email', ['email' => $email]);
            $sql->select('*');

            if ($sql->getRows() > 0) {
                $user = $sql->getArray()[0];

                self::removePreviousEntries($user);
                $entryID = self::addEntry($user);
                if ($entryID !== 0) {
                    $sql = rex_sql::factory();
                    $sql->setTable(rex::getTable(self::$table));
                    $sql->setWhere('id = :id', ['id' => $entryID]);
                    $sql->select('*');
                    self::sendMail($sql->getArray()[0]);
                }
            }
        }
    }

    /**
     * handle form submit
     *
     * @return void
     * @throws rex_exception
     * @throws rex_http_exception
     * @throws rex_sql_exception
     */
    public static function handleLogin(): void
    {
        /** return early if user is logged in */
        if (rex::getUser() !== null) {
            return;
        }

        if (self::isRoute(self::$loginRoute)) {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new rex_http_exception(new rex_exception('Request-Method not allowed'), '405');
            }

            $hash = rex_get('hash', 'string');
            $secret = rex_get('secret', 'string');

            if ('' === $hash) {
                throw new rex_http_exception(new rex_exception('Hash missing'), '400');
            }

            if ('' === $secret) {
                throw new rex_http_exception(new rex_exception('Secret missing'), '400');
            }

            $sql = rex_sql::factory();
            $sql->setTable(rex::getTable(self::$table));
            $sql->setWhere('hash = :hash', ['hash' => $hash]);
            $sql->select('*');

            if ($sql->getRows() > 0) {
                $entry = $sql->getArray()[0];
                self::checkExpiration($entry);
                self::checkSecret($secret);
                self::login($entry);
            }
        }

        exit();
    }

    /**
     * remove previous user entries
     *
     * @param array<int|string, bool|float|int|string|null> $user
     * @return void
     * @throws rex_sql_exception
     */
    private static function removePreviousEntries(array $user): void
    {
        $sql = rex_sql::factory();
        $sql->setTable(rex::getTable(self::$table));
        $sql->setWhere('user_id = :id', ['id' => $user['id']]);
        $sql->delete();
    }

    /**
     * add new user entry
     *
     * @param array<int|string, bool|float|int|string|null> $user
     * @return int
     * @throws rex_exception
     * @throws rex_sql_exception
     */
    private static function addEntry(array $user): int
    {
        $sql = rex_sql::factory();
        $sql->setTable(rex::getTable(self::$table));
        $sql->setValue('user_id', $user['id']);
        $sql->setValue('hash', self::getHash());
        $sql->setValue('expiration', date('Y-m-d H:i:s', time() + 3600));
        $sql->insert();
        return (int) $sql->getLastId();
    }

    /**
     * @param array<int|string, bool|float|int|string|null> $entry
     * @return void
     * @throws rex_exception
     */
    private static function checkExpiration(array $entry): void
    {
        if (strtotime((string) $entry['expiration']) <= time()) {
            self::deleteEntry($entry);
            throw new rex_exception('Hash expired');
        }
    }

    /**
     * check the given secret
     *
     * @param string $secret
     * @return void
     * @throws rex_exception
     */
    private static function checkSecret(string $secret): void
    {
        $addonSecret = rex_addon::get('passwordless_login')->getProperty('secret');

        if ($secret !== $addonSecret) {
            throw new rex_exception('Wrong secret provided');
        }
    }

    /**
     * @param array<int|string, bool|float|int|string|null> $entry
     * @return void
     * @throws rex_sql_exception
     */
    private static function deleteEntry(array $entry): void
    {
        $sql = rex_sql::factory();
        $sql->setTable(rex::getTable(self::$table));
        $sql->setWhere('hash = :hash', ['hash' => $entry['hash']]);
        $sql->delete();
    }

    /**
     * login
     *
     * @param array<int|string, bool|float|int|string|null> $entry
     * @return void
     * @throws rex_exception
     */
    private static function login(array $entry): void
    {
        $user = rex_user::get((int) $entry['user_id']);

        if ($user === null) {
            throw new rex_exception('Invalid User');
        }

        $sql = rex_sql::factory();
        self::startSession();
        self::regenerateSessionId();
        $_SESSION[static::getSessionNamespace()]['backend_login']['UID'] = $user->getValue('id');
        $_SESSION[static::getSessionNamespace()]['backend_login']['password'] = $user->getValue('password');
        $_SESSION[static::getSessionNamespace()]['backend_login']['STAMP'] = time();
        $params = [rex_sql::datetime(), rex_sql::datetime(), session_id(), $user->getLogin()];
        $sql->setQuery('UPDATE ' . rex::getTable('user') . ' SET login_tries=0, lasttrydate=?, lastlogin=?, session_id=? WHERE login=? LIMIT 1', $params);
        self::deleteEntry($entry);
        rex_response::sendRedirect(rex_url::backend());
    }

    /**
     * get the login url
     *
     * @param array<int|string, bool|float|int|string|null> $entry
     * @return string
     */
    private static function getUrl(array $entry)
    {
        $addonSecret = rex_addon::get('passwordless_login')->getProperty('secret');
        return rex::getServer() . self::$loginRoute . '?' . http_build_query(['hash' => $entry['hash'], 'secret' => $addonSecret]);
    }

    /**
     * send a mail to the user...
     *
     * @param array<int|string, bool|float|int|string|null> $entry
     * @return void
     * @throws \PHPMailer\PHPMailer\Exception
     * @throws rex_exception
     */
    private static function sendMail(array $entry): void
    {
        $mailFragment = new rex_fragment();
        $mailFragment->setVar('url', self::getUrl($entry), false);
        $mailBody = $mailFragment->parse('pll-mail.php');

        $user = rex_user::get((int) $entry['user_id']);

        if ($user === null) {
            throw new rex_exception('Invalid User');
        }

        $mailer = new rex_mailer();
        $mailer->isHTML(true);

        // @codingStandardsIgnoreStart
        $mailer->Subject = rex::getServerName();
        $mailer->Body = $mailBody;
        $mailer->FromName = rex::getServerName();
        // @codingStandardsIgnoreEnd

        $mailer->addAddress($user->getEmail());
        $mailer->send();

        header('Ok', true, 200);
        exit();
    }
}
