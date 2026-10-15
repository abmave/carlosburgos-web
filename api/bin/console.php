<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/src/bootstrap.php';
try {
    $command=$argv[1] ?? '';
    if ($command==='migrate') {
        $db=db();
        foreach (glob(dirname(__DIR__,2).'/database/*.sql') as $file) {
            $version=pathinfo($file,PATHINFO_FILENAME);
            if ($version!=='001_foundation') { $q=$db->prepare('SELECT COUNT(*) FROM schema_migrations WHERE version=?');$q->execute([$version]);if($q->fetchColumn()) continue; }
            $db->exec(file_get_contents($file));
            echo 'Applied '.$version.PHP_EOL;
        }
    } elseif ($command==='create-admin') {
        $email=strtolower(trim($argv[2] ?? '')); $name=trim($argv[3] ?? 'Administrador');
        if (!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>190 || $name==='' || strlen($name)>120) throw new RuntimeException('Use create-admin email "Nombre"');
        // Password supplied through standard input, never as an argument or committed seed.
        fwrite(STDERR,"Contraseña por entrada estándar (mínimo 12 caracteres):\n");
        $password=rtrim(fgets(STDIN,1026) ?: '',"\r\n");
        if (strlen($password)<12 || strlen($password)>72) throw new RuntimeException('Password must contain 12–72 bytes');
        $q=db()->prepare('INSERT INTO cms_users (email,name,password_hash,role) VALUES (?,?,?,?)');
        $q->execute([$email,$name,password_hash($password,PASSWORD_BCRYPT,['cost'=>12]),'admin']);
        echo "Administrador creado.\n";
    } else throw new RuntimeException('Commands: migrate | create-admin email "Nombre" (password via stdin)');
} catch (Throwable $e) { fwrite(STDERR,'Error: '.$e->getMessage().PHP_EOL); exit(1); }
