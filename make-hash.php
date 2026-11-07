<?php
/* ONE-TIME TOOL. Turns a password into a hash for sql/first-admin.sql. Upload it, use it once, then DELETE it from the server. */
$hash = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && is_string($_POST['password'] ?? null) && $_POST['password'] !== '') {
    $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
}
?><!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="robots" content="noindex"><title>Make password hash</title></head>
<body>
<h1>Make password hash</h1>
<p>Delete this file from the server when you are done.</p>
<form method="post"><label for="p">Password</label> <input id="p" name="password" type="password" required> <button type="submit">Make hash</button></form>
<?php if ($hash !== ''): ?><p>Copy this into the password_hash value:</p><p><code><?= htmlspecialchars($hash, ENT_QUOTES) ?></code></p><?php endif; ?>
</body></html>
