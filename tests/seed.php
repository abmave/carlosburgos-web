<?php
require '/app/api/src/bootstrap.php';
if(getenv('CB_TEST_MODE')!=='1')throw new RuntimeException('Disposable test environment required');
if(db()->query('SELECT COUNT(*) FROM cms_users')->fetchColumn())throw new RuntimeException('Refusing to replace existing accounts');
$password=bin2hex(random_bytes(24));
foreach(['admin','editor'] as $role){$q=db()->prepare('INSERT INTO cms_users(name,email,role,password_hash) VALUES(?,?,?,?)');$q->execute(['Prueba '.$role,$role.'@example.invalid',$role,password_hash($password,PASSWORD_BCRYPT)]);}
file_put_contents('/storage/test-credentials.json',json_encode(['password'=>$password]));
$image=imagecreatetruecolor(16,16);imagepng($image,'/storage/test.png');imagejpeg($image,'/storage/test.jpg');imagewebp($image,'/storage/test.webp');imagedestroy($image);
require '/tests/pdf-fixture.php';
echo "Disposable fixtures created; secrets not printed.\n";
