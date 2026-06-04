<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }




function SgcePublicoUrlRaizProyecto(): string {
    $Script = (string)($_SERVER['SCRIPT_NAME'] ?? '/');
    $Dir = str_replace('\\', '/', dirname($Script));
    if ($Dir === '.' || $Dir === '/' || $Dir === '\\') { return '/'; }
    if (basename($Dir) === 'public') { $Dir = dirname($Dir); }
    $Dir = '/' . trim($Dir, '/');
    return $Dir === '/' ? '/' : $Dir . '/';
}

function SgcePublicoNormalizarGrupo($Valor) {
    $Valor = SgceNormalizarMayusculas($Valor);
    return ($Valor !== '' && preg_match('/^[A-ZÁÉÍÓÚÜÑ0-9\-]+$/u', $Valor)) ? $Valor : '';
}

function SgcePublicoTextoEstado($Estado) {
    switch ((string)$Estado) {
        case 'A': return 'ASISTENCIA';
        case 'F': return 'FALTA';
        case 'R': return 'RETARDO';
        case 'J': return 'JUSTIFICANTE';
        default: return 'SIN REGISTRO';
    }
}

function SgcePublicoCatalogos(PDO $Pdo) {
    $Ciclo = SgceCicloActivo($Pdo);
    $CicloId = (int)($Ciclo['Id'] ?? 0);
    $Oferta = SgceOfertaActiva($Pdo);
    $OfertaId = (int)($Oferta['Id'] ?? 0);
    if ($CicloId <= 0 || $OfertaId <= 0) { return [[], [], [], false]; }

    $UsaProgramas = !empty($Oferta['UsaProgramas']);
    $StmtProgramas = $Pdo->prepare("SELECT DISTINCT PE.Id, PE.Nombre FROM Grupos G INNER JOIN ProgramasEducativos PE ON PE.Id = G.ProgramaId WHERE G.CicloId = ? AND G.OfertaId = ? AND G.Activo = 1 AND PE.Activo = 1 ORDER BY PE.Nombre ASC");
    $StmtProgramas->execute([$CicloId, $OfertaId]);
    $Programas = $StmtProgramas->fetchAll();

    $StmtGrados = $Pdo->prepare("SELECT DISTINCT Grado FROM Grupos WHERE CicloId = ? AND OfertaId = ? AND Activo = 1 ORDER BY CAST(Grado AS UNSIGNED), Grado ASC");
    $StmtGrados->execute([$CicloId, $OfertaId]);
    $Grados = $StmtGrados->fetchAll();

    $StmtGrupos = $Pdo->prepare("SELECT DISTINCT Grupo FROM Grupos WHERE CicloId = ? AND OfertaId = ? AND Activo = 1 ORDER BY Grupo ASC");
    $StmtGrupos->execute([$CicloId, $OfertaId]);
    $Grupos = $StmtGrupos->fetchAll();

    return [$Programas, $Grados, $Grupos, $UsaProgramas];
}

function SgcePublicoRateKey($NombreAlumno, $ProgramaId, $Grado, $Grupo, $Turno) {
    return SgceNormalizarMayusculas($NombreAlumno) . '|' . (int)$ProgramaId . '|' . SgceNormalizarMayusculas($Grado) . '|' . SgcePublicoNormalizarGrupo($Grupo) . '|' . SgceNormalizarMayusculas($Turno);
}

function SgcePublicoEnviarHeaders(): void {
    if (headers_sent()) { return; }
    header('X-Robots-Tag: noindex, nofollow, noarchive');
    header('X-Content-Type-Options: nosniff');
}

function SgcePublicoMensajeNoEncontrado(): string {
    return 'NO SE ENCONTRÓ UN ALUMNO CON ESOS DATOS. REVISA NOMBRE COMPLETO, GRADO, GRUPO Y TURNO.';
}

function SgcePublicoHoneypotActivado(): bool {
    $Valor = trim((string)($_POST['SitioWeb'] ?? ''));
    return $Valor !== '';
}

function SgcePublicoRateDisponible(PDO $Pdo, string $Contexto, string $RateKey): bool {
    return RateLimitDisponible($Pdo, $Contexto, $RateKey) && RateLimitDisponible($Pdo, $Contexto . '_ip', '');
}

