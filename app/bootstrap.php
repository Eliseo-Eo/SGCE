<?php
/**
 * SGCE - Bootstrap mínimo de aplicación.
 *
 * No reemplaza los require_once legacy. Solo prepara rutas base y autoload
 * progresivo para clases nuevas con namespace Sgce\.
 */
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli' && !defined('SGCE_INSTALLER')) {
    http_response_code(403);
    exit('Acceso directo no permitido.');
}

if (!defined('SGCE_APP_ROOT')) { define('SGCE_APP_ROOT', dirname(__DIR__)); }
if (!defined('SGCE_APP_SRC')) { define('SGCE_APP_SRC', SGCE_APP_ROOT . '/src'); }
if (!defined('SGCE_APP_STORAGE')) { define('SGCE_APP_STORAGE', SGCE_APP_ROOT . '/storage'); }

require_once __DIR__ . '/autoload.php';
