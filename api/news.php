<?php
require __DIR__ . '/../includes/bootstrap.php';
ok(q("SELECT id, title, body, DATE_FORMAT(published_at, '%Y-%m-%dT%H:%i:%s') AS published_at FROM news ORDER BY published_at DESC LIMIT 50")->fetchAll());
