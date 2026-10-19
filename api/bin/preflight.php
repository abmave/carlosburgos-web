<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/src/bootstrap.php';
$checks=['php_82'=>PHP_MAJOR_VERSION===8 && PHP_MINOR_VERSION===2];
foreach(['pdo_mysql','session','json','openssl','fileinfo','gd'] as $ext)$checks[$ext]=extension_loaded($ext);
$gd=function_exists('gd_info')?gd_info():[];
foreach(['JPEG Support','PNG Support','WebP Support'] as $key)$checks[$key]=(bool)($gd[$key]??false);
$checks['private_storage_writable']=is_dir(storagePath())&&is_writable(storagePath());
try{publicOrigin();$checks['origin']=true;}catch(Throwable $e){$checks['origin']=false;}
try{$checks['database']=db()->query('SELECT 1')->fetchColumn()==1;}catch(Throwable $e){$checks['database']=false;}
$pdf=false;
if(function_exists('exec')){exec('qpdf --version 2>/dev/null',$output,$status);$pdf=$status===0;}
$checks['pdf_policy']=!pdfUploadsEnabled()||$pdf;
echo json_encode(['checks'=>$checks,'pdf_enabled'=>pdfUploadsEnabled(),'qpdf_available'=>$pdf],JSON_PRETTY_PRINT).PHP_EOL;
exit(in_array(false,$checks,true)?1:0);