function SgcePublicoRegistrarFallo(PDO $Pdo, string $Contexto, string $RateKey, int $MaxIntentosExactos = 8, int $MaxIntentosIp = 24, int $VentanaMinutos = 15): void {
    RateLimitRegistrarFallo($Pdo, $Contexto, $RateKey, $MaxIntentosExactos, $VentanaMinutos);
    RateLimitRegistrarFallo($Pdo, $Contexto . '_ip', '', $MaxIntentosIp, $VentanaMinutos);
}

function SgcePublicoLimpiarFalloExacto(PDO $Pdo, string $Contexto, string $RateKey): void {
    RateLimitLimpiar($Pdo, $Contexto, $RateKey);
}

function SgcePublicoCampoHoneypot(): string {
    return '<div class="SgceHpField" aria-hidden="true"><label>Sitio web</label><input type="text" name="SitioWeb" value="" tabindex="-1" autocomplete="off"></div>';
}

function SgcePublicoBuscarAlumno(PDO $Pdo, $NombreAlumno, $ProgramaId, $Grado, $Grupo, $Turno, &$Error = '') {
    $NombreAlumno = SgceNormalizarMayusculas($NombreAlumno);
    $ProgramaId = (int)$ProgramaId;
    $Grado = SgceNormalizarMayusculas($Grado);
    $Grupo = SgcePublicoNormalizarGrupo($Grupo);
    $Turno = SgceNormalizarMayusculas($Turno);

    if ($NombreAlumno === '' || !preg_match('/^[\p{L}\s]+$/u', $NombreAlumno)) {
        $Error = 'ESCRIBE EL NOMBRE COMPLETO DEL ALUMNO, SOLO CON LETRAS Y ESPACIOS.';
        return null;
    }
    $LongitudNombre = function_exists('mb_strlen') ? mb_strlen($NombreAlumno, 'UTF-8') : strlen($NombreAlumno);
    if ($LongitudNombre < 5 || $LongitudNombre > 160) {
        $Error = 'ESCRIBE EL NOMBRE COMPLETO DEL ALUMNO COMO APARECE EN LA ESCUELA.';
        return null;
    }
    if (!SgceValidarGrado($Grado) || $Grupo === '' || !in_array($Turno, ['MATUTINO', 'VESPERTINO'], true)) {
        $Error = 'SELECCIONA ETAPA/GRADO, GRUPO Y TURNO PARA VALIDAR LA CONSULTA.';
        return null;
    }

    $Ciclo = SgceCicloActivo($Pdo);
    $CicloId = (int)($Ciclo['Id'] ?? 0);
    if ($CicloId <= 0) {
        $Error = 'NO HAY UN CICLO ESCOLAR ACTIVO PARA CONSULTAS PÚBLICAS.';
        return null;
    }
    $Oferta = SgceOfertaActiva($Pdo);
    $OfertaId = (int)($Oferta['Id'] ?? 0);
    if ($OfertaId <= 0) {
        $Error = 'NO HAY UNA OFERTA EDUCATIVA ACTIVA PARA CONSULTAS PÚBLICAS.';
        return null;
    }
    $UsaProgramas = !empty($Oferta['UsaProgramas']);
    if ($UsaProgramas && $ProgramaId <= 0) {
        $Error = 'SELECCIONA EL PROGRAMA EDUCATIVO PARA VALIDAR LA CONSULTA.';
        return null;
    }
    if (!$UsaProgramas && $ProgramaId <= 0) {
        $ProgramaId = SgceProgramaGeneralId($Pdo, $OfertaId);
    }

    $StmtGrupo = $Pdo->prepare("SELECT G.Id, G.CicloId, G.OfertaId, G.ProgramaId, G.EtapaId, G.Grado, G.Grupo, G.Turno, PE.Nombre AS ProgramaNombre FROM Grupos G INNER JOIN ProgramasEducativos PE ON PE.Id = G.ProgramaId WHERE G.CicloId = ? AND G.OfertaId = ? AND G.ProgramaId = ? AND G.Grado = ? AND G.Grupo = ? AND G.Turno = ? AND G.Activo = 1 LIMIT 1");
    $StmtGrupo->execute([$CicloId, $OfertaId, $ProgramaId, $Grado, $Grupo, $Turno]);
    $InfoGrupo = $StmtGrupo->fetch();
    if (!$InfoGrupo) {
        $Error = SgcePublicoMensajeNoEncontrado();
        return null;
    }

    $StmtAlumno = $Pdo->prepare("
        SELECT Al.Id, Al.NombreCompleto, AI.GrupoId, AI.CicloId, AI.Estado
        FROM AlumnoInscripciones AI
        INNER JOIN Alumnos Al ON Al.Id = AI.AlumnoId AND Al.Activo = 1
        WHERE AI.CicloId = ?
          AND AI.OfertaId = ?
          AND AI.ProgramaId = ?
          AND AI.GrupoId = ?
          AND AI.Estado = 'INSCRITO'
          AND Al.NombreCompleto = ?
        LIMIT 1
    ");
    $StmtAlumno->execute([$CicloId, $OfertaId, $ProgramaId, (int)$InfoGrupo['Id'], $NombreAlumno]);
    $Alumno = $StmtAlumno->fetch();
    if (!$Alumno) {
        $Error = SgcePublicoMensajeNoEncontrado();
        return null;
    }

    return [
        'Alumno' => $Alumno,
        'Grupo' => $InfoGrupo,
        'Ciclo' => $Ciclo,
        'NombreAlumno' => $NombreAlumno,
        'ProgramaId' => $ProgramaId,
        'ProgramaNombre' => $InfoGrupo['ProgramaNombre'] ?? '',
        'Grado' => $Grado,
        'GrupoLetra' => $Grupo,
        'Turno' => $Turno,
    ];
}

function SgcePublicoNormalizarFecha($Valor, $Default = null) {
    $Valor = trim((string)$Valor);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $Valor)) { return $Default ?: date('Y-m-d'); }
    $Dt = DateTime::createFromFormat('Y-m-d', $Valor);
    return $Dt && $Dt->format('Y-m-d') === $Valor ? $Valor : ($Default ?: date('Y-m-d'));
}

