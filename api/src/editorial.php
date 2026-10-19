<?php
declare(strict_types=1);

const CONTENT_FIELDS = [
 'specialties'=>['summary','description','faqs'],
 'team'=>['position','biography','education','registration'],
 'articles'=>['summary','body','category','author','date'],
 'multimedia'=>['description','date','url','format'],
 'cases'=>['context','strategy','result','process_status','url'],
];
function adminOnly(): array {
 $u=requireEditor(); if($u['role']!=='admin') respond(['error'=>'Requiere administrador.'],403); return $u;
}
function auditAction(array $u,string $action,int $version=1,?int $entityId=null,?array $metadata=null): void {
 $q=db()->prepare('INSERT INTO cms_audit(user_id,action,settings_version,entity_id,metadata) VALUES(?,?,?,?,?)');$q->execute([$u['id'],$action,$version,$entityId,$metadata===null?null:json_encode($metadata,JSON_THROW_ON_ERROR)]);
}
function textField(mixed $v,int $max=10000): string {
 if(!is_string($v)||strlen($v)>$max||preg_match('//u',$v)!==1||preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f]/',$v)) respond(['error'=>'Texto inválido o demasiado largo.'],422);
 return trim($v);
}
function videoUrl(string $url): string {
 if($url==='') return '';
 if(!preg_match('~^https://(?:www\.)?(?:youtube\.com/watch\?v=[A-Za-z0-9_-]{11}|youtu\.be/[A-Za-z0-9_-]{11}|vimeo\.com/[0-9]{1,12})$~D',$url)) respond(['error'=>'Use un enlace HTTPS de YouTube o Vimeo sin parámetros adicionales.'],422);
 return $url;
}
function editorialData(string $kind,array $data): array {
 $clean=[];
 foreach(CONTENT_FIELDS[$kind] as $field) $clean[$field]=textField($data[$field]??'', $field==='body'?50000:10000);
 if(isset($clean['url'])) $clean['url']=videoUrl($clean['url']);
 if(isset($clean['date']) && $clean['date']!=='' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$clean['date']) || date('Y-m-d',strtotime($clean['date']))!==$clean['date'])) respond(['error'=>'Fecha inválida.'],422);
 if($kind==='multimedia'&&!in_array($clean['format'],['video','interview','gallery'],true)) respond(['error'=>'Seleccione video, entrevista o galería.'],422);
 $clean['image_id']=$data['image_id']??null;
 if($clean['image_id']!==null){
  if(!is_int($clean['image_id'])) respond(['error'=>'Imagen inválida.'],422);
  $q=db()->prepare("SELECT id FROM media_files WHERE id=? AND private_case_id IS NULL AND mime LIKE 'image/%'");$q->execute([$clean['image_id']]);if(!$q->fetch()) respond(['error'=>'Imagen no disponible.'],422);
 }
 foreach(['specialty_ids'=>'specialties','team_ids'=>'team'] as $field=>$target){
  $ids=$data[$field]??[];if(!is_array($ids)||!array_is_list($ids)||count($ids)>50) respond(['error'=>'Relaciones inválidas.'],422);
  foreach($ids as $id){if(!is_int($id)) respond(['error'=>'Relación inválida.'],422);$q=db()->prepare('SELECT id FROM editorial_content WHERE id=? AND kind=?');$q->execute([$id,$target]);if(!$q->fetch()) respond(['error'=>'Relación inexistente.'],422);}
  $clean[$field]=array_values(array_unique($ids));
 }
 $gallery=$data['gallery_ids']??[];
 if(!is_array($gallery)||!array_is_list($gallery)||count($gallery)>30)respond(['error'=>'Galería inválida (máximo 30 imágenes).'],422);
 foreach($gallery as $image){if(!is_int($image))respond(['error'=>'Imagen inválida.'],422);$q=db()->prepare("SELECT id FROM media_files WHERE id=? AND private_case_id IS NULL AND mime LIKE 'image/%'");$q->execute([$image]);if(!$q->fetch())respond(['error'=>'Imagen de galería no disponible.'],422);}
 $clean['gallery_ids']=array_values(array_unique($gallery));
 return $clean;
}
function contentRow(array $r,bool $public=false): array {
 $out=['id'=>(int)$r['id'],'kind'=>$r['kind'],'slug'=>$r['slug'],'title'=>$r['title'],'content'=>json_decode($r['content'],true),'status'=>$r['status'],'order'=>(int)$r['sort_order'],'version'=>(int)$r['version']];
 if($public){
  foreach(['specialty_ids'=>'specialties','team_ids'=>'team'] as $field=>$kind){$out['content'][$field]=array_values(array_filter($out['content'][$field]??[],function($id)use($kind){$q=db()->prepare("SELECT id FROM editorial_content WHERE id=? AND kind=? AND status='published'");$q->execute([$id,$kind]);return (bool)$q->fetch();}));}
  $out['relations']=[];
  foreach(['specialty_ids'=>'specialties','team_ids'=>'team'] as $field=>$kind)foreach($out['content'][$field]??[] as $id){$q=db()->prepare("SELECT title,slug FROM editorial_content WHERE id=? AND kind=? AND status='published'");$q->execute([$id,$kind]);if($relation=$q->fetch())$out['relations'][]=['kind'=>$kind,...$relation];}
 } else {$out['approved_at']=$r['approved_at'];$out['approved_by']=$r['approved_by'];if((currentUser()['role']??'')==='admin')$out['approval']=$r['approval']?json_decode($r['approval'],true):null;}
 return $out;
}
function editorialRoutes(string $path,string $method): void {
 if(preg_match('~^/api/(public|admin)/content/([a-z]+)(?:/([^/]+))?$~D',$path,$m)){
  $public=$m[1]==='public';$kind=$m[2];$key=$m[3]??null;
  if(!isset(CONTENT_FIELDS[$kind]))respond(['error'=>'No encontrado.'],404);
  $u=$public?null:requireEditor();
  if($method==='GET'){
   $where='kind=?';$args=[$kind];if($public){$where.=" AND status='published'";}
   if($key!==null){$where.=$public?' AND slug=?':' AND id=?';$args[]=$key;}
   if(!$public && isset($_GET['status']) && $_GET['status']!==''){$where.=' AND status=?';$args[]=textField($_GET['status'],30);}
   if(isset($_GET['q'])){$where.=' AND title LIKE ?';$args[]='%'.textField($_GET['q'],200).'%';}
   $page=max(1,(int)($_GET['page']??1));$offset=($page-1)*20;
   $q=db()->prepare("SELECT * FROM editorial_content WHERE $where ORDER BY sort_order,id DESC LIMIT 20 OFFSET $offset");$q->execute($args);$rows=$q->fetchAll();
   if($key!==null){if(!$rows)respond(['error'=>'No encontrado.'],404);respond(contentRow($rows[0],$public));}
   $q=db()->prepare("SELECT COUNT(*) FROM editorial_content WHERE $where");$q->execute($args);
   respond(['items'=>array_map(fn($r)=>contentRow($r,$public),$rows),'total'=>(int)$q->fetchColumn(),'page'=>$page]);
  }
  if($public)respond(['error'=>'Método no permitido.'],405);
  csrf();$data=body();$db=db();$db->beginTransaction();
  // Shared lock order with deletion prevents newly saved references racing deletion.
  $db->query('SELECT id FROM media_files ORDER BY id FOR UPDATE')->fetchAll();
  $row=null;if($key!==null){$q=$db->prepare('SELECT * FROM editorial_content WHERE id=? AND kind=? FOR UPDATE');$q->execute([$key,$kind]);$row=$q->fetch();if(!$row)respond(['error'=>'No encontrado.'],404);if(($data['version']??null)!==(int)$row['version'])respond(['error'=>'Hay otra versión guardada. Recargue antes de editar.'],409);}
  if($method==='DELETE'){
   if(!$row)respond(['error'=>'No encontrado.'],404);
   $q=$db->prepare("UPDATE editorial_content SET status='withdrawn',approved_by=NULL,approved_at=NULL,approval=NULL,version=version+1,updated_by=? WHERE id=?");$q->execute([$u['id'],$key]);auditAction($u,$kind.'.withdrawn',(int)$row['version']+1,(int)$key);$db->commit();respond(['ok'=>true]);
  }
  if(!in_array($method,['POST','PUT'],true)||($method==='PUT'&&!$row)||($method==='POST'&&$row))respond(['error'=>'Método no permitido.'],405);
  $title=textField($data['title']??'',200);$slug=textField($data['slug']??'',160);
  if($title===''||!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$slug))respond(['error'=>'Título y slug válido son obligatorios.'],422);
  if(!is_array($data['content']??[]))respond(['error'=>'Contenido inválido.'],422);
  $content=editorialData($kind,$data['content']??[]);$json=json_encode($content,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
  $status=$data['status']??'draft';$order=$data['order']??0;
  if(!is_int($order)||abs($order)>100000||!in_array($status,['draft','review','approved','published','withdrawn'],true))respond(['error'=>'Estado u orden inválido.'],422);
  if($kind!=='cases'&&in_array($status,['review','approved'],true))respond(['error'=>'Estado exclusivo de casos.'],422);
  $approvedBy=$row['approved_by']??null;$approvedAt=$row['approved_at']??null;$approval=$row['approval']??null;
  if($kind==='cases'){
   $changed=$row && ($row['title']!==$title||$row['slug']!==$slug||$row['content']!==$json||(int)$row['sort_order']!==$order);
   if(in_array($status,['approved','published'],true)&&$u['role']!=='admin')respond(['error'=>'Solo un administrador aprueba o publica casos.'],403);
   if($changed && in_array($row['status'],['approved','published'],true)){$status='review';$approvedBy=null;$approvedAt=null;$approval=null;}
   elseif($status==='approved'){
    if(!$row || $row['status']!=='review')respond(['error'=>'Guarde y envíe primero el caso a revisión.'],422);
    $a=$data['approval']??[];$level=textField($a['anonymization']??'',200);$basis=textField($a['basis']??'',2000);
    if($level===''||$basis==='')respond(['error'=>'Indique anonimización y fundamento de autorización.'],422);
    $evidence=$a['evidence_id']??null;
    if(($a['consent_required']??false)===true){$q=$db->prepare('SELECT id FROM media_files WHERE id=? AND private_case_id=?');$q->execute([$evidence,$key]);if(!$q->fetch())respond(['error'=>'Adjunte evidencia privada de consentimiento.'],422);}
    $approvedBy=$u['id'];$approvedAt=gmdate('Y-m-d H:i:s');$approval=json_encode(['anonymization'=>$level,'basis'=>$basis,'evidence_id'=>$evidence,'consent_required'=>($a['consent_required']??false)===true]);
   } elseif($status==='published' && (!$row||$changed||!$approvedBy||!in_array($row['status'],['approved','published'],true)))respond(['error'=>'El caso requiere aprobación vigente.'],422);
   if(in_array($status,['draft','review','withdrawn'],true)){$approvedBy=null;$approvedAt=null;$approval=null;}
  }
  $version=$row?(int)$row['version']+1:1;
  try{
   if($row){$q=$db->prepare('UPDATE editorial_content SET title=?,slug=?,content=?,status=?,sort_order=?,version=?,approved_by=?,approved_at=?,approval=?,updated_by=? WHERE id=?');$q->execute([$title,$slug,$json,$status,$order,$version,$approvedBy,$approvedAt,$approval,$u['id'],$key]);}
   else{$q=$db->prepare('INSERT INTO editorial_content(kind,title,slug,content,status,sort_order,updated_by) VALUES(?,?,?,?,?,?,?)');$q->execute([$kind,$title,$slug,$json,$status,$order,$u['id']]);$key=$db->lastInsertId();}
  }catch(PDOException $e){if($e->getCode()==='23000')respond(['error'=>'El slug ya existe.'],409);throw $e;}
  auditAction($u,$kind.'.'.$status,$version,(int)$key,['title'=>$title,'slug'=>$slug,'content'=>$content,'approval'=>$approval?json_decode($approval,true):null]);$db->commit();$q=$db->prepare('SELECT * FROM editorial_content WHERE id=?');$q->execute([$key]);respond(contentRow($q->fetch()),$row?200:201);
 }
 mediaRoutes($path,$method);userRoutes($path,$method);
}

