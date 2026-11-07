<?php
/* List / save / delete handler for the admin pages that manage simple content (events, projects, news).
   $table and the column names come from the endpoint files, never from the request, so they are safe in SQL text.
   Every value goes through a prepared statement. Only admins get past require_admin(). */
function crud(string $table, array $rules, string $listSql): void
{
    $me = require_admin();
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') ok(q($listSql)->fetchAll());
    require_post();
    $action = in('action');
    $id = (int) in('id');
    if ($action === 'delete') {
        if ($id < 1) fail('Nothing was selected.', 422);
        q("DELETE FROM $table WHERE id = ?", [$id]);
        ok();
    }
    if ($action !== 'save') fail('Unknown action.', 422);

    $errs = [];
    $vals = [];
    foreach ($rules as $col => [$kind, $max]) {
        $v = in($col);
        if ($v === '') { $errs[$col] = 'This field is required.'; continue; }
        if (mb_strlen($v) > $max) { $errs[$col] = "Use at most $max characters."; continue; }
        if ($kind === 'datetime') {
            $t = date_create_from_format('Y-m-d\TH:i', $v);
            if (!$t || $t->format('Y-m-d\TH:i') !== $v) { $errs[$col] = 'Enter a valid date and time.'; continue; }
            $v = $t->format('Y-m-d H:i:s');
        }
        if ($kind === 'github' && !preg_match('#^https://github\.com/[\w.-]+/[\w.-]+/?$#', $v)) {
            $errs[$col] = 'Enter a GitHub repository link, like https://github.com/name/repo.';
            continue;
        }
        $vals[$col] = $v;
    }
    if ($errs) fail('Check the highlighted fields.', 422, $errs);

    $cols = array_keys($vals);
    if ($id > 0) {
        $set = implode(', ', array_map(fn($c) => "$c = ?", $cols));
        q("UPDATE $table SET $set WHERE id = ?", [...array_values($vals), $id]);
    } else {
        $marks = implode(', ', array_fill(0, count($cols), '?'));
        q("INSERT INTO $table (" . implode(', ', $cols) . ", created_by) VALUES ($marks, ?)", [...array_values($vals), $me['id']]);
    }
    ok();
}
