<?php
require 'Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession || $UserSession['Rol'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$Mensajes = [];
try {
    SgceAsegurarCicloPeriodos($Pdo);
    $Mensajes[] = 'Estructura escolar validada correctamente.';

    if (!SgceExisteColumna($Pdo, 'Usuarios', 'SessionTokenExpira')) {
        $Pdo->exec("ALTER TABLE Usuarios ADD COLUMN SessionTokenExpira DATETIME DEFAULT NULL AFTER SessionToken");
        $Mensajes[] = 'Se agregó expiración de sesión a Usuarios.';
    }
    if (!SgceExisteIndice($Pdo, 'Usuarios', 'idx_usuarios_session_expira')) {
        $Pdo->exec("ALTER TABLE Usuarios ADD INDEX idx_usuarios_session_expira (SessionTokenExpira)");
        $Mensajes[] = 'Se agregó índice de expiración de sesión.';
    }

    $Mensajes[] = 'Migración finalizada. En instalación desde cero normalmente no necesitas volver a ejecutar este archivo.';
} catch (Exception $E) {
    http_response_code(500);
    $Mensajes[] = 'Error: ' . $E->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SGCE | Migración</title>
<link rel="icon" href="favicon.ico">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/sgce-base.css?v=final">
</head>
<body>
<div class="container py-4">
    <div class="Top mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div><h2 class="fw-bold"><i class="fa-solid fa-database me-2"></i>MIGRACIÓN DEL SISTEMA</h2><p class="mb-0">Validador técnico para instalaciones existentes.</p></div>
        <a href="Admin.php?Tab=inicio" class="btn btn-outline-light Btn SgceBtnInicio"><i class="fa-solid fa-house me-2"></i> VOLVER A INICIO</a>
    </div>
    <div class="card Card p-4">
        <?php foreach ($Mensajes as $M): ?><div class="alert alert-info mb-2"><?= HGlobal($M) ?></div><?php endforeach; ?>
    </div>
</div>
</body>
</html>
