<?php

if (!function_exists('SgceNormalizarMayusculas')) {
    function SgceNormalizarMayusculas($Valor) {
        $Valor = trim((string)$Valor);
        if ($Valor === '') { return ''; }
        $Valor = preg_replace('/\s+/u', ' ', $Valor);
        return function_exists('mb_strtoupper') ? mb_strtoupper($Valor, 'UTF-8') : strtoupper($Valor);
    }
}

if (!function_exists('SgceNormalizarNombre')) {
    function SgceNormalizarNombre($Valor) {
        $Valor = SgceNormalizarMayusculas($Valor);
        if ($Valor === '') { return ''; }
        return preg_match('/^[\p{L}\s]+$/u', $Valor) ? $Valor : '';
    }
}

if (!function_exists('SgceNormalizarGrupo')) {
    function SgceNormalizarGrupo($Valor) {
        $Valor = SgceNormalizarMayusculas($Valor);
        return preg_match('/^[A-Z]+$/', $Valor) ? $Valor : '';
    }
}

if (!function_exists('SgceValidarGrado')) {
    function SgceValidarGrado($Valor) {
        $Valor = trim((string)$Valor);
        return $Valor !== '' && ctype_digit($Valor);
    }
}

if (!function_exists('SgceNormalizarTurno')) {
    function SgceNormalizarTurno($Valor) {
        $Valor = SgceNormalizarMayusculas($Valor);
        return in_array($Valor, ['MATUTINO', 'VESPERTINO'], true) ? $Valor : '';
    }
}

if (!function_exists('SgceTabAdminPermitida')) {
    function SgceTabAdminPermitida($Tab) {
        $Permitidas = ['inicio','maestros','grupos','alumnos','expedientes','asignaciones','bitacora'];
        return in_array($Tab, $Permitidas, true) ? $Tab : 'inicio';
    }
}

if (!function_exists('SgceRedirectAdminTab')) {
    function SgceRedirectAdminTab($Tab) {
        header('Location: Admin.php?Tab=' . urlencode(SgceTabAdminPermitida($Tab)));
        exit;
    }
}

/*
    SGCE_Helpers.php
    Helpers comunes para seguridad, paginación real, permisos, ciclo escolar y periodos.
    Las contraseñas siguen guardándose normales por decisión operativa del proyecto.
*/

if (!function_exists('SgceTieneRol')) {
    function SgceTieneRol($UserSession, $Roles) {
        return is_array($UserSession) && isset($UserSession['Rol']) && in_array($UserSession['Rol'], (array)$Roles, true);
    }
}

if (!function_exists('SgcePuedeAdministrarReportes')) {
    function SgcePuedeAdministrarReportes($UserSession) {
        return SgceTieneRol($UserSession, ['admin','director','secretario','coordinador','prefecto']);
    }
}



if (!function_exists('SgceRolesSistema')) {
    function SgceRolesSistema() {
        return [
            'admin' => 'ADMINISTRADOR',
            'maestro' => 'MAESTRO',
            'director' => 'DIRECTOR',
            'secretario' => 'SECRETARIO',
            'coordinador' => 'COORDINADOR',
            'prefecto' => 'PREFECTO'
        ];
    }
}

if (!function_exists('SgcePuedeGestionarUsuarios')) {
    function SgcePuedeGestionarUsuarios($UserSession) {
        return SgceTieneRol($UserSession, ['admin']);
    }
}

if (!function_exists('SgcePuedeGestionarAvisos')) {
    function SgcePuedeGestionarAvisos($UserSession) {
        return SgceTieneRol($UserSession, ['admin','director','secretario','coordinador']);
    }
}

if (!function_exists('SgcePaginaActual')) {
    function SgcePaginaActual($Nombre, $Default = 1) {
        $Valor = isset($_GET[$Nombre]) ? (int)$_GET[$Nombre] : (int)$Default;
        return max(1, $Valor);
    }
}

if (!function_exists('SgceLimitOffset')) {
    function SgceLimitOffset($Pagina, $PorPagina) {
        $Pagina = max(1, (int)$Pagina);
        $PorPagina = max(1, min(100, (int)$PorPagina));
        return [($Pagina - 1) * $PorPagina, $PorPagina];
    }
}

