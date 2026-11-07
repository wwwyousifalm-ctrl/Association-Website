<?php
require __DIR__ . '/../../includes/bootstrap.php';
$me = require_login();
require_post();
$errs = [];
$d = check_profile($errs);
if (!isset($errs['email']) && q('SELECT 1 FROM users WHERE email = ? AND id <> ?', [$d['email'], $me['id']])->fetch()) $errs['email'] = 'Another account already uses this email.';
if ($errs) fail('Check the highlighted fields.', 422, $errs);
q('UPDATE users SET full_name = ?, email = ?, phone = ?, program = ?, level = ? WHERE id = ?',
  [$d['full_name'], $d['email'], $d['phone'], $d['program'], (int) $d['level'], $me['id']]);
ok();
