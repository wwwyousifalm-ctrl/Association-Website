<?php
require __DIR__ . '/../../includes/bootstrap.php';
require_post();
$email = strtolower(in('email'));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('Enter a valid email address.', 422, ['email' => 'Enter a valid email address.']);
$u = q('SELECT id, full_name FROM users WHERE email = ?', [$email])->fetch();
if ($u) {
    $recent = (int) q('SELECT COUNT(*) FROM password_resets WHERE user_id = ? AND created_at > (NOW() - INTERVAL 1 HOUR)', [$u['id']])->fetchColumn();
    if ($recent < 3) {
        $token = bin2hex(random_bytes(32));
        q('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, NOW() + INTERVAL 1 HOUR)', [$u['id'], hash('sha256', $token)]);
        $link = rtrim((string) cfg('site_url'), '/') . '/reset-password.html?token=' . $token;
        send_mail($email, 'Reset your password',
            "Hello {$u['full_name']},\n\nUse this link within one hour to choose a new password:\n$link\n\nIf you did not ask for this, you can ignore this email.\n\nStudent Association, FMCS, University of Gezira");
    }
}
ok();   // the same reply whether or not the email exists, so nobody can test which emails are registered
