<?php
require '/app/api/src/bootstrap.php';
if(getenv('CB_TEST_MODE')!=='1')throw new RuntimeException('Test environment required');
$id=bin2hex(random_bytes(16));$token=bin2hex(random_bytes(32));
file_put_contents('/storage/sessions/sess_'.$id,'user_id|i:1;session_version|i:1;created_at|i:1;last_seen|i:1;csrf|s:64:"'.str_repeat('a',64).'";');
$q=db()->prepare('INSERT INTO password_resets(token_hash,user_id,expires_at) VALUES(?,1,DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 HOUR))');$q->execute([hash('sha256',$token)]);
file_put_contents('/storage/expired-fixtures.json',json_encode(['cookie'=>'cb_session='.$id,'token'=>$token]));
echo "Expired test fixtures prepared.\n";
