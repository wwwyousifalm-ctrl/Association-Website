<?php
require __DIR__ . '/../../includes/bootstrap.php';
require __DIR__ . '/../../includes/crud.php';
crud('projects', ['title' => ['text', 150], 'description' => ['text', 5000], 'tech' => ['text', 150], 'github_url' => ['github', 255]],
     'SELECT id, title, description, tech, github_url FROM projects ORDER BY created_at DESC');
