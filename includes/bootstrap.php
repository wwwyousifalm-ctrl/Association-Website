<?php
/* Shared setup for every API endpoint: config, database, session, JSON replies, CSRF, access checks, email. */
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';

// Keep these two lists in sync with the <select> options in register.html and settings.html
const PROGRAMS = ['computer-science', 'information-technology', 'mathematics', 'statistics'];
const LEVELS = ['1', '2', '3', '4'];

ini_set('display_errors', '0');

/* ---------- JSON replies ---------- */
function out(array $body, int $code = 200)
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function ok($data = []) { out(['ok' => true, 'data' => $data]); }
function fail(string $msg, int $code = 400, array $fields = []) { out(['ok' => false, 'error' => $msg, 'fields' => (object) $fields], $code); }

set_exception_handler(function (Throwable $e) {
    error_log($e->getMessage());   // details go to the server log, never to the visitor
    fail('Something went wrong on the server. Please try again later.', 500);
});

/* ---------- Config and database ---------- */
$cfgFile = __DIR__ . '/../config.php';
if (!is_file($cfgFile)) fail('The server is not configured yet.', 500);
$GLOBALS['cfg'] = require $cfgFile;
function cfg(string $key, $default = null) { return $GLOBALS['cfg'][$key] ?? $default; }

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . cfg('db_host') . ';dbname=' . cfg('db_name') . ';charset=utf8mb4',
            cfg('db_user'), cfg('db_pass'),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
        );
    }
    return $pdo;
}
/** Run a prepared statement. Every query in the site goes through here, so no value is ever put into SQL text. */
function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

/* ---------- Session: HttpOnly, SameSite, Secure on HTTPS, 2 hour idle timeout ---------- */
function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('fmcs_sid');
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
    if (isset($_SESSION['last']) && $_SESSION['last'] < time() - 7200) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION['last'] = time();
}
start_session();

/* ---------- CSRF: every POST must carry the session's token and come from this site ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') fail('Method not allowed.', 405);
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $host = explode(':', $_SERVER['HTTP_HOST'] ?? '')[0];
    if ($origin !== '' && parse_url($origin, PHP_URL_HOST) !== $host) fail('Request blocked.', 403);
    $sent = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) fail('Your session expired. Reload the page and try again.', 403);
}

/* ---------- Who is logged in. The role is read from the database on every request. ---------- */
function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['uid'])) {
            $user = q('SELECT id, full_name, email, student_id, phone, program, level, role, theme, language, notify_email FROM users WHERE id = ?', [$_SESSION['uid']])->fetch() ?: null;
            if (!$user) $_SESSION = [];
        }
    }
    return $user;
}
function require_login(): array
{
    $u = current_user();
    if (!$u) fail('Please log in to continue.', 401);
    return $u;
}
function require_admin(): array
{
    $u = require_login();
    if ($u['role'] !== 'admin') fail('You do not have permission to do that.', 403);
    return $u;
}
function log_in(int $id): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = $id;
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

/* ---------- Input and validation ---------- */
function in(string $key): string { $v = $_POST[$key] ?? ''; return is_string($v) ? trim($v) : ''; }
function raw(string $key): string { $v = $_POST[$key] ?? ''; return is_string($v) ? $v : ''; }

function check_profile(array &$errs): array
{
    $d = ['full_name' => in('full_name'), 'email' => strtolower(in('email')), 'phone' => in('phone'), 'program' => in('program'), 'level' => in('level')];
    $n = mb_strlen($d['full_name']);
    if ($n < 3 || $n > 100) $errs['full_name'] = 'Enter your full name (3 to 100 characters).';
    if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL) || strlen($d['email']) > 150) $errs['email'] = 'Enter a valid email address.';
    if (!preg_match('/^\+?[0-9 ()-]{7,20}$/', $d['phone'])) $errs['phone'] = 'Enter a valid phone number.';
    if (!in_array($d['program'], PROGRAMS, true)) $errs['program'] = 'Choose your program.';
    if (!in_array($d['level'], LEVELS, true)) $errs['level'] = 'Choose your level.';
    return $d;
}
function check_password(string $pw, string $field, array &$errs): void
{
    if (strlen($pw) < 8 || strlen($pw) > 72 || !preg_match('/[A-Za-z]/', $pw) || !preg_match('/\d/', $pw)) {
        $errs[$field] = 'Use 8 to 72 characters, with at least one letter and one number.';
    }
}

/* ---------- Login helpers ---------- */
function throttle(string $email): void
{
    $n = (int) q('SELECT COUNT(*) FROM login_attempts WHERE email = ? AND attempted_at > (NOW() - INTERVAL 15 MINUTE)', [$email])->fetchColumn();
    if ($n >= 5) fail('Too many failed attempts. Wait 15 minutes and try again.', 429);
}
function do_login(bool $adminOnly): void
{
    require_post();
    $email = strtolower(in('email'));
    $pw = raw('password');
    if ($email === '' || $pw === '') fail('Enter your email and password.', 422);
    throttle($email);
    $u = q('SELECT id, password_hash, role FROM users WHERE email = ?', [$email])->fetch();
    // Always run password_verify so a wrong email and a wrong password take the same time
    $hash = $u['password_hash'] ?? '$2y$10$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUVWXYZ01234';
    $good = password_verify($pw, $hash) && $u && (!$adminOnly || $u['role'] === 'admin');
    if (!$good) {
        q('INSERT INTO login_attempts (email) VALUES (?)', [$email]);
        fail('Wrong email or password.', 401);   // same message for every failure, so accounts and roles are not revealed
    }
    q('DELETE FROM login_attempts WHERE email = ? OR attempted_at < (NOW() - INTERVAL 1 DAY)', [$email]);
    log_in((int) $u['id']);
    ok(['role' => $u['role']]);
}

/* ---------- Email through SMTP (InfinityFree blocks PHP mail()) ---------- */
function send_mail(string $to, string $subject, string $body): bool
{
    if (!cfg('smtp_host') || !cfg('smtp_user')) { error_log('Email not sent: SMTP is not configured in config.php'); return false; }
    try {
        $m = new PHPMailer(true);
        $m->isSMTP();
        $m->Host = cfg('smtp_host');
        $m->Port = (int) cfg('smtp_port', 587);
        $m->SMTPAuth = true;
        $m->Username = cfg('smtp_user');
        $m->Password = cfg('smtp_pass');
        $m->SMTPSecure = cfg('smtp_secure', 'tls') === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $m->Timeout = 10;
        $m->CharSet = 'UTF-8';
        $m->setFrom(cfg('mail_from', cfg('smtp_user')), 'Student Association FMCS');
        $m->addAddress($to);
        $m->Subject = $subject;
        $m->Body = $body;
        return $m->send();
    } catch (Throwable $e) {
        error_log('Email failed: ' . $e->getMessage());
        return false;
    }
}
