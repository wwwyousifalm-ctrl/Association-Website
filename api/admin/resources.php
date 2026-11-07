<?php
require __DIR__ . '/../../includes/bootstrap.php';
$me = require_admin();
const TYPES = ['lecture', 'reference', 'notes', 'exam'];
$dir = __DIR__ . '/../../uploads/';

/** Delete an uploaded file, but only if the stored path is exactly what this script generates. */
function remove_file(string $path): void
{
    global $dir;
    if (preg_match('#^uploads/[a-f0-9]{32}\.pdf$#', $path)) @unlink($dir . basename($path));
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    ok(q('SELECT id, title, program, level, course, type, file_path FROM resources ORDER BY program, level, course, title')->fetchAll());
}
require_post();
$action = in('action');
$id = (int) in('id');

if ($action === 'delete') {
    $row = q('SELECT file_path FROM resources WHERE id = ?', [$id])->fetch();
    if (!$row) fail('That resource no longer exists.', 404);
    q('DELETE FROM resources WHERE id = ?', [$id]);
    remove_file($row['file_path']);
    ok();
}
if ($action !== 'save') fail('Unknown action.', 422);

$errs = [];
$title = in('title'); $course = in('course'); $program = in('program'); $level = in('level'); $type = in('type');
if ($title === '' || mb_strlen($title) > 150) $errs['title'] = 'Enter a title (up to 150 characters).';
if ($course === '' || mb_strlen($course) > 100) $errs['course'] = 'Enter the course name (up to 100 characters).';
if (!in_array($program, PROGRAMS, true)) $errs['program'] = 'Choose a program.';
if (!in_array($level, LEVELS, true)) $errs['level'] = 'Choose a level.';
if (!in_array($type, TYPES, true)) $errs['type'] = 'Choose a type.';

$upload = null;
$f = $_FILES['file'] ?? null;
if ($f && $f['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($f['error'] !== UPLOAD_ERR_OK || $f['size'] > 5 * 1024 * 1024) {
        $errs['file'] = 'The file could not be uploaded. Use a PDF under 5 MB.';
    } else {
        // Check the real content, not the name or the type the browser claims
        $mime = class_exists('finfo') ? (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']) : 'application/pdf';
        $head = (string) file_get_contents($f['tmp_name'], false, null, 0, 5);
        if ($mime !== 'application/pdf' || $head !== '%PDF-') $errs['file'] = 'Upload a PDF file.'; else $upload = $f;
    }
} elseif ($id < 1) {
    $errs['file'] = 'Choose a file to upload.';
}
if ($errs) fail('Check the highlighted fields.', 422, $errs);

$path = null;
if ($upload) {
    $name = bin2hex(random_bytes(16)) . '.pdf';   // random name: the original file name is never used
    if (!move_uploaded_file($upload['tmp_name'], $dir . $name)) fail('The file could not be saved on the server.', 500);
    $path = 'uploads/' . $name;
}
if ($id > 0) {
    $row = q('SELECT file_path FROM resources WHERE id = ?', [$id])->fetch();
    if (!$row) fail('That resource no longer exists.', 404);
    q('UPDATE resources SET title = ?, program = ?, level = ?, course = ?, type = ?, file_path = ? WHERE id = ?',
      [$title, $program, (int) $level, $course, $type, $path ?? $row['file_path'], $id]);
    if ($path) remove_file($row['file_path']);
} else {
    q('INSERT INTO resources (title, program, level, course, type, file_path, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)',
      [$title, $program, (int) $level, $course, $type, $path, $me['id']]);
}
ok();
