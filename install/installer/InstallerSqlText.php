<?php
if (!defined('SGCE_INSTALLER')) { http_response_code(403); exit('Acceso directo no permitido.'); }

function InstalarSepararSql($Sql) {
    $Sentencias = [];
    $Actual = '';
    $Comilla = null;
    $Escape = false;
    $Len = strlen($Sql);
    for ($I = 0; $I < $Len; $I++) {
        $Ch = $Sql[$I];
        $Next = ($I + 1 < $Len) ? $Sql[$I + 1] : '';

        if ($Comilla === null && $Ch === '-' && $Next === '-') {
            while ($I < $Len && $Sql[$I] !== "\n") { $I++; }
            continue;
        }
        if ($Comilla === null && $Ch === '#') {
            while ($I < $Len && $Sql[$I] !== "\n") { $I++; }
            continue;
        }
        if ($Comilla === null && $Ch === '/' && $Next === '*') {
            $I += 2;
            while ($I + 1 < $Len && !($Sql[$I] === '*' && $Sql[$I + 1] === '/')) { $I++; }
            $I++;
            continue;
        }

        if ($Comilla !== null) {
            $Actual .= $Ch;
            if ($Escape) { $Escape = false; continue; }
            if ($Ch === '\\') { $Escape = true; continue; }
            if ($Ch === $Comilla) { $Comilla = null; }
            continue;
        }

        if ($Ch === "'" || $Ch === '"' || $Ch === '`') {
            $Comilla = $Ch;
            $Actual .= $Ch;
            continue;
        }

        if ($Ch === ';') {
            $Stmt = trim($Actual);
            if ($Stmt !== '') { $Sentencias[] = $Stmt; }
            $Actual = '';
            continue;
        }

        $Actual .= $Ch;
    }
    $Stmt = trim($Actual);
    if ($Stmt !== '') { $Sentencias[] = $Stmt; }
    return $Sentencias;
}

function InstalarValidarPassword($Password) {
    $Password = (string)$Password;
    if (strlen($Password) < 8) { return 'La contraseña del administrador debe tener mínimo 8 caracteres.'; }
    if (!preg_match('/[A-ZÁÉÍÓÚÜÑ]/u', $Password)) { return 'La contraseña debe incluir al menos una mayúscula.'; }
    if (!preg_match('/[a-záéíóúüñ]/u', $Password)) { return 'La contraseña debe incluir al menos una minúscula.'; }
    if (!preg_match('/\d/', $Password)) { return 'La contraseña debe incluir al menos un número.'; }
    if (!preg_match('/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ0-9]/u', $Password)) { return 'La contraseña debe incluir al menos un carácter especial.'; }
    return true;
}

function InstalarMayusculas($Texto) {
    $Texto = (string)$Texto;
    if (function_exists('mb_strtoupper')) { return mb_strtoupper($Texto, 'UTF-8'); }
    $Texto = strtr($Texto, [
        'á'=>'Á','é'=>'É','í'=>'Í','ó'=>'Ó','ú'=>'Ú','ü'=>'Ü','ñ'=>'Ñ',
        'à'=>'À','è'=>'È','ì'=>'Ì','ò'=>'Ò','ù'=>'Ù','ä'=>'Ä','ë'=>'Ë','ï'=>'Ï','ö'=>'Ö'
    ]);
    return strtoupper($Texto);
}


function InstalarTextoBusqueda($Texto) {
    $Texto = InstalarMayusculas($Texto);
    $Texto = strtr($Texto, ['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U','Ñ'=>'N']);
    $Texto = preg_replace('/[^A-Z0-9 ]/u', ' ', $Texto);
    return preg_replace('/\s+/u', ' ', trim((string)$Texto));
}

function InstalarLongitud($Texto) {
    $Texto = (string)$Texto;
    return function_exists('mb_strlen') ? mb_strlen($Texto, 'UTF-8') : strlen($Texto);
}


function InstalarResumenSentenciaSql($Sentencia) {
    $Limpia = trim(preg_replace('/\s+/', ' ', (string)$Sentencia));
    if (preg_match('/^CREATE\s+TABLE\s+`?([A-Za-z0-9_]+)`?/i', $Limpia, $M)) { return 'CREATE TABLE ' . $M[1]; }
    if (preg_match('/^ALTER\s+TABLE\s+`?([A-Za-z0-9_]+)`?/i', $Limpia, $M)) { return 'ALTER TABLE ' . $M[1]; }
    if (preg_match('/^INSERT\s+INTO\s+`?([A-Za-z0-9_]+)`?/i', $Limpia, $M)) { return 'INSERT INTO ' . $M[1]; }
    return mb_substr($Limpia, 0, 180, 'UTF-8');
}

function InstalarEjecutarSqlInstalacion(PDO $Pdo, array $Sentencias) {
    foreach ($Sentencias as $Indice => $Sentencia) {
        try {
            $Pdo->exec($Sentencia);
        } catch (Throwable $E) {
            $Resumen = InstalarResumenSentenciaSql($Sentencia);
            throw new InstalarErrorSql('Falló la sentencia #' . ((int)$Indice + 1) . ' [' . $Resumen . ']. Detalle MySQL: ' . $E->getMessage(), 0, $E);
        }
    }
}
