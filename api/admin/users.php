<?php
require __DIR__ . '/../../includes/bootstrap.php';
$me = require_admin();
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    ok(q("SELECT id, full_name, email, student_id, program, level, role FROM users ORDER BY role = 'admin' DESC, full_name")->fetchAll());
}
require_post();
$action = in('action');
$id = (int) in('id');
$target = q('SELECT id, role FROM users WHERE id = ?', [$id])->fetch();
if (!$target) fail('That account no longer exists.', 404);
// Nobody can demote or delete themselves, so the site can never be left without an admin
if ($id === (int) $me['id']) fail('You cannot change or delete your own account here.', 403);

if ($action === 'delete') {
    q('DELETE FROM users WHERE id = ?', [$id]);
    ok();
}
if ($action !== 'promote' && $action !== 'demote') fail('Unknown action.', 422);
$new = $action === 'promote' ? 'admin' : 'student';
if ($target['role'] !== $new) {
    db()->beginTransaction();
    q('UPDATE users SET role = ? WHERE id = ?', [$new, $id]);
    q('INSERT INTO role_changes (user_id, changed_by, old_role, new_role) VALUES (?, ?, ?, ?)', [$id, $me['id'], $target['role'], $new]);
    db()->commit();
}
ok();
