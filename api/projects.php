<?php
require __DIR__ . '/../includes/bootstrap.php';
ok(q('SELECT id, title, description, tech, github_url FROM projects ORDER BY created_at DESC')->fetchAll());
