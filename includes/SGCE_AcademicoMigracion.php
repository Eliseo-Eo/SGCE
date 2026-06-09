<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

require_once dirname(__DIR__) . '/services/migracion/MigracionService.php';
