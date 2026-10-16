<?php
declare(strict_types=1);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/editorial.php';
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'");
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
try {
    if ($path === '/api/health' && $method === 'GET') respond(['status'=>'ok','database'=>'not_checked']);
    if ($path === '/api/public/settings' && $method === 'GET') respond(settings());
    if (str_starts_with($path, '/api/public/')) { editorialRoutes($path,$method); respond(['error'=>'not_found'],404); }
    if (!str_starts_with($path, '/api/admin/')) respond(['error'=>'not_found'], 404);
    beginSession();
    if ($path === '/api/admin/session' && $method === 'GET') respond(['user'=>currentUser(),'csrf'=>$_SESSION['csrf']]);
    if ($path === '/api/admin/login' && $method === 'POST') {
        csrf(); $data = body();
        $email = is_string($data['email'] ?? null) ? strtolower(trim($data['email'])) : '';
        $password = $data['password'] ?? '';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email)>190 || !is_string($password) || strlen($password)>72) respond(['error'=>'Credenciales inválidas.'], 401);
        // Serialize per-IP attempts to prevent concurrent requests bypassing the limit.
        $db = db(); $key = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown'); $now = time();
        $db->beginTransaction();
        $q = $db->prepare('INSERT IGNORE INTO login_limits (ip_key, attempts, window_start) VALUES (?, 0, ?)'); $q->execute([$key,$now]);
        $q = $db->prepare('SELECT attempts, window_start FROM login_limits WHERE ip_key = ? FOR UPDATE'); $q->execute([$key]); $limit=$q->fetch();
        $attempts = $now-(int)$limit['window_start'] >= 900 ? 0 : (int)$limit['attempts'];
        if ($attempts >= 10) { $db->commit(); header('Retry-After: '.max(1,900-($now-(int)$limit['window_start']))); respond(['error'=>'Demasiados intentos. Espere hasta 15 minutos.'],429); }
        $start = $attempts===0 ? $now : (int)$limit['window_start'];
        $q=$db->prepare('UPDATE login_limits SET attempts=?, window_start=? WHERE ip_key=?');$q->execute([$attempts+1,$start,$key]);$db->commit();
        $q=$db->prepare('SELECT * FROM cms_users WHERE email = ? AND active = 1');$q->execute([$email]);$u=$q->fetch();
        // A valid dummy bcrypt hash keeps nonexistent-user password checks comparable.
        $hash=$u ? $u['password_hash'] : '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
        $valid=password_verify($password,$hash);
        if (!$u || !$valid) respond(['error'=>'Credenciales inválidas.'],401);
        session_regenerate_id(true); $_SESSION=['user_id'=>(int)$u['id'],'session_version'=>(int)$u['session_version'],'created_at'=>$now,'last_seen'=>$now,'csrf'=>bin2hex(random_bytes(32))];
        respond(['user'=>currentUser(),'csrf'=>$_SESSION['csrf']]);
    }
    if ($path === '/api/admin/logout' && $method === 'POST') {
        csrf(); $_SESSION=[]; session_destroy();
        $params=session_get_cookie_params(); setcookie(session_name(), '', ['expires'=>time()-3600,'path'=>$params['path'],'secure'=>$params['secure'],'httponly'=>true,'samesite'=>'Strict']);
        respond(['ok'=>true]);
    }
    if ($path === '/api/admin/settings' && in_array($method,['GET','PUT'],true)) {
        $user=requireEditor();
        if ($method==='GET') respond(settings());
        csrf(); $data=body();
        $limits=['brandName'=>120,'heroTitle'=>100,'heroAccent'=>100,'heroDescription'=>500,'studyTitle'=>160,'studyLead'=>500,'studyDescription'=>1500,'contactEmail'=>190,'contactPhone'=>40,'whatsapp'=>15,'address'=>300,'schedule'=>300];
        $content=$data['content'] ?? null; $version=$data['version'] ?? null;
        if (!is_array($content) || !is_int($version) || $version<1 || array_diff(array_keys($content),array_merge(array_keys($limits),['heroImageId','linkedin','facebook','instagram'])) || array_diff(array_keys($limits),array_keys($content))) respond(['error'=>'Campos inválidos.'],422);
        $clean=[];
        foreach ($limits as $field=>$max) {
            if (!is_string($content[$field])) respond(['error'=>'Campo inválido: '.$field],422);
            $value=trim($content[$field]);
            if (preg_match('//u',$value)!==1 || preg_match_all('/./us',$value)>$max || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/',$value)) respond(['error'=>'Texto inválido o demasiado largo: '.$field],422);
            if (in_array($field,['brandName','heroTitle','heroAccent','heroDescription','studyTitle','studyLead','studyDescription'],true) && $value==='') respond(['error'=>'Complete los textos principales.'],422);
            $clean[$field]=$value;
        }
        if ($clean['contactEmail']!=='' && !filter_var($clean['contactEmail'],FILTER_VALIDATE_EMAIL)) respond(['error'=>'Correo inválido.'],422);
        if ($clean['contactPhone']!=='' && !preg_match('/^[+0-9 ()-]{5,40}$/D',$clean['contactPhone'])) respond(['error'=>'Teléfono inválido.'],422);
        if ($clean['whatsapp']!=='' && !preg_match('/^[1-9][0-9]{7,14}$/D',$clean['whatsapp'])) respond(['error'=>'WhatsApp debe incluir código de país y solo dígitos.'],422);
        $clean['heroImageId']=$content['heroImageId']??null;
        if($clean['heroImageId']!==null){
            if(!is_int($clean['heroImageId']))respond(['error'=>'Imagen inválida.'],422);
        }
        foreach(['linkedin'=>'www.linkedin.com','facebook'=>'www.facebook.com','instagram'=>'www.instagram.com'] as $network=>$host){
            $url=textField($content[$network]??'',500);
            if($url!=='' && (!filter_var($url,FILTER_VALIDATE_URL)||parse_url($url,PHP_URL_SCHEME)!=='https'||!in_array(parse_url($url,PHP_URL_HOST),[$host,substr($host,4)],true)||parse_url($url,PHP_URL_USER)||parse_url($url,PHP_URL_PORT)))respond(['error'=>'Enlace de red social inválido.'],422);
            $clean[$network]=$url;
        }
        $db=db(); $db->beginTransaction();
        if($clean['heroImageId']!==null){$q=$db->prepare("SELECT id FROM media_files WHERE id=? AND private_case_id IS NULL AND mime LIKE 'image/%' FOR UPDATE");$q->execute([$clean['heroImageId']]);if(!$q->fetch())respond(['error'=>'Seleccione una imagen pública.'],422);}
        $q=$db->prepare('UPDATE site_settings SET content=?,version=version+1,updated_by=? WHERE id=1 AND version=?');$q->execute([json_encode($clean,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),$user['id'],$version]);
        if ($q->rowCount()!==1) { $db->rollBack();respond(['error'=>'Otra persona modificó el contenido. Recargue antes de guardar.'],409); }
        $q=$db->prepare('INSERT INTO cms_audit (user_id,action,settings_version) VALUES (?, ?, ?)');$q->execute([$user['id'],'settings.published',$version+1]);
        $db->commit(); respond(['content'=>$clean,'version'=>$version+1]);
    }
    editorialRoutes($path,$method);
    respond(['error'=>'not_found'],404);
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log('CarlosBurgos API: '.$e->getMessage());
    respond(['error'=>'Servicio no disponible. Revise la configuración del servidor.'],503);
}
