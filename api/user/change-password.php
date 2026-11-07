<?php
require __DIR__ . '/../../includes/bootstrap.php';
$me = require_login();
require_post();
$errs = [];
$hash = q('SELECT password_hash FROM users WHERE id = ?', [$me['id']])->fetchColumn();
if (!password_verify(raw('current_password'), (string) $hash)) $errs['current_password'] = 'Your current password is not correct.';
$pw = raw('new_password');
check_password($pw, 'new_password', $errs);
if (raw('new_password_confirm') !== $pw) $errs['new_password_confirm'] = 'The passwords do not match.';
if ($errs) fail('Check the highlighted fields.', 422, $errs);
q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($pw, PASSWORD_DEFAULT), $me['id']]);
session_regenerate_id(true);
ok();
