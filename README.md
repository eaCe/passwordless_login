# :construction: WIP Anmelden ohne Passwort für REDAXO 5

Dieses Addon ermöglicht, sich ohne Passwort in REDAXO einzuloggen. Dazu wird beim Login die Mail-Adresse eingegeben und ein Link angefordert. Der Zugang zum Mail-Postfach ersetzt die Authentifizierung über ein Passwort.

> **Steuere eigene Verbesserungen** dem [GitHub-Repository](https://github.com/eaCe/passwordless_login) bei.

## Features

Das Addon bietet die Möglichkeit sich passwortlos in REDAXO anzumelden.
Via E-Mail erhält man einen Link, über den sich User automatisch anmelden können.
Der Link ist eine Stunde valide, danach verfällt dieser.

**User müssen zuvor mit einer E-Mail-Adresse angelegt worden sein.**

Die beiden Routen können über die Eps `PLL_ROUTE` und `PLL_LOGIN_ROUTE` angepasst werden.

### Warum?

Das Addon wurde entwickelt, um Nutzer*innen eine einfache Möglichkeit zu bieten sich in einem internen Bereich anzumelden.
Die Zugriffsrechte der User sind stark beschränkt.

## Installation

> **Installationsvoraussetzung:** Stelle sicher, dass PHPMailer in REDAXO installiert, aktiviert und ordnungsgemäß eingerichtet ist. [Weitere Informationen auf redaxo.org](https://www.redaxo.org/doku/main/addon-phpmailer)

Im REDAXO-Installer das Addon `passwordless_login` herunterladen und installieren. Das Addon verfügt über keine eigene Einstellungsseite.

Ein Formular für den E-Mail Versand einrichten. Die Formular-Methode muss Post sein und die Action auf die gesetzte Route verweisen, im Normalfall `/pll`. Weiter benötigt es ein email-Input.

```php
<form action="<?= rex_url::frontend('pll') ?>" method="post">
    <input type="email" name="email">
    <button type="submit">Senden</button>
</form>
```

## Lizenz

[MIT Lizenz](https://github.com/eaCe/passwordless_login/blob/master/LICENSE)

## Autoren

**eaCe**
https://github.com/eaCe

**Alexander Walther**
https://github.com/alxndr-w
