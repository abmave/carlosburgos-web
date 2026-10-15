<?php
require '/app/api/src/bootstrap.php';
if(getenv('CB_TEST_MODE')!=='1')throw new RuntimeException('Test environment required');
if(($argv[1]??'')==='clear-rate'){db()->exec('DELETE FROM login_limits');echo "Test rate limit cleared.\n";}
elseif(($argv[1]??'')==='expire'){
 foreach(glob('/storage/sessions/sess_*') as $file){$s=file_get_contents($file);$s=preg_replace('/last_seen\|i:\d+;/','last_seen|i:1;',$s);file_put_contents($file,$s);}echo "Test sessions expired.\n";
}elseif(($argv[1]??'')==='persist'){
 if(settings()['content']['heroTitle']!=='Persistencia CMS 002')throw new RuntimeException('Settings did not persist');
 if((int)db()->query('SELECT COUNT(*) FROM editorial_content')->fetchColumn()<5)throw new RuntimeException('Content did not persist');
 if((int)db()->query('SELECT COUNT(*) FROM media_files')->fetchColumn()<4)throw new RuntimeException('Media did not persist');
 echo "PASS persisted settings, editorial records and media after down/up.\n";
}
