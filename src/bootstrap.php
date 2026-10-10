<?php
declare(strict_types=1);

define('DATA_DIR', getenv('WEBCERT_DATA') ?: dirname(__DIR__) . '/data');

if (!ini_get('date.timezone')) {
    date_default_timezone_set('Europe/Berlin');
}

require __DIR__ . '/CertificateAuthority.php';

session_name('webcert');
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Strict',
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();

// ------------------------------------------------------------- Sprache

/*
 * Die Oberfläche ist auf Deutsch geschrieben; der deutsche Text dient zugleich als Schlüssel
 * für die Übersetzungen in src/lang/<code>.php. Fehlt eine Übersetzung, erscheint der deutsche Text.
 * Eine neue Sprache lässt sich durch Anlegen einer weiteren Datei in src/lang/ hinzufügen.
 *
 * Auswahl: Sprachumschalter (Cookie) → WEBCERT_LANG → Browser-Sprache → Deutsch.
 */
const LANG_COOKIE = 'webcert_lang';

/** @return array<string, array{name: string, date: string, datetime: string, decimal: string, thousands: string, strings: array<string, string>}> */
function languages(): array
{
    static $languages = null;
    if ($languages === null) {
        $languages = [];
        foreach (glob(__DIR__ . '/lang/*.php') as $file) {
            $languages[basename($file, '.php')] = require $file;
        }
    }
    return $languages;
}

function detect_language(): string
{
    $languages = languages();
    $cookie = $_COOKIE[LANG_COOKIE] ?? null;
    if (is_string($cookie) && isset($languages[$cookie])) {
        return $cookie;
    }
    $configured = getenv('WEBCERT_LANG');
    if ($configured && isset($languages[$configured])) {
        return $configured;
    }
    // Accept-Language, z. B. "en-US,en;q=0.9,de;q=0.8"
    $best = null;
    $bestQ = 0.0;
    foreach (explode(',', (string)($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '')) as $part) {
        if (!preg_match('/^\s*([a-z]{2,3})(?:-[a-z0-9]+)*\s*(?:;\s*q=([0-9.]+))?/i', $part, $m)) {
            continue;
        }
        $code = strtolower($m[1]);
        $q = isset($m[2]) ? (float)$m[2] : 1.0;
        if (isset($languages[$code]) && $q > $bestQ) {
            [$best, $bestQ] = [$code, $q];
        }
    }
    return $best ?? (isset($languages['de']) ? 'de' : (string)array_key_first($languages));
}

function lang(): string
{
    static $lang = null;
    return $lang ??= detect_language();
}

function lang_pack(): array
{
    return languages()[lang()];
}

/** Übersetzt einen (deutschen) Text; Platzhalter {name} werden durch $params ersetzt. Ergebnis ist nicht HTML-escaped. */
function t(string $text, array $params = []): string
{
    $text = lang_pack()['strings'][$text] ?? $text;
    if ($params) {
        $text = strtr($text, array_combine(array_map(fn($k) => '{' . $k . '}', array_keys($params)), array_map('strval', $params)));
    }
    return $text;
}

// Sprachwechsel per ?lang=xx: merken und ohne Parameter auf dieselbe Seite zurückleiten
if (isset($_GET['lang']) && is_string($_GET['lang']) && isset(languages()[$_GET['lang']])) {
    setcookie(LANG_COOKIE, $_GET['lang'], [
        'expires'  => time() + 5 * 365 * 86400,
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    $query = $_GET;
    unset($query['lang']);
    header('Location: ?' . http_build_query($query));
    exit;
}

function lang_url(string $code): string
{
    return '?' . http_build_query(['lang' => $code] + $_GET);
}

// ------------------------------------------------------------- Ausgabe

function h(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $action, array $params = []): string
{
    return '?' . http_build_query(['a' => $action] + $params);
}

function redirect(string $action, array $params = []): never
{
    header('Location: ' . url($action, $params));
    exit;
}

function render(string $view, array $vars = []): never
{
    extract($vars);
    ob_start();
    require __DIR__ . "/views/$view.php";
    $content = ob_get_clean();
    require __DIR__ . '/views/layout.php';
    exit;
}

function send_download(string $content, string $filename, string $mime): never
{
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($content));
    header('Cache-Control: no-store');
    echo $content;
    exit;
}

function safe_filename(string $name): string
{
    $name = preg_replace('/[^A-Za-z0-9._-]+/', '_', str_replace('*', 'wildcard', $name));
    return trim($name, '._') ?: 'zertifikat';
}

function pem_to_der(string $pem): string
{
    return base64_decode(preg_replace('/-----[^-]+-----|\s+/', '', $pem));
}

function fmt_date(int $timestamp): string
{
    return date(lang_pack()['date'], $timestamp);
}

function fmt_datetime(int $timestamp): string
{
    return date(lang_pack()['datetime'], $timestamp);
}

function fmt_number(float $number, int $decimals = 0): string
{
    return number_format($number, $decimals, lang_pack()['decimal'], lang_pack()['thousands']);
}

function days_left(int $timestamp): int
{
    return (int)floor(($timestamp - time()) / 86400);
}

function cert_status(int $validTo): array
{
    $days = days_left($validTo);
    return match (true) {
        $days < 0   => ['expired', t('abgelaufen')],
        $days <= 30 => ['warn', t('läuft in {days} Tagen ab', ['days' => $days])],
        default     => ['ok', t('gültig')],
    };
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = [$type, $message];
}

function take_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

// ----------------------------------------------------------------- CSRF

function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}

function csrf_check(): void
{
    if (!hash_equals(csrf_token(), (string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit(t('Ungültiges Formular-Token. Bitte die Seite neu laden.'));
    }
}

// ------------------------------------------------------------ Anmeldung

function auth_file(): string
{
    return DATA_DIR . '/auth.json';
}

function auth_configured(): bool
{
    return is_file(auth_file());
}

function auth_set_password(string $password): void
{
    file_put_contents(auth_file(), json_encode(['hash' => password_hash($password, PASSWORD_DEFAULT)]));
    @chmod(auth_file(), 0600);
}

function auth_verify(string $password): bool
{
    $data = json_decode((string)@file_get_contents(auth_file()), true);
    return is_array($data) && password_verify($password, $data['hash'] ?? '');
}

function logged_in(): bool
{
    return !empty($_SESSION['user']);
}

function login(): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = true;
}