function SgcePublicoValidarRangoFechas($FechaInicio, $FechaFin, &$Error = '', $MaxDias = 60) {
    $FechaInicio = SgcePublicoNormalizarFecha($FechaInicio, date('Y-m-d'));
    $FechaFin = SgcePublicoNormalizarFecha($FechaFin, date('Y-m-d'));
    $Inicio = new DateTime($FechaInicio);
    $Fin = new DateTime($FechaFin);
    $Hoy = new DateTime(date('Y-m-d'));

    if ($Inicio > $Fin) {
        $Error = 'LA FECHA INICIAL NO PUEDE SER MAYOR QUE LA FECHA FINAL.';
        return [$FechaInicio, $FechaFin];
    }
    if ($Fin > $Hoy) {
        $Error = 'LA FECHA FINAL NO PUEDE SER MAYOR AL DÍA ACTUAL.';
        return [$FechaInicio, $FechaFin];
    }
    $Dias = $Inicio->diff($Fin)->days + 1;
    if ($Dias > $MaxDias) {
        $Error = 'POR RENDIMIENTO Y SEGURIDAD, LA CONSULTA PÚBLICA PERMITE MÁXIMO ' . (int)$MaxDias . ' DÍAS POR BÚSQUEDA.';
        return [$FechaInicio, $FechaFin];
    }
    return [$FechaInicio, $FechaFin];
}

