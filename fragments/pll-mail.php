<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?= rex_i18n::msg('passwordless_login_email_text') ?></title>
    <style type="text/css">
        body{margin:0;background-color:#f3f6fb;font-family:sans-serif}table{border-spacing:0}td{padding:0}img{border:0}.wrapper{width:100%;table-layout:fixed;background-color:#f3f6fb;padding-bottom:60px;margin-top:40px}.main{background-color:#fff;margin:0 auto;width:600px;color:#283542;padding:50px;line-height:20px}.button{background-color:#283542;color:#fff;text-decoration:none;padding:12px 20px;font-weight:700;border-radius:5px}.link{color:#324050;text-decoration-color:#324050}.footer{margin-top:20px;width:600px}
    </style>
</head>
<body>
<center class="wrapper">
    <table class="main" width="100%">
        <tr>
            <td style="font-size: 16px; text-align: center; width: 100%; padding: 10px 5px;">
                <p class="content">
                    <?= rex_i18n::msg('passwordless_login_email_text') ?>
                </p>
            </td>
        </tr>
        <tr>
            <td height="20"></td>
        </tr>
        <tr style="text-align: center">
            <td>
                <a target="_blank" href="<?= $this->url ?>" class="button"><?= rex_i18n::msg('passwordless_login_email_login') ?></a>
            </td>
        </tr>
    </table>
    <table class="footer">
        <tr>
            <td style="text-align: center; color: #324050; font-size: 14px;">
                <p><a href="<?= rex::getServer() ?>" target="_blank" class="link"><?= rex::getServerName() ?></a></p>
            </td>
        </tr>
    </table>
</center>
</body>
</html>
