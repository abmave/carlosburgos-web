<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit;
require dirname(__DIR__).'/src/bootstrap.php';
$email=strtolower(trim($argv[1]??''));$name=trim($argv[2]??'');
if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>190||$name===''||strlen($name)>120)throw new RuntimeException('Use invite-admin.php email name');
$db=db();$db->beginTransaction();
$q=$db->prepare('INSERT INTO cms_users(name,email,role,password_hash) VALUES(?,?,?,?)');
$q->execute([$name,$email,'admin',password_hash(bin2hex(random_bytes(32)),PASSWORD_BCRYPT,['cost'=>12])]);
$id=(int)$db->lastInsertId();$token=bin2hex(random_bytes(32));
$q=$db->prepare('INSERT INTO password_resets(token_hash,user_id,expires_at) VALUES(?,?,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 30 MINUTE))');$q->execute([hash('sha256',$token),$id]);
$dir=getenv('CB_STORAGE_PATH')?:dirname(__DIR__).'/storage';
$html='<!doctype html><html lang="es"><meta charset="utf-8"><title>Activar administrador</title><h1>Activar administrador</h1><p>Enlace privado de un solo uso. Vence en 30 minutos. No comparta este archivo.</p><a href="http://localhost:5180/admin/reset#'.$token.'">Definir mi contraseña</a></html>';
if(file_put_contents($dir.'/admin-activation.html',$html)===false)throw new RuntimeException('Cannot write activation file');
chmod($dir.'/admin-activation.html',0600);$db->commit();echo "Administrador creado. Activación escrita en almacenamiento privado; secreto no impreso.\n";
