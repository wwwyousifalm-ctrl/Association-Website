<?php
require __DIR__ . '/../../includes/bootstrap.php';
require_admin();
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    ok(q('SELECT id, name, email, subject, body, is_read, created_at FROM messages ORDER BY created_at DESC LIMIT 200')->fetchAll());
}
require_post();
$action = in('action');
$id = (int) in('id');
if ($action === 'read' || $action === 'unread') q('UPDATE messages SET is_read = ? WHERE id = ?', [$action === 'read' ? 1 : 0, $id]);
elseif ($action === 'delete') q('DELETE FROM messages WHERE id = ?', [$id]);
else fail('Unknown action.', 422);
ok();
