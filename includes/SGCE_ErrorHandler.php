<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceLogDir(): string {
    if (defined('SGCE_LOG_DIR')) { return SGCE_LOG_DIR; }
    return dirname(__DIR__) . '/storage/logs';
}

function SgcePrepararLogDir(): void {
    $Dir = SgceLogDir();
    if (!is_dir($Dir)) { @mkdir($Dir, 0775, true); }
    $Ht = $Dir . '/.htaccess';
    if (!is_file($Ht)) { @file_put_contents($Ht, "Require all denied\nDeny from all\n"); }
}

function SgceRegistrarErrorTecnico(string $Contexto, $Error = null, array $Datos = []): string {
    SgcePrepararLogDir();
    $Id = date('YmdHis') . '-' . bin2hex(random_bytes(4));
    $Linea = [
        'id' => $Id,
        'fecha' => date('c'),
        'contexto' => $Contexto,
        'uri' => $_SERVER['REQUEST_URI'] ?? 'CLI',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'CLI',
        'usuario' => $_SESSION['UsuarioId'] ?? null,
        'datos' => $Datos,
    ];
    if ($Error instanceof Throwable) {
        $Linea['tipo'] = get_class($Error);
        $Linea['mensaje'] = $Error->getMessage();
        $Linea['archivo'] = $Error->getFile();
        $Linea['linea'] = $Error->getLine();
        $Linea['trace'] = $Error->getTraceAsString();
    } else {
        $Linea['mensaje'] = (string)$Error;
    }
    @file_put_contents(SgceLogDir() . '/sgce-' . date('Y-m-d') . '.log', json_encode($Linea, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);
    return $Id;
}

function SgceMostrarErrorCliente(string $Codigo = ''): void {
    if (!headers_sent()) { http_response_code(500); header('Content-Type: text/html; charset=utf-8'); }
    $CodigoHtml = htmlspecialchars($Codigo, ENT_QUOTES, 'UTF-8');
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>SGCE - Error</title><style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f4f7fb;font-family:Segoe UI,Arial,sans-serif;color:#1f2937}.card{max-width:560px;background:#fff;padding:34px;border-radius:24px;box-shadow:0 22px 55px rgba(15,23,42,.12);border-top:5px solid #97051E}.icon{width:58px;height:58px;border-radius:18px;background:#fff1f2;color:#97051E;display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:900}.btn{display:inline-block;margin-top:18px;padding:12px 18px;border-radius:999px;background:#97051E;color:#fff;text-decoration:none;font-weight:800}.code{margin-top:14px;color:#64748b;font-size:13px}</style></head><body><main class="card"><div class="icon">!</div><h1>Algo no salió como se esperaba</h1><p>El sistema registró el detalle técnico para revisión. Intenta nuevamente o contacta al administrador del sistema.</p>' . ($CodigoHtml ? '<div class="code">Código de seguimiento: ' . $CodigoHtml . '</div>' : '') . '<a class="btn" href="index.php">Volver al inicio</a></main></body></html>';
}

set_error_handler(function($Severity, $Message, $File, $Line) {
    if (!(error_reporting() & $Severity)) { return false; }
    SgceRegistrarErrorTecnico('ERROR_PHP', $Message, ['severity' => $Severity, 'archivo' => $File, 'linea' => $Line]);
    return true;
});

set_exception_handler(function($Throwable) {
    $Id = SgceRegistrarErrorTecnico('EXCEPCION_NO_CONTROLADA', $Throwable);
    if (PHP_SAPI === 'cli') { fwrite(STDERR, "SGCE error {$Id}: " . $Throwable->getMessage() . PHP_EOL); exit(1); }
    SgceMostrarErrorCliente($Id);
    exit;
});

register_shutdown_function(function() {
    $Error = error_get_last();
    if (!$Error) { return; }
    $Fatales = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (!in_array($Error['type'], $Fatales, true)) { return; }
    $Id = SgceRegistrarErrorTecnico('ERROR_FATAL', $Error['message'] ?? 'Error fatal', ['archivo' => $Error['file'] ?? '', 'linea' => $Error['line'] ?? 0]);
    if (PHP_SAPI !== 'cli' && !headers_sent()) {
        SgceMostrarErrorCliente($Id);
    }
});
