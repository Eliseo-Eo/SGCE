<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function RegistrarBitacora($Pdo, $UserSession, $Accion, $TablaAfectada = null, $RegistroId = null, $Detalle = null) {
    try {
        
        $BusquedaTexto = SgceTextoBusquedaNormalizado(trim((string)$Accion . ' ' . (string)$TablaAfectada . ' ' . (string)$RegistroId . ' ' . (string)$Detalle . ' ' . (is_array($UserSession) ? (string)($UserSession['NombreCompleto'] ?? '') : '')));
        $Stmt = $Pdo->prepare('INSERT INTO BitacoraMovimientos (UsuarioId, Rol, Accion, TablaAfectada, RegistroId, Detalle, BusquedaTexto, Ip) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $Stmt->execute([
            is_array($UserSession) && isset($UserSession['Id']) ? $UserSession['Id'] : null,
            is_array($UserSession) && isset($UserSession['Rol']) ? $UserSession['Rol'] : null,
            (string)$Accion,
            $TablaAfectada,
            $RegistroId,
            $Detalle,
            $BusquedaTexto,
            ObtenerIpCliente(),
        ]);
    } catch (Exception $E) {}
}