function mediaRoutes(string $path,string $method): void {
 if(preg_match('~^/api/public/media/([0-9]+)/metadata$~D',$path,$m)&&$method==='GET'){$q=db()->prepare('SELECT title,alt,mime FROM media_files WHERE id=? AND private_case_id IS NULL');$q->execute([$m[1]]);$r=$q->fetch();if(!$r)respond(['error'=>'No encontrado.'],404);respond($r);}
 if(preg_match('~^/api/(public/media|admin/evidence)/([0-9]+)$~D',$path,$m)&&$method==='GET'){
  $private=$m[1]==='admin/evidence';if($private)adminOnly();
  $q=db()->prepare('SELECT * FROM media_files WHERE id=? AND private_case_id IS '.($private?'NOT NULL':'NULL'));$q->execute([$m[2]]);$r=$q->fetch();if(!$r)respond(['error'=>'No encontrado.'],404);
  $file=storagePath().'/'.($private?'private':'media').'/'.$r['filename'];if(!is_file($file))respond(['error'=>'No encontrado.'],404);
  header('Content-Type: '.$r['mime']);header('Content-Length: '.$r['size']);header('Content-Disposition: '.($r['mime']==='application/pdf'?'attachment':'inline').'; filename="'.$r['filename'].'"');readfile($file);exit;
 }
 if(!preg_match('~^/api/admin/(media|evidence)(?:/([0-9]+))?$~D',$path,$m))return;
 $private=$m[1]==='evidence';$u=$private?adminOnly():requireEditor();$id=$m[2]??null;$db=db();
 if($method==='GET'){
  $page=max(1,(int)($_GET['page']??1));$offset=($page-1)*20;$query='%'.textField($_GET['q']??'',200).'%';
  $where='private_case_id IS '.($private?'NOT NULL':'NULL').' AND title LIKE ?';$args=[$query];
  if($private){$where.=' AND private_case_id=?';$args[]=(int)($_GET['case_id']??0);}
  $q=$db->prepare("SELECT id,mime,size,title,alt,version FROM media_files WHERE $where ORDER BY id DESC LIMIT 20 OFFSET $offset");$q->execute($args);$items=$q->fetchAll();
  $q=$db->prepare("SELECT COUNT(*) FROM media_files WHERE $where");$q->execute($args);respond(['items'=>$items,'total'=>(int)$q->fetchColumn(),'page'=>$page]);
 }
 csrf();
 if($method==='POST'&&!$id){
  if((int)($_SERVER['CONTENT_LENGTH']??0)>9*1024*1024)respond(['error'=>'Máximo 8 MiB por archivo.'],413);
  $f=$_FILES['file']??null;if(!$f||$f['error']!==UPLOAD_ERR_OK)respond(['error'=>'Carga inválida. Máximo 8 MiB.'],422);
  if($f['size']>8*1024*1024)respond(['error'=>'Máximo 8 MiB por archivo.'],413);
  $ext=strtolower(pathinfo($f['name'],PATHINFO_EXTENSION));$mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);$types=['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp','pdf'=>'application/pdf'];
  if(!isset($types[$ext])||$types[$ext]!==$mime||preg_match('~[/\\\\]~',$f['name']))respond(['error'=>'Tipo o nombre de archivo inválido.'],422);
  if($mime==='application/pdf'){
   if(!pdfUploadsEnabled() || !function_exists('exec'))respond(['error'=>'La carga PDF está deshabilitada en este servidor. Utilice JPEG, PNG o WebP.'],422);
   // qpdf parses the complete object/xref structure; warnings also reject damaged PDFs.
   exec('qpdf --check '.escapeshellarg($f['tmp_name']).' 2>&1',$output,$exit);if($exit!==0)respond(['error'=>'PDF corrupto.'],422);
   // Never serve PDF inline; active PDF content cannot become a same-origin document.
  }else{
   $info=@getimagesize($f['tmp_name']);if(!$info||$info[0]*$info[1]>40000000)respond(['error'=>'Imagen corrupta o dimensiones excesivas.'],422);
   $im=@imagecreatefromstring(file_get_contents($f['tmp_name']));if(!$im)respond(['error'=>'Imagen corrupta.'],422);
   // Re-encode to discard appended executable payloads and metadata.
   if($mime==='image/jpeg')imagejpeg($im,$f['tmp_name'],90);elseif($mime==='image/png')imagepng($im,$f['tmp_name']);else imagewebp($im,$f['tmp_name'],90);imagedestroy($im);
  }
  $case=null;if($private){$case=(int)($_POST['case_id']??0);$q=$db->prepare("SELECT id FROM editorial_content WHERE id=? AND kind='cases'");$q->execute([$case]);if(!$q->fetch())respond(['error'=>'Caso inexistente.'],422);}
  $title=textField($_POST['title']??$f['name'],200);$alt=textField($_POST['alt']??'',300);$name=bin2hex(random_bytes(24)).'.'.$ext;$dir=storagePath().'/'.($private?'private':'media');if(!is_dir($dir))mkdir($dir,0700,true);
  if(!move_uploaded_file($f['tmp_name'],$dir.'/'.$name))throw new RuntimeException('Upload unavailable');
  $db->beginTransaction();$q=$db->prepare('INSERT INTO media_files(filename,mime,size,title,alt,private_case_id,created_by) VALUES(?,?,?,?,?,?,?)');$q->execute([$name,$mime,filesize($dir.'/'.$name),$title,$alt,$case,$u['id']]);$id=(int)$db->lastInsertId();auditAction($u,$private?'evidence.uploaded':'media.uploaded');$db->commit();respond(['id'=>$id,'title'=>$title,'alt'=>$alt,'mime'=>$mime,'version'=>1],201);
 }
 if(!$id||!in_array($method,['PUT','DELETE'],true))respond(['error'=>'Método no permitido.'],405);
 $data=body();$db->beginTransaction();$q=$db->prepare('SELECT * FROM media_files WHERE id=? AND private_case_id IS '.($private?'NOT NULL':'NULL').' FOR UPDATE');$q->execute([$id]);$r=$q->fetch();if(!$r)respond(['error'=>'No encontrado.'],404);
 if(($data['version']??null)!==(int)$r['version'])respond(['error'=>'Versión modificada. Recargue.'],409);
 if($method==='DELETE'){
  // Preserve all references, including drafts and settings. Evidence is immutable.
  if($private)respond(['error'=>'La evidencia se conserva para auditoría.'],409);
  $q=$db->prepare("SELECT id FROM editorial_content WHERE (JSON_EXTRACT(content,'$.image_id')=? OR JSON_CONTAINS(JSON_EXTRACT(content,'$.gallery_ids'),?)) LIMIT 1");$q->execute([(int)$id,(string)(int)$id]);$s=settings()['content'];
  if($q->fetch()||($s['heroImageId']??null)==$id)respond(['error'=>'Medio utilizado. Retire primero sus referencias.'],409);
  $q=$db->prepare('DELETE FROM media_files WHERE id=?');$q->execute([$id]);auditAction($u,'media.deleted');$db->commit();unlink(storagePath().'/media/'.$r['filename']);respond(['ok'=>true]);
 }
 $q=$db->prepare('UPDATE media_files SET title=?,alt=?,version=version+1 WHERE id=?');$q->execute([textField($data['title']??'',200),textField($data['alt']??'',300),$id]);auditAction($u,'media.updated',(int)$r['version']+1);$db->commit();respond(['ok'=>true,'version'=>(int)$r['version']+1]);
}

