<?php
require 'Conexion.php';
$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession || !in_array($UserSession['Rol'], ['admin','director'], true)) { header('Location: index.php'); exit; }
try {
    $Pdo->exec("ALTER TABLE Usuarios MODIFY Rol ENUM('admin','maestro','director','secretario','coordinador','prefecto') NOT NULL DEFAULT 'maestro'");
    SgceAsegurarCicloPeriodos($Pdo);
    RegistrarBitacora($Pdo, $UserSession, 'MIGRACION_FIX31', 'BASE_DE_DATOS', null, 'MIGRACIÓN DE CICLOS Y PERIODOS EJECUTADA');
    echo '<h2 style="font-family:Arial;color:#7A0818">Migración SGCE FIX31 completada.</h2><p>Se aseguraron roles nuevos, CiclosEscolares, PeriodosEvaluacion y Calificaciones.PeriodoId.</p><p><a href="Admin.php">Volver al inicio</a></p>';
} catch (Exception $E) {
    http_response_code(500);
    echo '<h2>Error en migración</h2><pre>'.htmlspecialchars($E->getMessage(), ENT_QUOTES, 'UTF-8').'</pre>';
}
?>
