<?php
// Install privately as private/api/config.local.php; never inside public/.
return [
 'environment'=>'production',
 'db_host'=>'localhost', 'db_port'=>3306,
 'db_name'=>'', 'db_user'=>'', 'db_password'=>'',
 'storage_path'=>dirname(__DIR__).'/storage',
 'public_origin'=>'https://carlosburgosh.com',
 // Keep disabled until qpdf AND exec are verified on the destination.
 'pdf_uploads'=>false,
];
