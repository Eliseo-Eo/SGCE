<?php if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); } ?>
<!DOCTYPE html><html lang="es"><head>
<?= SgceLayoutHeadBase('SGCE | Pase De Lista', $Pdo, ['assets/css/asistencia-botones-metalicos.css']) ?>
</head><body>
<div class="container py-3 SgcePage SgceModuleWrap">
<?php require __DIR__ . '/partials/Header.php'; ?>
<?php require __DIR__ . '/partials/FiltroFecha.php'; ?>
<?= $Mensaje ?>
<?php require __DIR__ . '/partials/AvisoExistente.php'; ?>
<?php require __DIR__ . '/partials/Resumen.php'; ?>
<?php require __DIR__ . '/partials/Formulario.php'; ?>
</div>
<?= SgceLayoutSharedJs(['assets/js/Asistencia.js'], true, true) ?>
</body></html>
