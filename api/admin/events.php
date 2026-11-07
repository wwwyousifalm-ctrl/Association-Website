<?php
require __DIR__ . '/../../includes/bootstrap.php';
require __DIR__ . '/../../includes/crud.php';
crud('events', ['title' => ['text', 150], 'starts_at' => ['datetime', 16], 'location' => ['text', 150], 'description' => ['text', 5000]],
     'SELECT id, title, starts_at, location, description FROM events ORDER BY starts_at DESC');
