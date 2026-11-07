<?php
require __DIR__ . '/../../includes/bootstrap.php';
require_admin();
$n = fn(string $sql) => (int) q($sql)->fetchColumn();
ok([
    'events'    => $n('SELECT COUNT(*) FROM events'),
    'projects'  => $n('SELECT COUNT(*) FROM projects'),
    'resources' => $n('SELECT COUNT(*) FROM resources'),
    'news'      => $n('SELECT COUNT(*) FROM news'),
    'students'  => $n("SELECT COUNT(*) FROM users WHERE role = 'student'"),
    'admins'    => $n("SELECT COUNT(*) FROM users WHERE role = 'admin'"),
    'messages'  => $n('SELECT COUNT(*) FROM messages WHERE is_read = 0'),
]);
