<?php
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

if (!defined('SGCE_VERSION')) { define('SGCE_VERSION', '1.0.185'); }

function InstalarAssetUrl(string $Ruta): string {
    $Separador = str_contains($Ruta, '?') ? '&' : '?';
    return $Ruta . $Separador . 'v=' . rawurlencode((string)SGCE_VERSION);
}

function InstalarHeadAssets(): string {
    return implode("\n", [
        '<link rel="icon" href="assets/media/img/favicon.ico">',
        '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">',
        '<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">',
        '<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">',
        '<link rel="stylesheet" href="' . htmlspecialchars(InstalarAssetUrl('assets/css/sgce-base.min.css'), ENT_QUOTES, 'UTF-8') . '">',
        '<link rel="stylesheet" href="' . htmlspecialchars(InstalarAssetUrl('assets/css/sgce-soft-motion.css'), ENT_QUOTES, 'UTF-8') . '">',
        '<link rel="stylesheet" href="' . htmlspecialchars(InstalarAssetUrl('assets/css/sgce-buttons.css'), ENT_QUOTES, 'UTF-8') . '">',
        '<link rel="stylesheet" href="' . htmlspecialchars(InstalarAssetUrl('assets/css/installer.css'), ENT_QUOTES, 'UTF-8') . '">',
    ]);
}


if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

$Mensaje = '';
$Tipo = 'info';
$SqlFile = __DIR__ . '/install/SGCE.sql';
$LockFile = __DIR__ . '/storage/install.lock';
$LocalConfigFile = __DIR__ . '/config/database.local.php';

if (is_file($LockFile) && getenv('SGCE_ALLOW_REINSTALL') !== '1') {
    http_response_code(403);
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>SGCE | Instalador bloqueado</title>' . InstalarHeadAssets() . '</head><body class="SgceInstallerLockedBody"><main class="SgceInstallerLockedCard"><h1>Instalador bloqueado</h1><p>La instalación ya fue realizada. Para proteger el sistema, el instalador quedó bloqueado por install.lock.</p><p>Si necesitas reinstalar, elimina manualmente storage/install.lock o define SGCE_ALLOW_REINSTALL=1 de forma temporal.</p><a href="index.php" class="SgceInstallerLockedButton">Ir al sistema</a></main></body></html>';
    exit;
}


