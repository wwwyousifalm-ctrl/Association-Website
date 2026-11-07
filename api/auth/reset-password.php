<?php
require __DIR__ . '/../../includes/bootstrap.php';
require_post();
$errs = [];
$pw = raw('new_password');
check_password($pw, 'new_password', $errs);
if (raw('new_password_confirm') !== $pw) $errs['new_password_confirm'] = 'The passwords do not match.';
if ($errs) fail('Check the highlighted fields.', 422, $errs);
$token = in('token');
$row = preg_match('/^[a-f0-9]{64}$/', $token)
    ? q('SELECT id, user_id FROM password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()', [hash('sha256', $token)])->fetch()
    : false;
if (!$row) fail('This reset link is not valid or has expired. Ask for a new one.', 400);
db()->beginTransaction();
q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($pw, PASSWORD_DEFAULT), $row['user_id']]);
q('UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL', [$row['user_id']]);
db()->commit();
ok();