if (!function_exists('SgceRenderPager')) {
    function SgceRenderPager($NombrePagina, $PaginaActual, $TotalRegistros, $PorPagina, $ParametrosExtra = []) {
        $TotalPaginas = max(1, (int)ceil(((int)$TotalRegistros) / max(1, (int)$PorPagina)));
        if ($TotalPaginas <= 1) { return '<div class="SgcePagerServer text-muted small">Mostrando '.(int)$TotalRegistros.' registro(s).</div>'; }
        $PaginaActual = min(max(1, (int)$PaginaActual), $TotalPaginas);
        $Html = '<nav class="SgcePagerServer" aria-label="Paginación real"><ul class="pagination pagination-sm justify-content-center flex-wrap gap-1">';
        $Base = $_GET;
        foreach ($ParametrosExtra as $K=>$V) { $Base[$K] = $V; }
        $Crear = function($Pagina, $Texto, $Disabled=false, $Active=false) use ($Base, $NombrePagina) {
            $Params = $Base; $Params[$NombrePagina] = $Pagina;
            $Href = '?' . http_build_query($Params);
            $Clase = 'page-item' . ($Disabled ? ' disabled' : '') . ($Active ? ' active' : '');
            return '<li class="'.$Clase.'"><a class="page-link" href="'.htmlspecialchars($Href, ENT_QUOTES, 'UTF-8').'">'.$Texto.'</a></li>';
        };
        $Html .= $Crear(max(1, $PaginaActual-1), '&laquo;', $PaginaActual<=1);
        $Inicio = max(1, $PaginaActual - 2);
        $Fin = min($TotalPaginas, $PaginaActual + 2);
        if ($Inicio > 1) { $Html .= $Crear(1, '1', false, $PaginaActual===1); if ($Inicio > 2) { $Html .= '<li class="page-item disabled"><span class="page-link">...</span></li>'; } }
        for ($I=$Inicio; $I<=$Fin; $I++) { $Html .= $Crear($I, (string)$I, false, $I===$PaginaActual); }
        if ($Fin < $TotalPaginas) { if ($Fin < $TotalPaginas-1) { $Html .= '<li class="page-item disabled"><span class="page-link">...</span></li>'; } $Html .= $Crear($TotalPaginas, (string)$TotalPaginas, false, $PaginaActual===$TotalPaginas); }
        $Html .= $Crear(min($TotalPaginas, $PaginaActual+1), '&raquo;', $PaginaActual>=$TotalPaginas);
        $Html .= '</ul><div class="text-center text-muted small">Página '.$PaginaActual.' de '.$TotalPaginas.' · '.$TotalRegistros.' registro(s)</div></nav>';
        return $Html;
    }
}

if (!function_exists('SgceExisteColumna')) {
    function SgceExisteColumna($Pdo, $Tabla, $Columna) {
        $Stmt = $Pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $Stmt->execute([$Tabla, $Columna]);
        return (int)$Stmt->fetchColumn() > 0;
    }
}

if (!function_exists('SgceExisteIndice')) {
    function SgceExisteIndice($Pdo, $Tabla, $Indice) {
        $Stmt = $Pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?");
        $Stmt->execute([$Tabla, $Indice]);
        return (int)$Stmt->fetchColumn() > 0;
    }
}

if (!function_exists('SgceExisteTabla')) {
    function SgceExisteTabla($Pdo, $Tabla) {
        $Stmt = $Pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
        $Stmt->execute([$Tabla]);
        return (int)$Stmt->fetchColumn() > 0;
    }
}

