<?php
require __DIR__ . '/../../includes/bootstrap.php';
require_post();
$errs = [];
$d = check_profile($errs);
$sid = in('student_id');
if (!preg_match('/^\d{4,20}$/', $sid)) $errs['student_id'] = 'Student ID must be 4 to 20 digits.';
$pw = raw('password');
check_password($pw, 'password', $errs);
if (raw('password_confirm') !== $pw) $errs['password_confirm'] = 'The passwords do not match.';
if (empty($_POST['terms'])) $errs['terms'] = 'Accept the privacy policy to continue.';
if (!$errs) {
    if (q('SELECT 1 FROM users WHERE email = ?', [$d['email']])->fetch()) $errs['email'] = 'An account with this email already exists.';
    if (q('SELECT 1 FROM users WHERE student_id = ?', [$sid])->fetch()) $errs['student_id'] = 'This student ID is already registered. Contact the committee if it is yours.';
}
if ($errs) fail('Check the highlighted fields.', 422, $errs);
try {
    // The role is never read from the request: public registration can only create students
    q('INSERT INTO users (full_name, email, student_id, phone, program, level, password_hash, role) VALUES (?, ?, ?, ?, ?, ?, ?, \'student\')',
    [$d['full_name'], $d['email'], $sid, $d['phone'], $d['program'], (int) $d['level'], password_hash($pw, PASSWORD_DEFAULT)]);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') fail('That email or student ID is already registered.', 422);
    throw $e;
}
ok();