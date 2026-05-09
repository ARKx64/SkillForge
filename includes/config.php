<?php
// ============================================================
//  SkillForge - Shared configuration and helper functions
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'skillforge_db');
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'SkillForge');
define('APP_VERSION', '1.1.0');

ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');

error_reporting(E_ALL);
ini_set('display_errors', '1');

function detectAppPath(): string
{
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

    if ($scriptDir === '/' || $scriptDir === '.') {
        return '';
    }

    foreach (['/php', '/includes'] as $suffix) {
        if (str_ends_with($scriptDir, $suffix)) {
            $scriptDir = substr($scriptDir, 0, -strlen($suffix));
            break;
        }
    }

    return rtrim($scriptDir, '/');
}

function detectAppUrl(): string
{
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443');

    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return $scheme . '://' . $host . APP_PATH;
}

define('APP_PATH', detectAppPath());
define('APP_URL', detectAppUrl());

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    $message = 'Database connection failed. Please import database.sql in phpMyAdmin and verify your XAMPP MySQL settings.';

    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }

    http_response_code(500);
    exit('<h2>' . APP_NAME . '</h2><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>');
}

function startSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function app_url(string $path = ''): string
{
    $normalized = ltrim($path, '/');
    return $normalized === '' ? APP_URL : APP_URL . '/' . $normalized;
}

function asset_url(?string $path = null): ?string
{
    if (!$path) {
        return null;
    }

    return app_url(ltrim($path, '/'));
}

function avatar_url(?string $filename = null): ?string
{
    if (!$filename) {
        return null;
    }

    return app_url('assets/images/' . rawurlencode($filename));
}

function isLoggedIn(): bool
{
    startSession();
    return isset($_SESSION['user_id']);
}

function currentUser(): ?array
{
    startSession();
    return $_SESSION ?: null;
}

function sanitize($input): string
{
    return htmlspecialchars(strip_tags(trim((string) $input)), ENT_QUOTES, 'UTF-8');
}

function setFlash(string $type, string $message): void
{
    startSession();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    startSession();

    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function addNotification(PDO $pdo, int $userId, string $message, string $type = 'info'): void
{
    $stmt = $pdo->prepare('INSERT INTO notifications (user_id, message, type) VALUES (?, ?, ?)');
    $stmt->execute([$userId, $message, $type]);
}
