<?php
require __DIR__ . '/../../includes/bootstrap.php';
$me = require_login();
require_post();
$theme = in('theme');
$lang = in('language');
if (!in_array($theme, ['system', 'light', 'dark'], true) || !in_array($lang, ['en', 'ar'], true)) fail('Choose one of the listed options.', 422);
q('UPDATE users SET theme = ?, language = ?, notify_email = ? WHERE id = ?', [$theme, $lang, isset($_POST['notify_email']) ? 1 : 0, $me['id']]);
ok();
