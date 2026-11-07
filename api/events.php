<?php
require __DIR__ . '/../includes/bootstrap.php';
ok(q("SELECT id, title, DATE_FORMAT(starts_at, '%Y-%m-%dT%H:%i:%s') AS starts_at, location, description FROM events ORDER BY starts_at")->fetchAll());
