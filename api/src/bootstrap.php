<?php
declare(strict_types=1);
function config(): array {
    static $config;
    if ($config === null) {
        // Container configuration takes precedence; never writes credentials into the image.
        if (getenv('CB_DB_HOST') !== false) {
            $password = getenv('CB_DB_PASSWORD');
            if ($password === false || $password === '') throw new RuntimeException('Missing database password');
            $config = [
                'environment' => getenv('CB_ENVIRONMENT') ?: 'production',
                'db_host' => getenv('CB_DB_HOST'),
                'db_port' => (int)(getenv('CB_DB_PORT') ?: 3306),
                'db_name' => getenv('CB_DB_NAME') ?: 'carlosburgos',
                'db_user' => getenv('CB_DB_USER') ?: 'carlosburgos',
                'db_password' => $password,
            ];
            return $config;
        }
        $file = dirname(__DIR__) . '/config.local.php';
        if (!is_file($file)) throw new RuntimeException('Missing local configuration');
        $config = require $file;
        if (!is_array($config)) throw new RuntimeException('Invalid local configuration');
    }
    return $config;
}
function db(): PDO {
    static $db;
    if (!$db) {
        $c = config();
        $db = new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['db_host'], $c['db_port'], $c['db_name']), $c['db_user'], $c['db_password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
        $db->exec("SET time_zone = '+00:00'");
    }
    return $db;
}
function respond(array $data, int $status = 200): never {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}
function body(): array {
    if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== 0) respond(['error' => 'Se requiere JSON.'], 415);
    $input = file_get_contents('php://input', false, null, 0, 100001);
    if (strlen($input) > 100000) respond(['error' => 'Solicitud demasiado extensa.'], 413);
    try { $data = json_decode($input, true, 32, JSON_THROW_ON_ERROR); }
    catch (JsonException $e) { respond(['error' => 'JSON inválido.'], 400); }
    if (!is_array($data) || !str_starts_with(ltrim($input), '{')) respond(['error' => 'Objeto JSON requerido.'], 400);
    return $data;
}
function beginSession(): void {
    $production = (config()['environment'] ?? 'production') !== 'local';
    if ($production && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off')) respond(['error' => 'HTTPS requerido.'], 400);
    ini_set('session.gc_maxlifetime', '28800');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('cb_session');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/api', 'secure' => $production, 'httponly' => true, 'samesite' => 'Strict']);
    $sessionPath = (getenv('CB_STORAGE_PATH') ?: dirname(__DIR__).'/storage') . '/sessions';
    if (!is_dir($sessionPath) && !mkdir($sessionPath, 0700, true) && !is_dir($sessionPath)) throw new RuntimeException('Session directory unavailable');
    session_save_path($sessionPath);
    if (!session_start()) throw new RuntimeException('Session unavailable');
    if (isset($_SESSION['user_id']) && (time() - ($_SESSION['last_seen'] ?? 0) > 1800 || time() - ($_SESSION['created_at'] ?? 0) > 28800)) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}
function csrf(): void {
    if (!hash_equals($_SESSION['csrf'], $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) respond(['error' => 'Sesión de formulario inválida. Recargue la página.'], 403);
}
function currentUser(): ?array {
    if (!isset($_SESSION['user_id'])) return null;
    $q = db()->prepare('SELECT id, name, email, role, session_version FROM cms_users WHERE id = ? AND active = 1');
    $q->execute([$_SESSION['user_id']]);
    $user = $q->fetch();
    if (!$user || (int)$user['session_version'] !== ($_SESSION['session_version'] ?? 0)) { unset($_SESSION['user_id']); return null; }
    $_SESSION['last_seen'] = time();
    unset($user['session_version']);
    return $user;
}
function requireEditor(): array {
    $user = currentUser();
    if (!$user) respond(['error' => 'Inicie sesión para continuar.'], 401);
    if (!in_array($user['role'], ['admin', 'editor'], true)) respond(['error' => 'Sin permiso de edición.'], 403);
    unset($user['session_version']);
    return $user;
}
function settings(): array {
    $row = db()->query('SELECT content, version FROM site_settings WHERE id = 1')->fetch();
    if (!$row) throw new RuntimeException('Settings not initialized');
    return ['content' => json_decode($row['content'], true, 32, JSON_THROW_ON_ERROR), 'version' => (int)$row['version']];
}
