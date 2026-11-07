<?php
require __DIR__ . '/../includes/bootstrap.php';
ok(q('SELECT id, title, program, level, course, type, file_path AS file_url FROM resources ORDER BY program, level, course, title')->fetchAll());
