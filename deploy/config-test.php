<?php
require '/app/api/src/bootstrap.php';
function check(bool $ok):void{if(!$ok)throw new RuntimeException('Deployment configuration regression');}
check(storagePath()==='/storage');
foreach(['http://carlosburgosh.com','https://carlosburgosh.com/path','https://user:pass@carlosburgosh.com','https://carlosburgosh.com#x','https://carlosburgosh.com?x',''] as $value){
 putenv('CB_PUBLIC_ORIGIN='.$value);$failed=false;try{publicOrigin();}catch(RuntimeException $e){$failed=true;}check($failed);
}
putenv('CB_PUBLIC_ORIGIN=https://carlosburgosh.com');check(publicOrigin()==='https://carlosburgosh.com');
putenv('CB_PDF_UPLOADS=0');check(!pdfUploadsEnabled());
putenv('CB_PDF_UPLOADS=1');check(pdfUploadsEnabled());
putenv('CB_PDF_UPLOADS=true');check(!pdfUploadsEnabled());
$html=file_get_contents('/storage/admin-activation.html');
check(str_contains($html,'https://carlosburgosh.com/admin/reset#'));
check(!str_contains($html,'localhost'));
echo "PASS deployment configuration and production activation origin; secrets not printed.\n";