if (session_status() === PHP_SESSION_NONE) {
    if (!headers_sent()) {
        $RutaCookieInstalador = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        if ($RutaCookieInstalador === '' || $RutaCookieInstalador === '.') { $RutaCookieInstalador = '/'; }
        $HttpsInstalador = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => $RutaCookieInstalador,
            'secure' => $HttpsInstalador,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    session_start();
}

if (!defined('SGCE_INSTALLER')) { define('SGCE_INSTALLER', true); }
require_once __DIR__ . '/install/installer/InstallerLoader.php';

$YaInstalado = is_file($LockFile) || is_file($LocalConfigFile);

$AnioActual = (int)date('Y');
$Valores = [
    'Host' => $_POST['Host'] ?? 'localhost',
    'BaseDatos' => $_POST['BaseDatos'] ?? '',
    'UsuarioMysql' => $_POST['UsuarioMysql'] ?? '',
    'PasswordMysql' => $_POST['PasswordMysql'] ?? '',
    'AdminNombre' => $_POST['AdminNombre'] ?? '',
    'AdminUsuario' => $_POST['AdminUsuario'] ?? '',
    'BackupDir' => $_POST['BackupDir'] ?? (__DIR__ . '/storage/backups'),
    'NombreEscuela' => $_POST['NombreEscuela'] ?? '',
    'ClaveCentroTrabajo' => $_POST['ClaveCentroTrabajo'] ?? '',
    'DirectorNombre' => $_POST['DirectorNombre'] ?? '',
    'MunicipioEstado' => $_POST['MunicipioEstado'] ?? '',
    'TelefonoEscuela' => $_POST['TelefonoEscuela'] ?? '',
    'CorreoEscuela' => $_POST['CorreoEscuela'] ?? '',
    'LemaInstitucional' => $_POST['LemaInstitucional'] ?? '',
    'ColorInstitucional' => $_POST['ColorInstitucional'] ?? '#97051E',
    'CicloNombre' => $_POST['CicloNombre'] ?? '',
    'FechaInicio' => $_POST['FechaInicio'] ?? '',
    'FechaFin' => $_POST['FechaFin'] ?? '',
    'PeriodosCantidad' => $_POST['PeriodosCantidad'] ?? '',
    'PeriodosNombreBase' => $_POST['PeriodosNombreBase'] ?? '',
    'PeriodosModo' => $_POST['PeriodosModo'] ?? 'AUTOMATICO',
    'PeriodosPersonalizados' => $_POST['PeriodosPersonalizados'] ?? '',
    'UsaPlaneaciones' => $_POST['UsaPlaneaciones'] ?? '',
    'TipoPlaneacion' => $_POST['TipoPlaneacion'] ?? 'CICLO',
    'PlaneacionesCantidad' => $_POST['PlaneacionesCantidad'] ?? '',
    'TurnosDisponibles' => $_POST['TurnosDisponibles'] ?? "MATUTINO\nVESPERTINO",
    'CalificacionMinima' => $_POST['CalificacionMinima'] ?? '5',
    'CalificacionMaxima' => $_POST['CalificacionMaxima'] ?? '10',
    'CalificacionAprobatoria' => $_POST['CalificacionAprobatoria'] ?? '6',
    'CalificacionDecimales' => $_POST['CalificacionDecimales'] ?? '1',
    'MatriculaAutomatica' => $_POST['MatriculaAutomatica'] ?? '1',
    'MatriculaPrefijo' => $_POST['MatriculaPrefijo'] ?? 'SGCE',
    'NivelEducativo' => $_POST['NivelEducativo'] ?? 'SECUNDARIA',
    'NombreOfertaAcademica' => $_POST['NombreOfertaAcademica'] ?? '',
    'TipoPeriodizacion' => $_POST['TipoPeriodizacion'] ?? 'ANUAL',
    'TotalEtapas' => $_POST['TotalEtapas'] ?? '',
    'UsaProgramas' => $_POST['UsaProgramas'] ?? '',
    'ProgramasIniciales' => $_POST['ProgramasIniciales'] ?? '',
    'UrlBaseSistema' => $_POST['UrlBaseSistema'] ?? (function_exists('InstalarDetectarUrlBaseSistema') ? InstalarDetectarUrlBaseSistema() : ''),
];

if (isset($_GET['VerificarServidor'])) {
    header('Content-Type: application/json; charset=utf-8');
    if ($YaInstalado) {
        http_response_code(403);
        echo json_encode(['error' => 'Instalador bloqueado.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    echo json_encode(['checks' => InstalarVerificacionesServidor($Valores, true)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$YaInstalado) {
    $DetallesEliminacion = [];
    $InstalacionEstructuraCreada = false;
    $InstalacionCompletada = false;
    try {
        if (!InstalarValidarCsrf($_POST['InstalarCsrfToken'] ?? '')) {
            throw new Exception('Solicitud inválida. Recarga el instalador e intenta nuevamente.');
        }
        if (($_POST['ConfirmarInstalacion'] ?? '') !== 'INSTALAR SGCE') {
            throw new Exception('Confirmación inválida. Escribe exactamente INSTALAR SGCE.');
        }
        if (!is_file($SqlFile)) { throw new Exception('No se encontró install/SGCE.sql.'); }

        $Host = trim((string)$Valores['Host']);
        $BaseDatos = trim((string)$Valores['BaseDatos']);
        $UsuarioMysql = trim((string)$Valores['UsuarioMysql']);
        $PasswordMysql = (string)$Valores['PasswordMysql'];
        $AdminNombre = InstalarNormalizarTexto($Valores['AdminNombre'], true);
        $AdminUsuario = trim((string)$Valores['AdminUsuario']);
        $AdminPassword = (string)($_POST['AdminPassword'] ?? '');
        $AdminPasswordConfirm = (string)($_POST['AdminPasswordConfirm'] ?? '');
        $BackupDir = trim((string)$Valores['BackupDir']);
        $UrlBaseSistema = InstalarNormalizarUrlBaseSistema($Valores['UrlBaseSistema'] ?? InstalarDetectarUrlBaseSistema());

        $NombreEscuela = InstalarNormalizarTexto($Valores['NombreEscuela'], true);
        $ClaveCentroTrabajo = InstalarNormalizarTexto($Valores['ClaveCentroTrabajo'], true);
        $DirectorNombre = InstalarNormalizarTexto($Valores['DirectorNombre'], true);
        $MunicipioEstado = InstalarNormalizarTexto($Valores['MunicipioEstado'], true);
        $TelefonoEscuela = InstalarNormalizarTexto($Valores['TelefonoEscuela']);
        $CorreoEscuela = InstalarNormalizarTexto($Valores['CorreoEscuela']);
        $LemaInstitucional = InstalarNormalizarTexto($Valores['LemaInstitucional']);
        $ColorInstitucional = strtoupper(trim((string)($Valores['ColorInstitucional'] ?? '#97051E')));
        $CicloNombre = InstalarNormalizarTexto($Valores['CicloNombre'], true);
        $FechaInicio = trim((string)$Valores['FechaInicio']);
        $FechaFin = trim((string)$Valores['FechaFin']);
        $PeriodosCantidadTexto = trim((string)($Valores['PeriodosCantidad'] ?? ''));
        if ($PeriodosCantidadTexto === '' || !ctype_digit($PeriodosCantidadTexto)) { throw new Exception('Escribe la cantidad de periodos de evaluación.'); }
        $PeriodosCantidad = max(1, min(12, (int)$PeriodosCantidadTexto));
        if (trim((string)($Valores['PeriodosNombreBase'] ?? '')) === '') { throw new Exception('Escribe el nombre base del periodo de evaluación.'); }
        $PeriodosNombreBase = InstalarNombreBasePeriodoValido($Valores['PeriodosNombreBase'] ?? '');
        $PeriodosModo = InstalarModoPeriodosValido($Valores['PeriodosModo'] ?? 'AUTOMATICO');
        $PeriodosPersonalizados = InstalarNormalizarTexto($Valores['PeriodosPersonalizados'] ?? '', true);
        $PeriodosFinales = InstalarGenerarNombresPeriodos($PeriodosCantidad, $PeriodosNombreBase, $PeriodosModo, $PeriodosPersonalizados);
        $UsaPlaneaciones = !empty($Valores['UsaPlaneaciones']);
        $TipoPlaneacion = InstalarTipoPlaneacionValido($Valores['TipoPlaneacion'] ?? 'CICLO');
        $PlaneacionesCantidadTexto = trim((string)($Valores['PlaneacionesCantidad'] ?? ''));
        $TurnosDisponiblesTexto = InstalarTurnosTextoSeguro((string)($Valores['TurnosDisponibles'] ?? "MATUTINO\nVESPERTINO"));
        $CalificacionMinima = max(0, min(100, (float)($Valores['CalificacionMinima'] ?? 5)));
        $CalificacionMaxima = max(0, min(100, (float)($Valores['CalificacionMaxima'] ?? 10)));
        if ($CalificacionMinima >= $CalificacionMaxima) { throw new Exception('La escala de calificaciones no es válida.'); }
        $CalificacionAprobatoria = max($CalificacionMinima, min($CalificacionMaxima, (float)($Valores['CalificacionAprobatoria'] ?? 6)));
        $CalificacionDecimales = !empty($Valores['CalificacionDecimales']) ? '1' : '0';
        $MatriculaAutomatica = !empty($Valores['MatriculaAutomatica']) ? '1' : '0';
        $MatriculaPrefijo = InstalarNormalizarMayusculas((string)($Valores['MatriculaPrefijo'] ?? 'SGCE'));
        if ($MatriculaAutomatica === '1' && !preg_match('/^[A-Z0-9]{2,12}$/', $MatriculaPrefijo)) { $MatriculaPrefijo = 'SGCE'; }
        if ($MatriculaAutomatica !== '1') { $MatriculaPrefijo = 'SGCE'; }
        if ($UsaPlaneaciones && ($PlaneacionesCantidadTexto === '' || !ctype_digit($PlaneacionesCantidadTexto))) { throw new Exception('Escribe la cantidad de planeaciones.'); }
        $PlaneacionesCantidad = $UsaPlaneaciones ? max(1, min(12, (int)$PlaneacionesCantidadTexto)) : 0;
        $NivelEducativo = InstalarNivelValido($Valores['NivelEducativo'] ?? 'SECUNDARIA');
        $TipoPeriodizacion = InstalarTipoPeriodizacionValido($Valores['TipoPeriodizacion'] ?? 'ANUAL');
        $TotalEtapasTexto = trim((string)($Valores['TotalEtapas'] ?? ''));
        if ($TotalEtapasTexto === '' || !ctype_digit($TotalEtapasTexto)) { throw new Exception('Escribe la cantidad de etapas académicas.'); }
        $TotalEtapas = max(1, min(20, (int)$TotalEtapasTexto));
        $UsaProgramas = !empty($Valores['UsaProgramas']) || InstalarRequiereProgramasEducativos($NivelEducativo);
        $NombreOfertaAcademica = InstalarNombreOfertaFinal($Valores['NombreOfertaAcademica'] ?? '', $NivelEducativo);
        $ProgramasIniciales = InstalarNormalizarTexto($Valores['ProgramasIniciales'] ?? '', true);
        $ProgramasCapturados = array_values(array_filter(array_map(static fn($P) => InstalarNormalizarTexto($P, true), preg_split('/[,;\n]+/u', $ProgramasIniciales))));
        $ProgramasRealesCapturados = array_values(array_filter($ProgramasCapturados, static fn($P) => $P !== 'GENERAL'));
        if ($UsaProgramas && count($ProgramasRealesCapturados) === 0) {
            throw new Exception('Este nivel u organización usa programas educativos. Captura al menos un programa real, por ejemplo: INFORMÁTICA, SISTEMAS, CONTABILIDAD o MAESTRÍA EN EDUCACIÓN.');
        }

        if ($Host === '' || $UsuarioMysql === '' || $BaseDatos === '' || !InstalarNombreBaseValido($BaseDatos)) {
            throw new Exception('Revisa host, usuario MySQL y nombre de base de datos. La base solo puede usar letras, números y guion bajo.');
        }
        if ($NombreEscuela === '' || InstalarLongitud($NombreEscuela) < 3 || InstalarLongitud($NombreEscuela) > 150) {
            throw new Exception('Escribe el nombre oficial de la escuela. Debe tener entre 3 y 150 caracteres.');
        }
        if ($ClaveCentroTrabajo !== '' && !preg_match('/^[A-Z0-9\-]{3,30}$/u', $ClaveCentroTrabajo)) {
            throw new Exception('La CCT / clave solo debe usar letras, números o guion, de 3 a 30 caracteres.');
        }
        foreach ([
            InstalarValidarTextoOpcional($DirectorNombre, 'El nombre del director(a)', 120, true),
            InstalarValidarTextoOpcional($MunicipioEstado, 'Municipio y estado', 120, false),
            InstalarValidarTelefonoOpcional($TelefonoEscuela),
            InstalarValidarCorreoOpcional($CorreoEscuela),
        ] as $ValidacionCampo) {
            if ($ValidacionCampo !== true) { throw new Exception($ValidacionCampo); }
        }
        if (InstalarLongitud($LemaInstitucional) > 180) {
            throw new Exception('El lema institucional no debe exceder 180 caracteres.');
        }
        if (!preg_match('/^#[0-9A-F]{6}$/', $ColorInstitucional)) {
            throw new Exception('Selecciona un color institucional válido.');
        }
        $ChecksServidor = InstalarVerificacionesServidor($Valores, false);
        if (!InstalarChecksCriticosOk($ChecksServidor)) {
            throw new Exception('El servidor todavía no cumple los requisitos mínimos. Usa Verificar servidor para revisar permisos y extensiones.');
        }
        if ($AdminNombre === '' || InstalarLongitud($AdminNombre) < 3 || !InstalarSoloLetrasEspacios($AdminNombre)) {
            throw new Exception('Escribe el nombre del administrador. Solo debe contener letras y espacios.');
        }
        if ($AdminUsuario === '' || !preg_match('/^[a-zA-Z0-9._@-]{3,80}$/', $AdminUsuario)) {
            throw new Exception('Revisa el usuario administrador. Debe tener mínimo 3 caracteres y acepta letras, números, punto, guion, guion bajo o @.');
        }
        if ($AdminPassword !== $AdminPasswordConfirm) {
            throw new Exception('Las contraseñas del administrador no coinciden. Revisa ambos campos e intenta nuevamente.');
        }
        $ValidacionPassword = InstalarValidarPassword($AdminPassword);
        if ($ValidacionPassword !== true) { throw new Exception($ValidacionPassword); }
        if ($CicloNombre === '' || !InstalarValidarFecha($FechaInicio) || !InstalarValidarFecha($FechaFin) || strtotime($FechaInicio) >= strtotime($FechaFin)) {
            throw new Exception('Revisa el ciclo escolar. Debe tener nombre, fecha de inicio y fecha de fin válida.');
        }
        if (count($PeriodosFinales) !== $PeriodosCantidad || count(array_unique($PeriodosFinales)) !== count($PeriodosFinales)) {
            throw new Exception('Revisa los periodos de evaluación. Deben existir y no repetirse.');
        }
        if ($UsaPlaneaciones && ($PlaneacionesCantidad < 1 || $PlaneacionesCantidad > 12)) {
            throw new Exception('La cantidad de planeaciones debe estar entre 1 y 12.');
        }
        if ($UsaPlaneaciones && $TipoPlaneacion === 'PERIODO' && $PlaneacionesCantidad > $PeriodosCantidad) {
            throw new Exception('Cuando el tipo es Por periodo, la cantidad de planeaciones no puede ser mayor que la cantidad de periodos de evaluación.');
        }
        if ($NombreOfertaAcademica === '' || InstalarLongitud($NombreOfertaAcademica) > 140) {
            throw new Exception('Escribe un nombre válido para la oferta educativa.');
        }

        
        InstalarVerificarEscritura($LocalConfigFile);
        if (!is_dir(dirname($LockFile))) { @mkdir(dirname($LockFile), 0775, true); }
        if (!is_writable(dirname($LockFile))) {
            throw new Exception('La carpeta storage no tiene permisos de escritura para crear install.lock. Ruta: ' . dirname($LockFile) . ' | Permisos: ' . InstalarFormatoPermisos(dirname($LockFile)) . ' | Usuario PHP: ' . InstalarUsuarioPhp());
        }

        try {
            $PdoServidor = InstalarCrearConexionMysql(InstalarDsnServidorMysql($Host), $UsuarioMysql, $PasswordMysql);
        } catch (Throwable $EConexionServidor) {
            InstalarRegistrarError($EConexionServidor, 'MYSQL_SERVIDOR');
            throw new InstalarMensajeUsuario('No se pudo conectar al servidor MySQL. Revisa host, usuario y contraseña.');
        }

        try {
            if (!InstalarBaseDatosExiste($PdoServidor, $BaseDatos)) {
                try {
                    InstalarCrearBaseDatos($PdoServidor, $BaseDatos);
                } catch (Throwable $ECrearBase) {
                    InstalarRegistrarError($ECrearBase, 'MYSQL_CREAR_BASE');
                    throw new InstalarMensajeUsuario('La base de datos no existe y este usuario MySQL no tiene permiso para crearla. En local usa un usuario con permiso CREATE DATABASE o crea la base manualmente; en Plesk créala primero desde el panel.');
                }
            }
        } catch (InstalarMensajeUsuario $EUsuario) {
            throw $EUsuario;
        } catch (Throwable $EBaseExiste) {
            InstalarRegistrarError($EBaseExiste, 'MYSQL_REVISAR_BASE');
            throw new InstalarMensajeUsuario('No fue posible revisar o preparar la base de datos indicada. Revisa permisos del usuario MySQL sobre esa base.');
        }

        $DsnDb = InstalarDsnBaseMysql($Host, $BaseDatos);
        try {
            $PdoInstall = InstalarCrearConexionMysql($DsnDb, $UsuarioMysql, $PasswordMysql);
        } catch (Throwable $EConexionBase) {
            InstalarRegistrarError($EConexionBase, 'MYSQL_CONECTAR_BASE');
            throw new InstalarMensajeUsuario('La base existe o fue creada, pero el usuario MySQL no tiene permiso para usarla. Asigna permisos completos sobre esa base e intenta nuevamente.');
        }

        $TablasExistentes = $PdoInstall->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($TablasExistentes)) {
            throw new InstalarMensajeUsuario('La base de datos seleccionada no está vacía. Usa una base exclusiva y vacía para evitar mezclar instalaciones.');
        }

        $Sql = file_get_contents($SqlFile);
        if ($Sql === false || trim($Sql) === '') { throw new InstalarMensajeUsuario('El SQL de instalación está vacío.'); }
        InstalarEjecutarSqlInstalacion($PdoInstall, InstalarSepararSql($Sql));
        $InstalacionEstructuraCreada = true;

        $PdoDb = InstalarCrearConexionMysql($DsnDb, $UsuarioMysql, $PasswordMysql);

        $PdoDb->beginTransaction();
        $StmtAdmin = $PdoDb->prepare("INSERT INTO Usuarios (Username, Password, NombreCompleto, NombreBusqueda, Rol, Activo) VALUES (?, ?, ?, ?, 'admin', 1)");
        $StmtAdmin->execute([$AdminUsuario, password_hash($AdminPassword, PASSWORD_DEFAULT), $AdminNombre, InstalarTextoBusqueda($AdminNombre)]);
        $AdminId = (int)$PdoDb->lastInsertId();

        InstalarGuardarConfiguracion($PdoDb, [
            'NombreEscuela' => $NombreEscuela,
            'ClaveCentroTrabajo' => $ClaveCentroTrabajo,
            'DirectorNombre' => $DirectorNombre,
            'MunicipioEstado' => $MunicipioEstado,
            'TelefonoEscuela' => $TelefonoEscuela,
            'CorreoEscuela' => $CorreoEscuela,
            'LemaInstitucional' => $LemaInstitucional,
            'ColorInstitucional' => $ColorInstitucional,
            'SistemaNombre' => 'SGCE',
            'UrlBaseSistema' => $UrlBaseSistema,
            'ConsultaPublicaAsistenciaLimiteDetalle' => '600',
            'PeriodosCantidad' => (string)$PeriodosCantidad,
            'PeriodosNombreBase' => $PeriodosNombreBase,
            'PeriodosModo' => $PeriodosModo,
            'PeriodosPersonalizados' => implode(PHP_EOL, $PeriodosFinales),
            'UsaPlaneaciones' => $UsaPlaneaciones ? '1' : '0',
            'TipoPlaneacion' => $TipoPlaneacion,
            'PlaneacionesCantidad' => (string)$PlaneacionesCantidad,
            'TurnosDisponibles' => $TurnosDisponiblesTexto,
            'CalificacionMinima' => (string)$CalificacionMinima,
            'CalificacionMaxima' => (string)$CalificacionMaxima,
            'CalificacionAprobatoria' => (string)$CalificacionAprobatoria,
            'CalificacionDecimales' => $CalificacionDecimales,
            'MatriculaAutomatica' => $MatriculaAutomatica,
            'MatriculaPrefijo' => $MatriculaPrefijo,
            'NivelEducativo' => $NivelEducativo,
            'NombreOfertaAcademica' => $NombreOfertaAcademica,
            'TipoPeriodizacion' => $TipoPeriodizacion,
            'TotalEtapas' => (string)$TotalEtapas,
            'UsaProgramas' => $UsaProgramas ? '1' : '0',
            'InstalacionFecha' => date('Y-m-d H:i:s'),
        ]);

        $EtiquetaEtapa = InstalarEtiquetaEtapaPorTipo($TipoPeriodizacion);
        $StmtOferta = $PdoDb->prepare('INSERT INTO OfertasEducativas (Nombre, NivelEducativo, TipoPeriodizacion, TotalEtapas, EtiquetaEtapa, UsaProgramas, Activo) VALUES (?, ?, ?, ?, ?, ?, 1)');
        $StmtOferta->execute([$NombreOfertaAcademica, $NivelEducativo, $TipoPeriodizacion, $TotalEtapas, $EtiquetaEtapa, $UsaProgramas ? 1 : 0]);
        $OfertaId = (int)$PdoDb->lastInsertId();
        $StmtConfigAcademica = $PdoDb->prepare("INSERT INTO ConfiguracionesAcademicas (OfertaId, CantidadPeriodosEvaluacion, NombreBasePeriodo, ModoPeriodos, PeriodosPersonalizados, UsaPlaneaciones, TipoPlaneacion, PlaneacionesCantidad, Activo) VALUES (?, ?, ?, ?, NULLIF(?, ''), ?, ?, ?, 1)");
        $StmtConfigAcademica->execute([$OfertaId, $PeriodosCantidad, $PeriodosNombreBase, $PeriodosModo, implode(PHP_EOL, $PeriodosFinales), $UsaPlaneaciones ? 1 : 0, $TipoPlaneacion, $PlaneacionesCantidad]);
        $StmtEtapa = $PdoDb->prepare('INSERT INTO EtapasAcademicas (OfertaId, Nombre, Orden, EsTerminal, Activo) VALUES (?, ?, ?, ?, 1)');
        for ($OrdenEtapa = 1; $OrdenEtapa <= $TotalEtapas; $OrdenEtapa++) {
            $StmtEtapa->execute([$OfertaId, InstalarEtiquetaEtapa($OrdenEtapa, $TipoPeriodizacion), $OrdenEtapa, $OrdenEtapa === $TotalEtapas ? 1 : 0]);
        }
        $StmtPrograma = $PdoDb->prepare("INSERT IGNORE INTO ProgramasEducativos (OfertaId, Nombre, Clave, Activo) VALUES (?, ?, NULLIF(?, ''), 1)");
        $StmtPrograma->execute([$OfertaId, 'GENERAL', 'GEN']);
        foreach (preg_split('/[,;\n]+/u', $ProgramasIniciales) as $ProgramaInstalar) {
            $ProgramaInstalar = InstalarNormalizarTexto($ProgramaInstalar, true);
            if ($ProgramaInstalar !== '') { $StmtPrograma->execute([$OfertaId, $ProgramaInstalar, '']); }
        }

        $PdoDb->prepare('UPDATE CiclosEscolares SET Activo = 0')->execute();
        $StmtCiclo = $PdoDb->prepare('INSERT INTO CiclosEscolares (Nombre, FechaInicio, FechaFin, Activo) VALUES (?, ?, ?, 1)');
        $StmtCiclo->execute([$CicloNombre, $FechaInicio, $FechaFin]);
        $CicloId = (int)$PdoDb->lastInsertId();
        $StmtPeriodo = $PdoDb->prepare('INSERT INTO PeriodosEvaluacion (CicloId, OfertaId, Nombre, Orden, Activo) VALUES (?, ?, ?, ?, 1)');
        foreach ($PeriodosFinales as $OrdenPeriodo => $NombrePeriodoInstalar) {
            $StmtPeriodo->execute([$CicloId, $OfertaId, $NombrePeriodoInstalar, $OrdenPeriodo + 1]);
        }

        $StmtBitacora = $PdoDb->prepare('INSERT INTO BitacoraMovimientos (UsuarioId, Rol, Accion, TablaAfectada, RegistroId, Detalle, BusquedaTexto, Ip) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $StmtBitacora->execute([$AdminId, 'admin', 'INSTALACION_INICIAL', 'ConfiguracionSistema', null, 'INSTALACIÓN INICIAL DEL SISTEMA', 'INSTALACION INICIAL SISTEMA CONFIGURACION', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
        $PdoDb->commit();

        if (!is_dir(dirname($LockFile))) { @mkdir(dirname($LockFile), 0755, true); }
        if (!is_dir($BackupDir)) { @mkdir($BackupDir, 0755, true); }
        InstalarAsegurarProteccionesIniciales($BackupDir);

        $ConfigExport = "<?php\nreturn [\n" .
            "    'host' => " . var_export($Host, true) . ",\n" .
            "    'database' => " . var_export($BaseDatos, true) . ",\n" .
            "    'username' => " . var_export($UsuarioMysql, true) . ",\n" .
            "    'password' => " . var_export($PasswordMysql, true) . ",\n" .
            "    'charset' => 'utf8mb4',\n" .
            "    'timezone' => 'America/Mexico_City',\n" .
            "    'backup_dir' => " . var_export($BackupDir, true) . ",\n" .
            "    'log_dir' => " . var_export(__DIR__ . '/storage/logs', true) . ",\n" .
            "    'planeaciones_dir' => " . var_export(__DIR__ . '/storage/planeaciones', true) . ",\n" .
            "    'base_url' => " . var_export($UrlBaseSistema, true) . ",\n" .
            "    'force_https' => " . (str_starts_with($UrlBaseSistema, 'https://') ? 'true' : 'false') . ",\n" .
            "    'trusted_proxy_headers' => false,\n" .
            "    'trusted_proxies' => '',\n" .
            "    'production' => true,\n" .
            "];\n";
        InstalarEscribirArchivoSeguro($LocalConfigFile, $ConfigExport);

        $LockOk = file_put_contents($LockFile, 'SGCE INSTALADO: ' . date('Y-m-d H:i:s') . PHP_EOL, LOCK_EX);
        if ($LockOk === false) {
            $Error = error_get_last();
            throw new Exception('La base y la configuración se crearon, pero no se pudo escribir storage/install.lock. Detalle: ' . (($Error['message'] ?? 'sin detalle')));
        }
        InstalarEliminarDirectorio(__DIR__ . '/install', $DetallesEliminacion);
        register_shutdown_function('unlink', __FILE__);

        $InstalacionCompletada = true;
        $Mensaje = 'Instalación completada correctamente. Ya puedes entrar al sistema con el administrador inicial.';
        if ($DetallesEliminacion) { error_log('SGCE instalador: revisar limpieza automática: ' . implode(' | ', $DetallesEliminacion)); }
        $Tipo = 'success';
        $YaInstalado = true;
    } catch (Exception $E) {
        if (isset($PdoDb) && $PdoDb instanceof PDO && $PdoDb->inTransaction()) { $PdoDb->rollBack(); }
        if (!$InstalacionCompletada && $InstalacionEstructuraCreada && isset($PdoInstall) && $PdoInstall instanceof PDO) {
            try { InstalarRollbackInstalacionParcial($PdoInstall); } catch (Throwable $ERollback) { InstalarRegistrarError($ERollback, 'INSTALACION_ROLLBACK'); }
        }
        if (!$InstalacionCompletada) { @unlink($LocalConfigFile); @unlink($LockFile); }
        $CodigoError = InstalarRegistrarError($E, 'INSTALACION');
        if ($E instanceof InstalarMensajeUsuario) {
            $Mensaje = $E->getMessage() . ' Código de seguimiento: ' . $CodigoError;
        } elseif ($E instanceof InstalarErrorSql) {
            $Mensaje = 'Error al crear la estructura de la base de datos: ' . $E->getMessage() . ' Código de seguimiento: ' . $CodigoError;
        } else {
            $Mensaje = InstalarModoDebug()
                ? 'Error al instalar: ' . $E->getMessage()
                : 'No se pudo completar la instalación. Verifica los datos capturados y pulsa Verificar servidor. Código de seguimiento: ' . $CodigoError;
        }
        $Tipo = 'danger';
    }
}
$ChecksInicialesInstalador = (!$YaInstalado) ? InstalarVerificacionesServidor($Valores, false) : [];
$ResumenChecksInstalador = (!$YaInstalado && function_exists('InstalarResumenChecks')) ? InstalarResumenChecks($ChecksInicialesInstalador) : ['ok' => 0, 'warning' => 0, 'error' => 0, 'total' => 0];
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Instalación SGCE</title>
<?= InstalarHeadAssets() ?>
</head>
<body class="SgceBody SgceInstallerPage">
<main class="SgceModuleWrap SgceInstallerMain">
    <section class="Top SgceInstallerHero">
        <div class="TopLeft">
            <div class="IconBox"><span class="SgceColorIcon" aria-hidden="true">🧰</span></div>
            <div>
                <h1>INSTALACIÓN SGCE</h1>
                <p>Configura la escuela, crea el ciclo escolar inicial y registra el administrador principal.</p>
            </div>
        </div>
    </section>

    <?php if ($Mensaje !== ''): ?>
        <div class="alert alert-<?= HInst($Tipo) ?> SgceInstallerAlert border-0 shadow-sm rounded-4 mt-4 fw-semibold" role="alert">
            <div class="SgceInstallerAlertBody">
                <i class="fa-solid <?= $Tipo === 'success' ? 'fa-circle-check' : ($Tipo === 'warning' ? 'fa-triangle-exclamation' : 'fa-circle-info') ?> me-2"></i>
                <span><?= HInst($Mensaje) ?></span>
            </div>
            <button type="button" class="SgceInstallerAlertClose" aria-label="Cerrar mensaje" data-sgce-dismiss>
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    <?php endif; ?>

    <?php if ($YaInstalado): ?>
        <section class="SgcePanel mt-4 p-4 SgceInstallerCard">
            <h2 class="h4 fw-bold text-success"><span class="SgceColorIcon SgceTitleIcon me-2" aria-hidden="true">✅</span>Sistema listo</h2>
            <p class="mb-3">El sistema está listo. Entra desde la pantalla principal con el administrador creado. Por seguridad, el instalador quedó bloqueado; si tu servidor lo permite, elimina el archivo <strong>Instalar.php</strong> después de confirmar el acceso.</p>
            <a href="index.php" class="BtnPrimary text-decoration-none d-inline-flex align-items-center gap-2"><span class="SgceColorIcon" aria-hidden="true">🚪</span> Ir al acceso principal</a>
        </section>
    <?php else: ?>
        <section class="SgcePanel mt-4 p-4 SgceInstallerCard">
            <div class="SgceInstallerTitle">
                <span><span class="SgceColorIcon" aria-hidden="true">⚙️</span></span>
                <div>
                    <h2>Configuración inicial del sistema</h2>
                    <p>Prepara el sistema con los datos oficiales de la escuela, el ciclo escolar inicial y el administrador principal.</p>
                </div>
            </div>
            <div class="SgceInstallerWarning">
                <span class="SgceColorIcon" aria-hidden="true">ℹ️</span>
                <div><strong>Importante:</strong> En local el instalador puede crear la base si el usuario MySQL tiene permiso. En Plesk, crea primero una base exclusiva y vacía desde el panel; SGCE usará esa base para crear las tablas iniciales.</div>
            </div>
            <div class="SgceInstallerCheckPanel" id="SgceInstallerCheckPanel">
                <div class="SgceInstallerCheckHeader">
                    <div>
                        <strong><span class="SgceColorIcon" aria-hidden="true">✅</span> Prediagnóstico del servidor</strong>
                        <p>Revisa requisitos de PHP, permisos, temporales, subida de archivos y conexión antes de instalar.</p>
                    </div>
                    <div class="SgceInstallerCheckSummary" aria-label="Resumen del prediagnóstico">
                        <span class="SgceCheckOk">OK: <?= (int)$ResumenChecksInstalador['ok'] ?></span>
                        <span class="SgceCheckWarning">Avisos: <?= (int)$ResumenChecksInstalador['warning'] ?></span>
                        <span class="SgceCheckError">Errores: <?= (int)$ResumenChecksInstalador['error'] ?></span>
                    </div>
                </div>
                <div class="SgceInstallerCheckActions">
                    <button type="button" class="SgceInstallerDetailsBtn" id="SgceInstallerDetailsBtn" aria-expanded="false" aria-controls="SgceInstallerCheckResults"><i class="fa-solid fa-list-check"></i> Ver detalles</button>
                    <button type="button" class="BtnPrimary SgceInstallerVerifyBtn" id="SgceInstallerVerifyBtn"><span class="SgceColorIcon" aria-hidden="true">🛡️</span> Verificar servidor y MySQL</button>
                </div>
                <div class="SgceInstallerCheckResults IsPreloaded" id="SgceInstallerCheckResults" hidden>
                    <?php foreach ($ChecksInicialesInstalador as $CheckInstalador): ?>
                        <div class="SgceInstallerCheckItem SgceInstallerCheck<?= HInst(strtoupper((string)($CheckInstalador['estado'] ?? 'warning'))) ?>">
                            <i class="fa-solid <?= ($CheckInstalador['estado'] ?? '') === 'ok' ? 'fa-circle-check' : (($CheckInstalador['estado'] ?? '') === 'error' ? 'fa-circle-xmark' : 'fa-triangle-exclamation') ?>"></i>
                            <div>
                                <strong><?= HInst($CheckInstalador['titulo'] ?? 'Verificación') ?></strong>
                                <p><?= HInst($CheckInstalador['detalle'] ?? '') ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <form method="post" class="row g-3 mt-2" id="SgceInstallerForm">
                <?= InstalarCampoCsrf() ?>
                <div class="col-12"><h3 class="SgceInstallerSectionTitle"><span class="SgceColorIcon" aria-hidden="true">🗄️</span> Conexión MySQL</h3></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Host SQL</label><input class="form-control FormControl" name="Host" value="<?= HInst($Valores['Host']) ?>" required placeholder="Localhost"></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Base de datos</label><input class="form-control FormControl" name="BaseDatos" value="<?= HInst($Valores['BaseDatos']) ?>" required maxlength="64" pattern="[A-Za-z0-9_]{1,64}" title="Solo letras, números y guion bajo." placeholder="Base De Datos"></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Usuario SQL</label><input class="form-control FormControl" name="UsuarioMysql" value="<?= HInst($Valores['UsuarioMysql']) ?>" required placeholder="Usuario SQL"></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Contraseña SQL</label><input class="form-control FormControl" type="password" name="PasswordMysql" value="<?= HInst($Valores['PasswordMysql']) ?>"></div>
                <div class="col-12"><label class="fw-bold mb-2">Carpeta de respaldos</label><input class="form-control FormControl" name="BackupDir" value="<?= HInst($Valores['BackupDir']) ?>" required></div>
                <div class="col-12"><label class="fw-bold mb-2">URL base del sistema</label><input class="form-control FormControl" type="url" name="UrlBaseSistema" value="<?= HInst($Valores['UrlBaseSistema']) ?>" required placeholder="https://sgce.tu-dominio.com/"><small class="text-muted fw-semibold">Déjala como la detecta el instalador o ajústala si trabajas en subcarpeta, Plesk o proxy.</small></div>

                <div class="col-12"><h3 class="SgceInstallerSectionTitle"><span class="SgceColorIcon" aria-hidden="true">🏫</span> Datos oficiales de la escuela</h3></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Nombre oficial de la escuela</label><input class="form-control FormControl InputUpper" name="NombreEscuela" value="<?= HInst($Valores['NombreEscuela']) ?>" required minlength="3" maxlength="150" placeholder="Nombre de la escuela"></div>
                <div class="col-md-6"><label class="fw-bold mb-2">CCT / Clave</label><input class="form-control FormControl InputUpper" name="ClaveCentroTrabajo" value="<?= HInst($Valores['ClaveCentroTrabajo']) ?>" maxlength="30" pattern="[A-Z0-9\-]{0,30}" title="Solo letras, números o guion." placeholder="Opcional"></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Director(a)</label><input class="form-control FormControl InputUpper" name="DirectorNombre" value="<?= HInst($Valores['DirectorNombre']) ?>" maxlength="120" pattern="[A-ZÁÉÍÓÚÜÑ .'-]*" title="Solo letras y espacios." placeholder="Opcional"></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Municipio y estado</label><input class="form-control FormControl InputUpper" name="MunicipioEstado" value="<?= HInst($Valores['MunicipioEstado']) ?>" maxlength="120" placeholder="Opcional"></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Teléfono</label><input class="form-control FormControl InputDigits" type="tel" name="TelefonoEscuela" value="<?= HInst($Valores['TelefonoEscuela']) ?>" inputmode="numeric" autocomplete="tel" minlength="7" maxlength="15" pattern="\d{7,15}" title="Solo números, mínimo 7 y máximo 15 dígitos." placeholder="Opcional"></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Correo institucional</label><input class="form-control FormControl" type="email" name="CorreoEscuela" value="<?= HInst($Valores['CorreoEscuela']) ?>" maxlength="120" autocomplete="email" placeholder="Opcional"></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Lema o leyenda inferior</label><input class="form-control FormControl" name="LemaInstitucional" value="<?= HInst($Valores['LemaInstitucional'] ?? '') ?>" maxlength="180" placeholder="Opcional"></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Color institucional</label><div class="SgceColorControl"><input class="form-control FormControl" type="color" name="ColorInstitucional" id="ColorInstitucional" value="<?= HInst($Valores['ColorInstitucional'] ?: '#97051E') ?>"><span id="ColorInstitucionalTexto"><?= HInst($Valores['ColorInstitucional'] ?: '#97051E') ?></span></div></div>

                <div class="col-12"><h3 class="SgceInstallerSectionTitle"><span class="SgceColorIcon" aria-hidden="true">🧭</span> Configuración académica inicial</h3></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Nivel educativo</label><select class="form-select FormControl" name="NivelEducativo" required>
                    <?php foreach(['PRIMARIA'=>'Primaria','SECUNDARIA'=>'Secundaria','BACHILLERATO'=>'Bachillerato / Preparatoria','UNIVERSIDAD'=>'Universidad / Licenciatura','MAESTRIA'=>'Maestría','DOCTORADO'=>'Doctorado','CURSO'=>'Curso / Diplomado'] as $ClaveNivel=>$TextoNivel): ?>
                    <option value="<?= HInst($ClaveNivel) ?>" <?= InstalarNivelValido($Valores['NivelEducativo']) === $ClaveNivel ? 'selected' : '' ?>><?= HInst($TextoNivel) ?></option>
                    <?php endforeach; ?>
                </select><small class="text-muted fw-semibold">Define la lógica académica principal.</small></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Nombre específico de la oferta educativa <span class="text-muted">(opcional)</span></label><input class="form-control FormControl InputUpper" name="NombreOfertaAcademica" value="<?= HInst($Valores['NombreOfertaAcademica']) ?>" maxlength="140" placeholder="Ej. Secundaria Técnica / Bachillerato Tecnológico"><small class="text-muted fw-semibold">Si lo dejas vacío, SGCE usará el nivel educativo como nombre.</small></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Organización académica</label><select class="form-select FormControl" name="TipoPeriodizacion" required>
                    <?php foreach(['ANUAL'=>'Años / grados','SEMESTRAL'=>'Semestres','CUATRIMESTRAL'=>'Cuatrimestres','TRIMESTRAL'=>'Trimestres','MODULAR'=>'Módulos / niveles'] as $ClaveTipo=>$TextoTipo): ?>
                    <option value="<?= HInst($ClaveTipo) ?>" <?= InstalarTipoPeriodizacionValido($Valores['TipoPeriodizacion']) === $ClaveTipo ? 'selected' : '' ?>><?= HInst($TextoTipo) ?></option>
                    <?php endforeach; ?>
                </select></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Cantidad de etapas académicas</label><input class="form-control FormControl InputDigits" name="TotalEtapas" value="<?= HInst($Valores['TotalEtapas']) ?>" required min="1" max="20" maxlength="2" inputmode="numeric" placeholder="Ej. 3, 6, 8"></div>
                <?php $ProgramasHabilitados = !empty($Valores['UsaProgramas']) || InstalarRequiereProgramasEducativos($Valores['NivelEducativo'] ?? 'SECUNDARIA'); ?>
                <div class="col-12 SgceInstallerToggleWrap"><label class="fw-bold mb-2 SgceInstallerToggleSpacer" aria-hidden="true">Opción</label><label class="SgceInstallerToggle"><input class="form-check-input" type="checkbox" name="UsaProgramas" value="1" <?= $ProgramasHabilitados ? 'checked' : '' ?> <?= InstalarRequiereProgramasEducativos($Valores['NivelEducativo'] ?? 'SECUNDARIA') ? 'disabled' : '' ?>><span class="SgceInstallerToggleText"><strong>Usa programas educativos</strong><small>Programas, especialidades o posgrados</small></span></label></div>
                <div class="col-12"><label class="fw-bold mb-2">Programas educativos o programas iniciales</label><textarea class="form-control FormControl InputUpper SgceProgramasDependiente" name="ProgramasIniciales" rows="2" placeholder="Ej. Informática, Contabilidad, Enfermería" <?= $ProgramasHabilitados ? '' : 'disabled' ?>><?= HInst($Valores['ProgramasIniciales']) ?></textarea><small class="text-muted fw-semibold SgceProgramasHelp <?= $ProgramasHabilitados ? '' : 'SgceMuted' ?>">Activa “Usa programas educativos” para capturar programas. En universidad, maestría y doctorado es obligatorio.</small></div>

                <div class="col-12"><h3 class="SgceInstallerSectionTitle"><span class="SgceColorIcon" aria-hidden="true">🧩</span> Parámetros multinivel</h3></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Turnos disponibles</label><textarea class="form-control FormControl InputUpper" name="TurnosDisponibles" rows="4" placeholder="MATUTINO
VESPERTINO
NOCTURNO
SABATINO"><?= HInst($Valores['TurnosDisponibles']) ?></textarea><small class="text-muted fw-semibold">Escribe un turno por línea. Ejemplo: MATUTINO, VESPERTINO, NOCTURNO, SABATINO, EN LÍNEA o SIN TURNO.</small></div>
                <div class="col-md-6"><div class="row g-3">
                    <div class="col-md-4"><label class="fw-bold mb-2 SgceInstallerFieldLabel">Calificación mínima</label><input class="form-control FormControl" type="number" step="0.01" min="0" max="100" name="CalificacionMinima" value="<?= HInst($Valores['CalificacionMinima']) ?>" required></div>
                    <div class="col-md-4"><label class="fw-bold mb-2 SgceInstallerFieldLabel">Calificación aprobatoria</label><input class="form-control FormControl" type="number" step="0.01" min="0" max="100" name="CalificacionAprobatoria" value="<?= HInst($Valores['CalificacionAprobatoria']) ?>" required></div>
                    <div class="col-md-4"><label class="fw-bold mb-2 SgceInstallerFieldLabel">Calificación máxima</label><input class="form-control FormControl" type="number" step="0.01" min="0" max="100" name="CalificacionMaxima" value="<?= HInst($Valores['CalificacionMaxima']) ?>" required></div>
                    <div class="col-md-6"><input type="hidden" name="CalificacionDecimales" value="0"><label class="SgceInstallerToggle"><input class="form-check-input" type="checkbox" name="CalificacionDecimales" value="1" <?= !empty($Valores['CalificacionDecimales']) ? 'checked' : '' ?>><span class="SgceInstallerToggleText"><strong>Permitir decimales</strong><small>Ej. 8.5 o 92.75</small></span></label></div>
                    <div class="col-md-6"><input type="hidden" name="MatriculaAutomatica" value="0"><label class="SgceInstallerToggle"><input class="form-check-input" type="checkbox" name="MatriculaAutomatica" id="SgceInstallerMatriculaAutomatica" value="1" <?= !empty($Valores['MatriculaAutomatica']) ? 'checked' : '' ?>><span class="SgceInstallerToggleText"><strong>Matrícula automática</strong><small>Si no se captura, SGCE genera una</small></span></label></div>
                    <div class="col-md-6"><label class="fw-bold mb-2">Prefijo de matrícula</label><input class="form-control FormControl InputUpperAscii SgceMatriculaDependiente" name="MatriculaPrefijo" value="<?= HInst($Valores['MatriculaPrefijo']) ?>" maxlength="12" pattern="[A-Z0-9]{2,12}" placeholder="SGCE" data-sgce-matricula-campo="1"></div>
                    <div class="col-md-6"><label class="fw-bold mb-2">Formato generado</label><input class="form-control FormControl SgceMatriculaDependiente" id="SgceInstallerMatriculaEjemplo" value="<?= HInst((string)$Valores['MatriculaPrefijo'] . '-' . date('Y') . '-000001') ?>" readonly data-sgce-matricula-campo="1"><small class="text-muted fw-semibold SgceMatriculaHelp">Solo se usa si Matrícula automática está activada.</small></div>
                </div></div>

                <div class="col-12"><h3 class="SgceInstallerSectionTitle"><span class="SgceColorIcon" aria-hidden="true">📅</span> Ciclo, periodos y planeaciones</h3></div>
                <div class="col-md-4"><label class="fw-bold mb-2">Nombre del ciclo</label><input class="form-control FormControl InputUpper" name="CicloNombre" value="<?= HInst($Valores['CicloNombre']) ?>" required maxlength="40" placeholder="Ej. 2026-2027 / 2026-A"></div>
                <div class="col-md-4"><label class="fw-bold mb-2">Fecha de inicio</label><input class="form-control FormControl" type="date" name="FechaInicio" value="<?= HInst($Valores['FechaInicio']) ?>" required></div>
                <div class="col-md-4"><label class="fw-bold mb-2">Fecha de fin</label><input class="form-control FormControl" type="date" name="FechaFin" value="<?= HInst($Valores['FechaFin']) ?>" required></div>
                <div class="col-md-4"><label class="fw-bold mb-2">Cantidad de periodos de evaluación</label><input class="form-control FormControl InputDigits" name="PeriodosCantidad" value="<?= HInst($Valores['PeriodosCantidad']) ?>" required min="1" max="12" maxlength="2" inputmode="numeric" placeholder="Ej. 3"></div>
                <div class="col-md-4"><label class="fw-bold mb-2">Nombre base del periodo</label><input class="form-control FormControl InputUpper" name="PeriodosNombreBase" value="<?= HInst($Valores['PeriodosNombreBase']) ?>" required maxlength="60" placeholder="Parcial / Trimestre / Unidad"></div>
                <div class="col-md-4"><label class="fw-bold mb-2">Modo de periodos</label><select class="form-select FormControl" name="PeriodosModo" id="SgceInstallerPeriodosModo"><option value="AUTOMATICO" <?= ($Valores['PeriodosModo'] ?? '') === 'AUTOMATICO' ? 'selected' : '' ?>>Automático</option><option value="PERSONALIZADO" <?= ($Valores['PeriodosModo'] ?? '') === 'PERSONALIZADO' ? 'selected' : '' ?>>Personalizado</option></select></div>
                <div class="col-12"><label class="fw-bold mb-2">Periodos personalizados</label><textarea class="form-control FormControl InputUpper SgcePeriodosPersonalizadosDependiente" name="PeriodosPersonalizados" rows="2" placeholder="Opcional. Ej. Parcial 1, Parcial 2, Ordinario, Extraordinario" <?= (($Valores['PeriodosModo'] ?? 'AUTOMATICO') === 'PERSONALIZADO') ? '' : 'disabled' ?>><?= HInst((($Valores['PeriodosModo'] ?? 'AUTOMATICO') === 'PERSONALIZADO') ? $Valores['PeriodosPersonalizados'] : '') ?></textarea><small class="text-muted fw-semibold SgcePeriodosPersonalizadosHelp <?= (($Valores['PeriodosModo'] ?? 'AUTOMATICO') === 'PERSONALIZADO') ? '' : 'SgceMuted' ?>">Solo se captura cuando el modo de periodos está en personalizado.</small></div>
                <?php $PlaneacionesHabilitadas = !empty($Valores['UsaPlaneaciones']); ?>
                <div class="col-md-4 SgceInstallerToggleWrap"><label class="fw-bold mb-2 SgceInstallerToggleSpacer" aria-hidden="true">Opción</label><label class="SgceInstallerToggle"><input type="hidden" name="UsaPlaneaciones" value="0"><input class="form-check-input" type="checkbox" name="UsaPlaneaciones" value="1" <?= $PlaneacionesHabilitadas ? 'checked' : '' ?>><span class="SgceInstallerToggleText"><strong>Usa planeaciones</strong><small>Control de entregas docentes</small></span></label></div>
                <div class="col-md-4"><label class="fw-bold mb-2">Tipo de planeación</label><select class="form-select FormControl SgcePlaneacionesDependiente" name="TipoPlaneacion" data-sgce-planeacion-campo="1" <?= $PlaneacionesHabilitadas ? '' : 'disabled' ?>><option value="CICLO" <?= ($Valores['TipoPlaneacion'] ?? '') === 'CICLO' ? 'selected' : '' ?>>Por ciclo</option><option value="PERIODO" <?= ($Valores['TipoPlaneacion'] ?? '') === 'PERIODO' ? 'selected' : '' ?>>Por periodo de evaluación</option><option value="UNIDAD" <?= ($Valores['TipoPlaneacion'] ?? '') === 'UNIDAD' ? 'selected' : '' ?>>Por unidad/tema</option><option value="SEMANA" <?= ($Valores['TipoPlaneacion'] ?? '') === 'SEMANA' ? 'selected' : '' ?>>Semanal</option></select></div>
                <div class="col-md-4"><label class="fw-bold mb-2">Planeaciones a entregar</label><input class="form-control FormControl InputDigits SgcePlaneacionesDependiente" name="PlaneacionesCantidad" data-sgce-planeacion-campo="1" value="<?= HInst($Valores['PlaneacionesCantidad']) ?>" min="1" max="12" maxlength="2" inputmode="numeric" placeholder="Ej. 6" <?= $PlaneacionesHabilitadas ? '' : 'disabled' ?>><small class="text-muted fw-semibold SgcePlaneacionesHelp <?= $PlaneacionesHabilitadas ? '' : 'SgceMuted' ?>">Se solicitará la cantidad configurada de planeaciones por materia durante el ciclo escolar.</small></div>

                <div class="col-12"><h3 class="SgceInstallerSectionTitle"><span class="SgceColorIcon" aria-hidden="true">👤</span> Administrador inicial</h3></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Nombre del administrador</label><input class="form-control FormControl InputUpper" name="AdminNombre" value="<?= HInst($Valores['AdminNombre']) ?>" required minlength="3" maxlength="120" pattern="[A-ZÁÉÍÓÚÜÑ .'-]+" title="Solo letras y espacios." placeholder="NOMBRE COMPLETO"></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Usuario administrador</label><input class="form-control FormControl" name="AdminUsuario" value="<?= HInst($Valores['AdminUsuario']) ?>" required minlength="3" maxlength="80" pattern="[A-Za-z0-9._@-]{3,80}" autocomplete="username" placeholder="Usuario"></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Contraseña administrador</label><input class="form-control FormControl" type="password" name="AdminPassword" required minlength="8" autocomplete="new-password" placeholder="Mínimo 8, mayúscula, minúscula, número y símbolo"></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Repetir contraseña</label><input class="form-control FormControl" type="password" name="AdminPasswordConfirm" required minlength="8" autocomplete="new-password" placeholder="Repite la contraseña del administrador"></div>
                <div class="col-12"><label class="fw-bold mb-2">Confirmación</label><input class="form-control FormControl" name="ConfirmarInstalacion" placeholder="INSTALAR SGCE" required></div>
                <div class="col-12"><button type="submit" class="BtnPrimary SgceInstallerBtn border-0"><span class="SgceColorIcon" aria-hidden="true">✨</span> Instalar sistema</button></div>
            </form>
        </section>
    <?php endif; ?>
</main>
<?= InstalarFooterAssets() ?>
</body>
</html>
