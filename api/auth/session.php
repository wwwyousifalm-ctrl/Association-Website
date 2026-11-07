<?php
require __DIR__ . '/../../includes/bootstrap.php';
$u = current_user();
ok(['authenticated' => (bool) $u, 'role' => $u['role'] ?? null, 'user' => $u, 'csrf_token' => csrf_token()]);