if (!function_exists('SgceAsegurarCicloPeriodos')) {
    function SgceAsegurarCicloPeriodos($Pdo) {
        $Pdo->exec("CREATE TABLE IF NOT EXISTS CiclosEscolares (
            Id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            Nombre VARCHAR(30) NOT NULL,
            FechaInicio DATE NOT NULL,
            FechaFin DATE NOT NULL,
            Activo TINYINT(1) NOT NULL DEFAULT 1,
            FechaCreacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unico_ciclo_nombre (Nombre),
            INDEX idx_ciclos_activo_fecha (Activo, FechaInicio, FechaFin)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $Pdo->exec("CREATE TABLE IF NOT EXISTS PeriodosEvaluacion (
            Id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            CicloId INT UNSIGNED NOT NULL,
            Nombre VARCHAR(60) NOT NULL,
            Orden INT UNSIGNED NOT NULL DEFAULT 1,
            Activo TINYINT(1) NOT NULL DEFAULT 1,
            FechaCreacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_periodos_ciclos FOREIGN KEY (CicloId) REFERENCES CiclosEscolares(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
            UNIQUE KEY unico_periodo_ciclo_nombre (CicloId, Nombre),
            INDEX idx_periodos_ciclo_orden (CicloId, Activo, Orden)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $CicloId = (int)$Pdo->query("SELECT Id FROM CiclosEscolares WHERE Activo = 1 ORDER BY FechaInicio DESC, Id DESC LIMIT 1")->fetchColumn();
        if ($CicloId <= 0) {
            $Anio = (int)date('Y');
            $Mes = (int)date('n');
            $Inicio = $Mes >= 8 ? $Anio : $Anio - 1;
            $Fin = $Inicio + 1;
            $Nombre = $Inicio . '-' . $Fin;
            $Stmt = $Pdo->prepare("INSERT IGNORE INTO CiclosEscolares (Nombre, FechaInicio, FechaFin, Activo) VALUES (?, ?, ?, 1)");
            $Stmt->execute([$Nombre, $Inicio.'-08-01', $Fin.'-07-31']);
            $CicloId = (int)$Pdo->lastInsertId();
            if ($CicloId <= 0) { $CicloId = (int)$Pdo->query("SELECT Id FROM CiclosEscolares WHERE Nombre = " . $Pdo->quote($Nombre) . " LIMIT 1")->fetchColumn(); }
        }
        $Periodos = [['PRIMER PERIODO',1],['SEGUNDO PERIODO',2],['TERCER PERIODO',3],['FINAL',4]];
        $StmtP = $Pdo->prepare("INSERT IGNORE INTO PeriodosEvaluacion (CicloId, Nombre, Orden, Activo) VALUES (?, ?, ?, 1)");
        foreach ($Periodos as $P) { $StmtP->execute([$CicloId, $P[0], $P[1]]); }
        $PeriodoId = (int)$Pdo->query("SELECT Id FROM PeriodosEvaluacion WHERE CicloId = ".(int)$CicloId." AND Activo = 1 ORDER BY Orden ASC LIMIT 1")->fetchColumn();

        if (SgceExisteTabla($Pdo, 'Calificaciones') && !SgceExisteColumna($Pdo, 'Calificaciones', 'PeriodoId')) {
            $Pdo->exec("ALTER TABLE Calificaciones ADD COLUMN PeriodoId INT UNSIGNED NULL AFTER AsignacionId");
        }
        if (SgceExisteTabla($Pdo, 'Calificaciones') && SgceExisteColumna($Pdo, 'Calificaciones', 'PeriodoId')) {
            $Pdo->exec("UPDATE Calificaciones SET PeriodoId = ".(int)$PeriodoId." WHERE PeriodoId IS NULL");
            try { $Pdo->exec("ALTER TABLE Calificaciones MODIFY PeriodoId INT UNSIGNED NOT NULL"); } catch (Exception $E) {}
            if (SgceExisteIndice($Pdo, 'Calificaciones', 'unico_alumno_asignacion')) {
                try { $Pdo->exec("ALTER TABLE Calificaciones DROP INDEX unico_alumno_asignacion"); } catch (Exception $E) {}
            }
            if (!SgceExisteIndice($Pdo, 'Calificaciones', 'unico_alumno_asignacion_periodo')) {
                try { $Pdo->exec("ALTER TABLE Calificaciones ADD UNIQUE KEY unico_alumno_asignacion_periodo (AlumnoId, AsignacionId, PeriodoId)"); } catch (Exception $E) {}
            }
            if (!SgceExisteIndice($Pdo, 'Calificaciones', 'idx_calificaciones_periodo')) {
                try { $Pdo->exec("ALTER TABLE Calificaciones ADD INDEX idx_calificaciones_periodo (PeriodoId, AsignacionId, AlumnoId)"); } catch (Exception $E) {}
            }
        }
    }
}

if (!function_exists('SgcePeriodoActualId')) {
    function SgcePeriodoActualId($Pdo, $PeriodoSolicitado = 0) {
        $PeriodoSolicitado = (int)$PeriodoSolicitado;
        if ($PeriodoSolicitado > 0) {
            $Stmt = $Pdo->prepare("SELECT Id FROM PeriodosEvaluacion WHERE Id = ? AND Activo = 1 LIMIT 1");
            $Stmt->execute([$PeriodoSolicitado]);
            $Id = (int)$Stmt->fetchColumn();
            if ($Id > 0) { return $Id; }
        }
        return (int)$Pdo->query("SELECT P.Id FROM PeriodosEvaluacion P JOIN CiclosEscolares C ON P.CicloId=C.Id WHERE P.Activo=1 AND C.Activo=1 ORDER BY C.FechaInicio DESC, P.Orden ASC LIMIT 1")->fetchColumn();
    }
}

if (!function_exists('SgcePeriodosDisponibles')) {
    function SgcePeriodosDisponibles($Pdo) {
        return $Pdo->query("SELECT P.Id, P.Nombre, P.Orden, C.Nombre AS CicloNombre FROM PeriodosEvaluacion P JOIN CiclosEscolares C ON P.CicloId=C.Id WHERE P.Activo=1 AND C.Activo=1 ORDER BY C.FechaInicio DESC, P.Orden ASC")->fetchAll();
    }
}
