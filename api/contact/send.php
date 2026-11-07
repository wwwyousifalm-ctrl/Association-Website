<?php
require __DIR__ . '/../../includes/bootstrap.php';
require_post();
if (in('website') !== '') ok();   // hidden trap field: people never see it, bots fill it in
$name = in('name'); $email = strtolower(in('email')); $subject = in('subject'); $msg = in('message');
$errs = [];
if (mb_strlen($name) < 2 || mb_strlen($name) > 100) $errs['name'] = 'Enter your name.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) $errs['email'] = 'Enter a valid email address.';
if (!in_array($subject, ['question', 'resource', 'event', 'project', 'other'], true)) $errs['subject'] = 'Choose a subject.';
$len = mb_strlen($msg);
if ($len < 10 || $len > 2000) $errs['message'] = 'Write between 10 and 2000 characters.';
if ($errs) fail('Check the highlighted fields.', 422, $errs);
$mine = (int) q('SELECT COUNT(*) FROM messages WHERE email = ? AND created_at > (NOW() - INTERVAL 1 HOUR)', [$email])->fetchColumn();
$all = (int) q('SELECT COUNT(*) FROM messages WHERE created_at > (NOW() - INTERVAL 1 HOUR)')->fetchColumn();
if ($mine >= 3 || $all >= 40) fail('Too many messages right now. Please try again later.', 429);
q('INSERT INTO messages (name, email, subject, body) VALUES (?, ?, ?, ?)', [$name, $email, $subject, $msg]);
ok();