function userRoutes(string $path,string $method): void {
 if($path==='/api/admin/reset-password'&&$method==='POST'){
  csrf();$data=body();$token=textField($data['token']??'',100);
  // Passwords are opaque secrets: preserve their bytes, including boundary spaces.
  // Validate before opening the transaction or consuming the one-time token.
  $pw=$data['password']??null;
  if(!is_string($pw)||strlen($pw)<12||strlen($pw)>72||str_contains($pw,"\0")) {
   respond(['error'=>'Contraseña de 12 a 72 bytes, sin caracteres NUL.'],422);
  }
  $db=db();$db->beginTransaction();$q=$db->prepare('SELECT user_id FROM password_resets WHERE token_hash=? AND expires_at>UTC_TIMESTAMP() FOR UPDATE');$q->execute([hash('sha256',$token)]);$id=$q->fetchColumn();if(!$id)respond(['error'=>'Enlace inválido o vencido.'],422);
  $q=$db->prepare('UPDATE cms_users SET password_hash=?,session_version=session_version+1 WHERE id=?');$q->execute([password_hash($pw,PASSWORD_BCRYPT,['cost'=>12]),$id]);$q=$db->prepare('DELETE FROM password_resets WHERE user_id=?');$q->execute([$id]);auditAction(['id'=>$id],'password.reset');$db->commit();respond(['ok'=>true]);
 }
 if(!preg_match('~^/api/admin/users(?:/([0-9]+))?(?:/(reset))?$~D',$path,$m))return;
 $u=adminOnly();$id=$m[1]??null;$db=db();
 if($method==='GET'){$page=max(1,(int)($_GET['page']??1));$offset=($page-1)*20;$q=$db->prepare("SELECT id,name,email,role,active,session_version AS version FROM cms_users WHERE name LIKE ? ORDER BY id LIMIT 20 OFFSET $offset");$q->execute(['%'.textField($_GET['q']??'',120).'%']);respond(['items'=>$q->fetchAll(),'page'=>$page]);}
 csrf();$data=body();$db->beginTransaction();
 // Lock the complete administrator set to prevent concurrent removal of the last admin.
 $users=$db->query('SELECT id,role,active,session_version FROM cms_users ORDER BY id FOR UPDATE')->fetchAll();
 $target=null;foreach($users as $row)if((string)$row['id']===(string)$id)$target=$row;
 if($id&&!$target)respond(['error'=>'Cuenta inexistente.'],404);
 if(isset($m[2])&&$method==='POST'){
  $token=bin2hex(random_bytes(32));$q=$db->prepare('DELETE FROM password_resets WHERE user_id=?');$q->execute([$id]);$q=$db->prepare('INSERT INTO password_resets(token_hash,user_id,expires_at) VALUES(?,?,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 30 MINUTE))');$q->execute([hash('sha256',$token),$id]);auditAction($u,'user.reset-issued');$db->commit();respond(['token'=>$token,'expires_in'=>1800]);
 }
 if(!in_array($method,['POST','PUT'],true))respond(['error'=>'Método no permitido.'],405);
 $name=textField($data['name']??'',120);$email=strtolower(textField($data['email']??'',190));$role=$data['role']??'editor';$active=$data['active']??true;
 if($name===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||!in_array($role,['admin','editor'],true)||!is_bool($active))respond(['error'=>'Datos de cuenta inválidos.'],422);
 if($target){
  if(($data['version']??null)!==(int)$target['session_version'])respond(['error'=>'Cuenta modificada. Recargue.'],409);
  $admins=count(array_filter($users,fn($r)=>$r['role']==='admin'&&(int)$r['active']===1));if($target['role']==='admin'&&(int)$target['active']===1&&(!$active||$role!=='admin')&&$admins<=1)respond(['error'=>'Debe conservar un administrador activo.'],422);
  $q=$db->prepare('UPDATE cms_users SET name=?,email=?,role=?,active=?,session_version=session_version+1 WHERE id=?');$q->execute([$name,$email,$role,(int)$active,$id]);
 }else{
  // Unusable random initial password; administrator hands over an expiring reset link.
  $q=$db->prepare('INSERT INTO cms_users(name,email,role,active,password_hash) VALUES(?,?,?,?,?)');$q->execute([$name,$email,$role,(int)$active,password_hash(bin2hex(random_bytes(32)),PASSWORD_BCRYPT)]);$id=(int)$db->lastInsertId();
 }
 auditAction($u,'user.saved',1,(int)$id,['role'=>$role,'active'=>$active]);$db->commit();respond(['id'=>(int)$id],$target?200:201);
}
