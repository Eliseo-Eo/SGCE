<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceAdminNormalizarMatricula($Valor): string {
    $Valor = SgceNormalizarMayusculas((string)$Valor);
    return preg_match('/^[A-Z0-9._\-\/]{1,40}$/', $Valor) ? $Valor : '';
}

function SgceAdminEjecutarTransaccion(PDO $Pdo, callable $Operacion) {
    $Iniciada = !$Pdo->inTransaction();
    if ($Iniciada) { $Pdo->beginTransaction(); }
    try {
        $Resultado = $Operacion();
        if ($Iniciada) { $Pdo->commit(); }
        return $Resultado;
    } catch (Throwable $E) {
        if ($Iniciada && $Pdo->inTransaction()) { $Pdo->rollBack(); }
        throw $E;
    }
}
