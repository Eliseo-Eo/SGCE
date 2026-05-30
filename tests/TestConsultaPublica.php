<?php
if (php_sapi_name() !== 'cli') { http_response_code(403); exit('Uso CLI únicamente.'); }
require __DIR__ . '/RunStaticChecks.php';
