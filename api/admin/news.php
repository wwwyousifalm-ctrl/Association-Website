<?php
require __DIR__ . '/../../includes/bootstrap.php';
require __DIR__ . '/../../includes/crud.php';
crud('news', ['title' => ['text', 150], 'body' => ['text', 10000]],
     'SELECT id, title, body, published_at FROM news ORDER BY published_at DESC');
