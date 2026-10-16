<?php
declare(strict_types=1);
require '/app/api/src/bootstrap.php';

// This suite must never create or modify accounts in the real local database.
if (getenv('CB_TEST_MODE') !== '1' || !is_file('/storage/test-credentials.json')) {
    throw new RuntimeException('Disposable seeded test environment required');
}
if (PHP_MAJOR_VERSION !== 8 || PHP_MINOR_VERSION !== 2) {
    throw new RuntimeException('Password regression requires PHP 8.2');
}
$checks = 0;
function check(bool $ok, string $label): void {
    global $checks;
    if (!$ok) throw new RuntimeException($label);
    $checks++;
}
function httpCall(array &$client, string $path, string $method = 'GET', ?array $data = null, int $expected = 200): array {
    $headers = ['Content-Type: application/json'];
    if (isset($client['cookie'])) $headers[] = 'Cookie: '.$client['cookie'];
    if (isset($client['csrf'])) $headers[] = 'X-CSRF-Token: '.$client['csrf'];
    $options = ['method'=>$method, 'header'=>implode("\r\n", $headers), 'ignore_errors'=>true, 'timeout'=>15];
    if ($data !== null) $options['content'] = json_encode((object)$data, JSON_THROW_ON_ERROR);
    $raw = file_get_contents('http://127.0.0.1:8080/api'.$path, false, stream_context_create(['http'=>$options]));
    $responseHeaders = $http_response_header ?? [];
    preg_match('~HTTP/\S+ (\d+)~', $responseHeaders[0] ?? '', $matches);
    // Never print payloads, credentials, cookies, tokens or database snapshots on failure.
    check((int)($matches[1] ?? 0) === $expected, $method.' '.$path.' expected HTTP '.$expected.', got '.($matches[1] ?? 'none'));
    foreach ($responseHeaders as $header) {
        if (stripos($header, 'Set-Cookie: cb_session=') === 0) $client['cookie'] = explode(';', substr($header, 12))[0];
    }
    $result = json_decode($raw ?: '', true, 32, JSON_THROW_ON_ERROR);
    if (isset($result['csrf'])) $client['csrf'] = $result['csrf'];
    return $result;
}
function login(array &$client, string $email, string $password, int $expected = 200): void {
    // Only this isolated test caller; rate-limit behavior remains covered by editorial.mjs.
    $q = db()->prepare('DELETE FROM login_limits WHERE ip_key=?');
    $q->execute([hash('sha256', '127.0.0.1')]);
    httpCall($client, '/admin/session');
    httpCall($client, '/admin/login', 'POST', ['email'=>$email, 'password'=>$password], $expected);
}
function snapshot(int $id): array {
    $q = db()->prepare('SELECT password_hash,session_version FROM cms_users WHERE id=?');
    $q->execute([$id]); $user = $q->fetch();
    $q = db()->prepare('SELECT token_hash,expires_at FROM password_resets WHERE user_id=? ORDER BY token_hash');
    $q->execute([$id]); $tokens = $q->fetchAll();
    $q = db()->prepare("SELECT COUNT(*) FROM cms_audit WHERE user_id=? AND action='password.reset'");
    $q->execute([$id]);
    return ['user'=>$user, 'tokens'=>$tokens, 'resets'=>(int)$q->fetchColumn()];
}
try {
    $admin = [];
    $credentials = json_decode(file_get_contents('/storage/test-credentials.json'), true, 32, JSON_THROW_ON_ERROR);
    login($admin, 'admin@example.invalid', $credentials['password']);
    $email = 'password-regression-'.bin2hex(random_bytes(8)).'@example.invalid';
    $created = httpCall($admin, '/admin/users', 'POST', ['name'=>'Password regression', 'email'=>$email, 'role'=>'editor', 'active'=>true], 201);
    $id = (int)$created['id'];
    $previousSession = null;
    $passwords = [
        'activation-spaces'=>'  '.bin2hex(random_bytes(12)).'  ',
        'reset-spaces'=>'  '.bin2hex(random_bytes(12)).'  ',
        'ordinary'=>bin2hex(random_bytes(12)),
        'utf8'=>str_repeat('á', 20).bin2hex(random_bytes(8)),
        'minimum-12'=>'  '.bin2hex(random_bytes(4)).'  ',
        'maximum-72'=>bin2hex(random_bytes(36)),
        'utf8-72'=>str_repeat('ñ', 24).bin2hex(random_bytes(12)),
    ];
    foreach ($passwords as $scenario=>$password) {
        $issued = httpCall($admin, '/admin/users/'.$id.'/reset', 'POST', []);
        $token = $issued['token'];
        $resetClient = [];
        httpCall($resetClient, '/admin/session');
        if ($scenario === 'reset-spaces') {
            $invalids = [null, false, true, 123456789012, 1.25, [], new stdClass(), '', str_repeat('x',11), str_repeat('x',73), str_repeat('é',37), bin2hex(random_bytes(8))."\0"];
            foreach ($invalids as $value) {
                $before = snapshot($id);
                httpCall($resetClient, '/admin/reset-password', 'POST', ['token'=>$token,'password'=>$value], 422);
                check(snapshot($id) === $before, 'Invalid password changed hash, session version, token or audit');
            }
            $before = snapshot($id);
            httpCall($resetClient, '/admin/reset-password', 'POST', ['token'=>$token], 422);
            check(snapshot($id) === $before, 'Missing password changed account or token');
            httpCall($previousSession, '/admin/settings');
        }
        $before = snapshot($id);
        httpCall($resetClient, '/admin/reset-password', 'POST', ['token'=>bin2hex(random_bytes(32)),'password'=>$password], 422);
        check(snapshot($id) === $before, 'Invalid token changed state');
        httpCall($resetClient, '/admin/reset-password', 'POST', ['token'=>$token,'password'=>$password]);
        $after = snapshot($id);
        check((int)$after['user']['session_version'] === (int)$before['user']['session_version']+1, 'Session version did not advance once');
        check($after['tokens'] === [], 'Successful reset did not consume tokens');
        check(password_verify($password, $after['user']['password_hash']), 'Stored hash does not match exact password');
        check(password_get_info($after['user']['password_hash'])['algoName'] === 'bcrypt', 'Expected bcrypt hash');
        if ($previousSession !== null) httpCall($previousSession, '/admin/settings', 'GET', null, 401);
        $exact = [];
        login($exact, $email, $password);
        if (trim($password) !== $password) {
            $trimmed = [];
            login($trimmed, $email, trim($password), 401);
        }
        $beforeReuse = snapshot($id);
        httpCall($resetClient, '/admin/reset-password', 'POST', ['token'=>$token,'password'=>$password], 422);
        check(snapshot($id) === $beforeReuse, 'Reused token changed state');
        $previousSession = $exact;
    }
    $issued = httpCall($admin, '/admin/users/'.$id.'/reset', 'POST', []);
    $q = db()->prepare('UPDATE password_resets SET expires_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 MINUTE) WHERE token_hash=? AND user_id=?');
    $q->execute([hash('sha256',$issued['token']),$id]);
    $before = snapshot($id);
    httpCall($resetClient, '/admin/reset-password', 'POST', ['token'=>$issued['token'],'password'=>bin2hex(random_bytes(12))], 422);
    check(snapshot($id) === $before, 'Expired token changed state');
    httpCall($previousSession, '/admin/settings');
    echo "PASS password regression: $checks checks; exact spaces, UTF-8, byte boundaries, invalid values unchanged, token lifecycle and session revocation (PHP ".PHP_VERSION.").\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL password regression: '.$e->getMessage()."\n");
    exit(1);
}