function SgcePublicoResumenAsistencia(PDO $Pdo, $AlumnoId, $GrupoId, $FechaInicio, $FechaFin) {
    $Conteos = ['A' => 0, 'F' => 0, 'R' => 0, 'J' => 0];
    $GrupoInfo = SgceGrupoObtenerPorId($Pdo, (int)$GrupoId);
    $CicloId = (int)($GrupoInfo['CicloId'] ?? 0);

    $TotalMaterias = 0;
    $Detalle = [];
    if ($CicloId > 0) {
        $StmtTotalMaterias = $Pdo->prepare("SELECT COUNT(*) FROM Asignaciones WHERE CicloId = ? AND GrupoId = ? AND Activo = 1");
        $StmtTotalMaterias->execute([$CicloId, (int)$GrupoId]);
        $TotalMaterias = (int)$StmtTotalMaterias->fetchColumn();

        $StmtDetalle = $Pdo->prepare("
            SELECT Asis.FechaDia, DATE_FORMAT(Asis.FechaDia, '%d/%m/%Y') AS FechaTexto, Asis.Estado,
                   Asg.MateriaNombre, U.NombreCompleto AS Maestro
            FROM Asistencias Asis
            INNER JOIN Asignaciones Asg ON Asg.Id = Asis.AsignacionId AND Asg.CicloId = Asis.CicloId
            INNER JOIN Usuarios U ON U.Id = Asg.MaestroId
            INNER JOIN AlumnoInscripciones AI ON AI.AlumnoId = Asis.AlumnoId AND AI.CicloId = Asis.CicloId AND AI.GrupoId = Asg.GrupoId
            WHERE Asis.AlumnoId = ?
              AND Asis.CicloId = ?
              AND Asis.FechaDia BETWEEN ? AND ?
              AND Asg.GrupoId = ?
              AND Asg.Activo = 1
            ORDER BY Asis.FechaDia DESC, Asg.MateriaNombre ASC
            LIMIT 600
        ");
        $StmtDetalle->execute([(int)$AlumnoId, $CicloId, $FechaInicio, $FechaFin, (int)$GrupoId]);
        $Detalle = $StmtDetalle->fetchAll();
    }

    foreach ($Detalle as $Fila) {
        $Estado = (string)$Fila['Estado'];
        if (isset($Conteos[$Estado])) { $Conteos[$Estado]++; }
    }

    $RegistrosCapturados = count($Detalle);
    $EsHoy = ($FechaInicio === date('Y-m-d') && $FechaFin === date('Y-m-d'));
    $SinCapturarHoy = $EsHoy ? max(0, $TotalMaterias - $RegistrosCapturados) : null;

    $RegistrosPositivos = $Conteos['A'] + $Conteos['R'] + $Conteos['J'];
    if ($TotalMaterias <= 0) {
        $EstatusGeneral = 'SIN MATERIAS ASIGNADAS';
        $ClaseEstado = 'warning';
        $IconoEstado = 'fa-triangle-exclamation';
        $Descripcion = 'EL GRUPO TODAVÍA NO TIENE MATERIAS ASIGNADAS EN EL CICLO ESCOLAR CONSULTADO.';
    } elseif ($RegistrosCapturados <= 0) {
        $EstatusGeneral = 'SIN REGISTROS EN EL RANGO';
        $ClaseEstado = 'secondary';
        $IconoEstado = 'fa-clock';
        $Descripcion = 'NO SE ENCONTRARON ASISTENCIAS CAPTURADAS PARA ESTE ALUMNO EN EL RANGO SELECCIONADO.';
    } elseif ($Conteos['F'] <= 0 && $RegistrosPositivos > 0) {
        $EstatusGeneral = 'SIN FALTAS EN EL RANGO';
        $ClaseEstado = 'success';
        $IconoEstado = 'fa-circle-check';
        $Descripcion = 'EL ALUMNO TIENE ASISTENCIAS, RETARDOS O JUSTIFICANTES, SIN FALTAS CAPTURADAS EN EL RANGO.';
    } elseif ($RegistrosPositivos > 0 && $Conteos['F'] > 0) {
        $EstatusGeneral = 'ASISTENCIA PARCIAL';
        $ClaseEstado = 'warning';
        $IconoEstado = 'fa-circle-half-stroke';
        $Descripcion = 'EL ALUMNO TIENE REGISTROS POSITIVOS, PERO TAMBIÉN PRESENTA FALTAS EN EL RANGO.';
    } else {
        $EstatusGeneral = 'CON FALTAS REGISTRADAS';
        $ClaseEstado = 'danger';
        $IconoEstado = 'fa-circle-xmark';
        $Descripcion = 'EN LOS REGISTROS CAPTURADOS DEL RANGO, EL ALUMNO PRESENTA FALTAS.';
    }

    return [
        'TotalMaterias' => $TotalMaterias,
        'Conteos' => $Conteos,
        'Detalle' => $Detalle,
        'RegistrosCapturados' => $RegistrosCapturados,
        'SinCapturarHoy' => $SinCapturarHoy,
        'EstatusGeneral' => $EstatusGeneral,
        'ClaseEstado' => $ClaseEstado,
        'IconoEstado' => $IconoEstado,
        'IconoEmoji' => (function($IconoEstado) {
            switch ($IconoEstado) {
                case 'fa-triangle-exclamation': return '⚠️';
                case 'fa-clock': return '⏱️';
                case 'fa-circle-check': return '✅';
                case 'fa-circle-half-stroke': return '🟡';
                case 'fa-circle-xmark': return '❌';
                default: return '📋';
            }
        })($IconoEstado),
        'Descripcion' => $Descripcion,
    ];
}

function SgcePublicoCalificacionesCiclo(PDO $Pdo, $AlumnoId, $GrupoId) {
    $GrupoInfo = SgceGrupoObtenerPorId($Pdo, (int)$GrupoId);
    $CicloId = (int)($GrupoInfo['CicloId'] ?? 0);
    if ($CicloId <= 0) {
        return ['Ciclo' => null, 'Periodos' => [], 'Filas' => [], 'PromedioGeneral' => null, 'Capturadas' => 0, 'Materias' => 0];
    }

    $Ciclo = SgceCicloPorId($Pdo, $CicloId);
    if (!$Ciclo || (int)($Ciclo['Activo'] ?? 0) !== 1) {
        return ['Ciclo' => $Ciclo, 'Periodos' => [], 'Filas' => [], 'PromedioGeneral' => null, 'Capturadas' => 0, 'Materias' => 0];
    }

    $StmtInscrito = $Pdo->prepare("SELECT Id FROM AlumnoInscripciones WHERE AlumnoId = ? AND CicloId = ? AND GrupoId = ? AND Estado = 'INSCRITO' LIMIT 1");
    $StmtInscrito->execute([(int)$AlumnoId, $CicloId, (int)$GrupoId]);
    if (!$StmtInscrito->fetchColumn()) {
        return ['Ciclo' => $Ciclo, 'Periodos' => [], 'Filas' => [], 'PromedioGeneral' => null, 'Capturadas' => 0, 'Materias' => 0];
    }

    $OfertaId = (int)($GrupoInfo['OfertaId'] ?? 0);
    $StmtPeriodos = $Pdo->prepare("SELECT Id, Nombre, Orden FROM PeriodosEvaluacion WHERE CicloId = ? AND OfertaId = ? AND Activo = 1 ORDER BY Orden ASC, Id ASC");
    $StmtPeriodos->execute([$CicloId, $OfertaId]);
    $Periodos = $StmtPeriodos->fetchAll();

    $StmtAsignaciones = $Pdo->prepare("
        SELECT A.Id, A.MateriaNombre, U.NombreCompleto AS Maestro
        FROM Asignaciones A
        INNER JOIN Usuarios U ON U.Id = A.MaestroId
        WHERE A.CicloId = ? AND A.GrupoId = ? AND A.Activo = 1 AND U.Activo = 1
        ORDER BY A.MateriaNombre ASC
    ");
    $StmtAsignaciones->execute([$CicloId, (int)$GrupoId]);
    $Asignaciones = $StmtAsignaciones->fetchAll();

    $Matriz = [];
    if ($Asignaciones && $Periodos) {
        $AsignacionIds = array_map(fn($A) => (int)$A['Id'], $Asignaciones);
        $PeriodoIds = array_map(fn($P) => (int)$P['Id'], $Periodos);
        $PlaceAsg = implode(',', array_fill(0, count($AsignacionIds), '?'));
        $PlacePer = implode(',', array_fill(0, count($PeriodoIds), '?'));
        $Sql = "SELECT AsignacionId, PeriodoId, Calificacion FROM Calificaciones WHERE AlumnoId = ? AND AsignacionId IN ($PlaceAsg) AND PeriodoId IN ($PlacePer)";
        $StmtCal = $Pdo->prepare($Sql);
        $StmtCal->execute(array_merge([(int)$AlumnoId], $AsignacionIds, $PeriodoIds));
        foreach ($StmtCal->fetchAll() as $C) {
            $Matriz[(int)$C['AsignacionId']][(int)$C['PeriodoId']] = $C['Calificacion'];
        }
    }

    $Filas = [];
    $SumaGeneral = 0.0;
    $CuentaGeneral = 0;
    foreach ($Asignaciones as $A) {
        $Valores = [];
        $SumaMateria = 0.0;
        $CuentaMateria = 0;
        foreach ($Periodos as $P) {
            $Valor = $Matriz[(int)$A['Id']][(int)$P['Id']] ?? null;
            $Valores[(int)$P['Id']] = $Valor;
            if ($Valor !== null && $Valor !== '') {
                $SumaMateria += (float)$Valor;
                $CuentaMateria++;
                $SumaGeneral += (float)$Valor;
                $CuentaGeneral++;
            }
        }
        $Filas[] = [
            'AsignacionId' => (int)$A['Id'],
            'MateriaNombre' => $A['MateriaNombre'],
            'Maestro' => $A['Maestro'],
            'Valores' => $Valores,
            'PromedioMateria' => $CuentaMateria > 0 ? round($SumaMateria / $CuentaMateria, 2) : null,
            'CapturadasMateria' => $CuentaMateria,
        ];
    }

    return [
        'Ciclo' => $Ciclo,
        'Periodos' => $Periodos,
        'Filas' => $Filas,
        'PromedioGeneral' => $CuentaGeneral > 0 ? round($SumaGeneral / $CuentaGeneral, 2) : null,
        'Capturadas' => $CuentaGeneral,
        'Materias' => count($Asignaciones),
    ];
}


function SgcePublicoLimpiarTokensConsulta(): void {
    IniciarSesionSegura();
    if (empty($_SESSION['SgceConsultaPublica']) || !is_array($_SESSION['SgceConsultaPublica'])) {
        $_SESSION['SgceConsultaPublica'] = [];
        return;
    }
    $Ahora = time();
    foreach ($_SESSION['SgceConsultaPublica'] as $Token => $Info) {
        $Expira = is_array($Info) ? (int)($Info['Expira'] ?? 0) : 0;
        if ($Expira <= $Ahora) { unset($_SESSION['SgceConsultaPublica'][$Token]); }
    }
}

function SgcePublicoCrearTokenConsulta(string $Tipo, array $Datos, int $Minutos = 20): string {
    IniciarSesionSegura();
    SgcePublicoLimpiarTokensConsulta();
    if (!isset($_SESSION['SgceConsultaPublica']) || !is_array($_SESSION['SgceConsultaPublica'])) {
        $_SESSION['SgceConsultaPublica'] = [];
    }
    while (count($_SESSION['SgceConsultaPublica']) >= 8) {
        $PrimerToken = array_key_first($_SESSION['SgceConsultaPublica']);
        if ($PrimerToken === null) { break; }
        unset($_SESSION['SgceConsultaPublica'][$PrimerToken]);
    }
    $Token = bin2hex(random_bytes(18));
    $_SESSION['SgceConsultaPublica'][$Token] = [
        'Tipo' => $Tipo,
        'Datos' => $Datos,
        'Expira' => time() + (max(1, min(30, $Minutos)) * 60),
        'CreadoEn' => time(),
    ];
    return $Token;
}

function SgcePublicoLeerTokenConsulta(string $Token, string $Tipo = ''): ?array {
    IniciarSesionSegura();
    SgcePublicoLimpiarTokensConsulta();
    $Token = trim($Token);
    if ($Token === '' || !preg_match('/^[a-f0-9]{36}$/i', $Token)) { return null; }
    $Info = $_SESSION['SgceConsultaPublica'][$Token] ?? null;
    if (!is_array($Info)) { return null; }
    if ((int)($Info['Expira'] ?? 0) <= time()) {
        unset($_SESSION['SgceConsultaPublica'][$Token]);
        return null;
    }
    if ($Tipo !== '' && (string)($Info['Tipo'] ?? '') !== $Tipo) { return null; }
    return $Info;
}

function SgcePublicoTokenDesdeGet(): string {
    $Token = trim((string)($_GET['ConsultaToken'] ?? ''));
    return preg_match('/^[a-f0-9]{36}$/i', $Token) ? $Token : '';
}

function SgcePublicoFormatoCalificacion($Valor) {
    return $Valor !== null && $Valor !== '' ? number_format((float)$Valor, 2) : '-';
}
