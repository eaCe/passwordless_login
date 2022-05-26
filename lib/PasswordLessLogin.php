<?php

class PasswordLessLogin extends rex_backend_login
{
    public static string $table = 'passwordless_login';
    public static string $route = '';
    public static string $loginRoute = '';

    /**
     * @throws rex_exception
     */
    private static function getHash(): string {
        return rex_login::passwordHash(time() . 2) . rex_addon::get('passwordless_login')->getConfig('key');
    }

    /**
     * get current url path
     *
     * @return string
     */
    public static function getCurrentPath(): string {
        $url = parse_url($_SERVER['REQUEST_URI']);
        return $url['path'] ?? '';
    }

    /**
     * check if route current path is a route
     *
     * @param string $route
     * @return bool
     */
    private static function isRoute(string $route): bool {
        return mb_substr(self::getCurrentPath(), 1, mb_strlen($route)) === $route;
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
    public static function handleSubmit(): void {
        if (self::isRoute(self::$route)) {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new rex_http_exception(new rex_exception('Request-Method not allowed'), 405);
            }

            $email = rex_post('email', 'string');

            if ('' === $email) {
                throw new rex_http_exception(new rex_exception('E-Mail missing'), 400);
            }

            $sql = rex_sql::factory();
            $sql->setTable(rex::getTable('user'));
            $sql->setWhere('email = :email', ['email' => $email]);
            $sql->select('*');

            if ($sql->getRows()) {
                $user = $sql->getArray()[0];

                self::removePreviousEntries($user);
                $entryID = self::addEntry($user);
                if ($entryID) {
                    $sql = rex_sql::factory();
                    $sql->setTable(rex::getTable(self::$table));
                    $sql->setWhere('id = :id', ['id' => $entryID]);
                    $sql->select('*');
                    rex_activity::message(json_encode($sql->getArray()[0]))->type(rex_activity::TYPE_INFO)->log();
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
    public static function handleLogin(): void {
        /** return early if user is logged in */
        if (rex::getUser()) {
            return;
        }

        if (self::isRoute(self::$loginRoute)) {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new rex_http_exception(new rex_exception('Request-Method not allowed'), 405);
            }

            $hash = rex_get('hash', 'string');

            if ('' === $hash) {
                throw new rex_http_exception(new rex_exception('Hash missing'), 400);
            }

            $sql = rex_sql::factory();
            $sql->setTable(rex::getTable(self::$table));
            $sql->setWhere('hash = :hash', ['hash' => $hash]);
            $sql->select('*');

            if ($sql->getRows()) {
                $entry = $sql->getArray()[0];
                self::checkExpiration($entry);
                self::login($entry);
            }
        }

        exit();
    }

    /**
     * remove previous user entries
     *
     * @param array $user
     * @return void
     * @throws rex_sql_exception
     */
    private static function removePreviousEntries(array $user): void {
        $sql = rex_sql::factory();
        $sql->setTable(rex::getTable(self::$table));
        $sql->setWhere('user_id = :id', ['id' => $user['id']]);
        $sql->delete();
    }

    /**
     * add new user entry
     *
     * @param array $user
     * @return int
     * @throws rex_exception
     * @throws rex_sql_exception
     */
    private static function addEntry(array $user): int {
        $sql = rex_sql::factory();
        $sql->setTable(rex::getTable(self::$table));
        $sql->setValue('user_id', $user['id']);
        $sql->setValue('hash', self::getHash());
        $sql->setValue('expiration', date('Y-m-d H:i:s', time() + 3600));
        $sql->insert();
        return $sql->getLastId();
    }

    /**
     * @param array $entry
     * @return void
     * @throws rex_exception
     */
    private static function checkExpiration(array $entry) {
        if (strtotime($entry['expiration']) <= time()) {
            // TODO: remove hash from db..
            throw new rex_exception('Hash expired');
        }
    }

    /**
     * login
     *
     * @param array $entry
     * @return void
     * @throws rex_exception
     */
    private static function login(array $entry): void {
        $user = rex_user::get((int)$entry['user_id']);
        $sql = rex_sql::factory();
        self::startSession();
        self::regenerateSessionId();
        $_SESSION[static::getSessionNamespace()]['backend_login']['UID'] = $user->getValue('id');
        $_SESSION[static::getSessionNamespace()]['backend_login']['password'] = $user->getValue('password');
        $_SESSION[static::getSessionNamespace()]['backend_login']['STAMP'] = time();
        $params = [rex_sql::datetime(), rex_sql::datetime(), session_id(), $user->getLogin()];
        $sql->setQuery('UPDATE ' . rex::getTable('user') . ' SET login_tries=0, lasttrydate=?, lastlogin=?, session_id=? WHERE login=? LIMIT 1', $params);
        exit();
    }

    /**
     * send a mail to the user...
     *
     * @param array $entry
     * @return void
     * @throws \PHPMailer\PHPMailer\Exception
     * @throws rex_exception
     */
    public static function sendMail(array $entry): void {
        $mailFragment = new rex_fragment();
        $mailFragment->setVar('hash', $entry['hash'], false);
        $mailFragment->setVar('route', self::$loginRoute, false);
        $mailBody = $mailFragment->parse('pll-mail.php');

        $user = rex_user::get((int)$entry['user_id']);
        $mailer = new rex_mailer();
        $mailer->Subject = rex::getServerName();
        $mailer->Body = $mailBody;
        $mailer->AltBody = strip_tags($mailBody);
        $mailer->FromName = rex::getServerName();
        $mailer->addAddress($user->getEmail());
        $mailer->Send();

        header('Ok', true, 200);
        exit();
    }
}
