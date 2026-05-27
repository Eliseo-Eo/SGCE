<?php
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

$Mensaje = '';
$Tipo = 'info';
$SqlFile = __DIR__ . '/install/ControlEscolar.sql';
$LockFile = __DIR__ . '/storage/install.lock';
$LocalConfigFile = __DIR__ . '/config/database.local.php';

function HInst($Texto) { return htmlspecialchars((string)$Texto, ENT_QUOTES, 'UTF-8'); }

function InstalarModoDebug() {
    return getenv('SGCE_DEBUG_INSTALLER') === '1';
}

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

function InstalarNombreBaseValido($Nombre) {
    return preg_match('/^[A-Za-z0-9_]{1,64}$/', (string)$Nombre) === 1;
}

function InstalarNormalizarTexto($Texto, $Mayusculas = false) {
    $Texto = trim(preg_replace('/\s+/u', ' ', (string)$Texto));
    return $Mayusculas ? mb_strtoupper($Texto, 'UTF-8') : $Texto;
}

function InstalarValidarFecha($Fecha) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$Fecha)) { return false; }
    $D = DateTime::createFromFormat('Y-m-d', (string)$Fecha);
    return $D && $D->format('Y-m-d') === $Fecha;
}

function InstalarSoloLetrasEspacios($Texto) {
    return preg_match('/^[\p{L} .\'-]+$/u', (string)$Texto) === 1;
}

function InstalarValidarTelefonoOpcional($Telefono) {
    $Telefono = trim((string)$Telefono);
    if ($Telefono === '') { return true; }
    if (!preg_match('/^\d{7,15}$/', $Telefono)) {
        return 'El teléfono debe contener solo números, mínimo 7 y máximo 15 dígitos.';
    }
    return true;
}

function InstalarValidarCorreoOpcional($Correo) {
    $Correo = trim((string)$Correo);
    if ($Correo === '') { return true; }
    if (strlen($Correo) > 120 || filter_var($Correo, FILTER_VALIDATE_EMAIL) === false || strpos($Correo, '@') === false || strpos($Correo, '.') === false) {
        return 'El correo institucional debe tener formato válido, por ejemplo direccion@escuela.com.';
    }
    return true;
}

function InstalarValidarTextoOpcional($Valor, $Campo, $Maximo = 120, $SoloLetras = false) {
    $Valor = trim((string)$Valor);
    if ($Valor === '') { return true; }
    if (mb_strlen($Valor, 'UTF-8') > $Maximo) { return $Campo . ' no debe superar ' . $Maximo . ' caracteres.'; }
    if ($SoloLetras && !InstalarSoloLetrasEspacios($Valor)) { return $Campo . ' solo debe contener letras, espacios, puntos, guiones o apóstrofes.'; }
    return true;
}

function InstalarFormatoPermisos($Path) {
    if (!file_exists($Path)) { return 'NO EXISTE'; }
    $Permisos = substr(sprintf('%o', fileperms($Path)), -4);
    $Propietario = function_exists('posix_getpwuid') ? (posix_getpwuid(fileowner($Path))['name'] ?? fileowner($Path)) : fileowner($Path);
    $Grupo = function_exists('posix_getgrgid') ? (posix_getgrgid(filegroup($Path))['name'] ?? filegroup($Path)) : filegroup($Path);
    return $Permisos . ' ' . $Propietario . ':' . $Grupo;
}

function InstalarUsuarioPhp() {
    if (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
        $Info = posix_getpwuid(posix_geteuid());
        if (is_array($Info) && !empty($Info['name'])) { return $Info['name']; }
    }
    return get_current_user();
}

function InstalarVerificarEscritura($RutaArchivo) {
    $Dir = dirname($RutaArchivo);
    if (!is_dir($Dir)) {
        if (!mkdir($Dir, 0775, true) && !is_dir($Dir)) {
            throw new Exception('No se pudo crear la carpeta de configuración: ' . $Dir);
        }
    }
    $RealDir = realpath($Dir) ?: $Dir;
    if (!is_writable($Dir)) {
        throw new Exception('La carpeta config no tiene permisos de escritura para PHP. Ruta: ' . $RealDir . ' | Permisos: ' . InstalarFormatoPermisos($Dir) . ' | Usuario PHP: ' . InstalarUsuarioPhp());
    }
    $Prueba = $Dir . '/.sgce_write_test_' . bin2hex(random_bytes(4));
    $Ok = file_put_contents($Prueba, 'ok', LOCK_EX);
    if ($Ok === false) {
        $Error = error_get_last();
        throw new Exception('PHP no pudo escribir archivo de prueba en config. Ruta: ' . $RealDir . ' | Usuario PHP: ' . InstalarUsuarioPhp() . ' | Detalle: ' . (($Error['message'] ?? 'sin detalle')));
    }
    @unlink($Prueba);
    return true;
}

function InstalarEscribirArchivoSeguro($RutaArchivo, $Contenido) {
    InstalarVerificarEscritura($RutaArchivo);
    $Dir = dirname($RutaArchivo);
    $Tmp = $Dir . '/.' . basename($RutaArchivo) . '.tmp.' . bin2hex(random_bytes(4));
    $Bytes = file_put_contents($Tmp, $Contenido, LOCK_EX);
    if ($Bytes === false) {
        $Error = error_get_last();
        throw new Exception('No se pudo escribir archivo temporal de configuración. Ruta: ' . $Tmp . ' | Usuario PHP: ' . InstalarUsuarioPhp() . ' | Detalle: ' . (($Error['message'] ?? 'sin detalle')));
    }
    @chmod($Tmp, 0640);
    if (!rename($Tmp, $RutaArchivo)) {
        $Error = error_get_last();
        @unlink($Tmp);
        throw new Exception('No se pudo guardar config/database.local.php. Ruta destino: ' . $RutaArchivo . ' | Permisos config: ' . InstalarFormatoPermisos($Dir) . ' | Usuario PHP: ' . InstalarUsuarioPhp() . ' | Detalle: ' . (($Error['message'] ?? 'sin detalle')));
    }
    @chmod($RutaArchivo, 0640);
    return true;
}

function InstalarEliminarDirectorio($Dir, &$Detalles) {
    if (!is_dir($Dir)) { return true; }
    $Ok = true;
    foreach (scandir($Dir) ?: [] as $Item) {
        if ($Item === '.' || $Item === '..') { continue; }
        $Path = $Dir . DIRECTORY_SEPARATOR . $Item;
        if (is_dir($Path)) { $Ok = InstalarEliminarDirectorio($Path, $Detalles) && $Ok; }
        elseif (!@unlink($Path)) { $Detalles[] = 'No se pudo eliminar: ' . $Path; $Ok = false; }
    }
    if (!@rmdir($Dir)) { $Detalles[] = 'No se pudo eliminar carpeta: ' . $Dir; $Ok = false; }
    return $Ok;
}

function InstalarGuardarConfiguracion($PdoDb, $Datos) {
    $Stmt = $PdoDb->prepare('INSERT INTO ConfiguracionSistema (Clave, Valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE Valor = VALUES(Valor), FechaActualizacion = CURRENT_TIMESTAMP');
    foreach ($Datos as $Clave => $Valor) {
        $Stmt->execute([$Clave, $Valor]);
    }
}

$YaInstalado = is_file($LockFile);

$AnioActual = (int)date('Y');
$Valores = [
    'Host' => $_POST['Host'] ?? 'localhost',
    'BaseDatos' => $_POST['BaseDatos'] ?? 'ControlEscolar',
    'UsuarioMysql' => $_POST['UsuarioMysql'] ?? 'root',
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
    'CicloNombre' => $_POST['CicloNombre'] ?? ($AnioActual . '-' . ($AnioActual + 1)),
    'FechaInicio' => $_POST['FechaInicio'] ?? ($AnioActual . '-08-01'),
    'FechaFin' => $_POST['FechaFin'] ?? (($AnioActual + 1) . '-07-31'),
    'PeriodoUno' => $_POST['PeriodoUno'] ?? 'PRIMER PARCIAL',
    'PeriodoDos' => $_POST['PeriodoDos'] ?? 'SEGUNDO PARCIAL',
    'PeriodoTres' => $_POST['PeriodoTres'] ?? 'TERCER PARCIAL',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$YaInstalado) {
    $DetallesEliminacion = [];
    try {
        if (($_POST['ConfirmarInstalacion'] ?? '') !== 'INSTALAR SGCE') {
            throw new Exception('Confirmación inválida. Escribe exactamente INSTALAR SGCE.');
        }
        if (!is_file($SqlFile)) { throw new Exception('No se encontró install/ControlEscolar.sql.'); }

        $Host = trim((string)$Valores['Host']);
        $BaseDatos = trim((string)$Valores['BaseDatos']);
        $UsuarioMysql = trim((string)$Valores['UsuarioMysql']);
        $PasswordMysql = (string)$Valores['PasswordMysql'];
        $AdminNombre = InstalarNormalizarTexto($Valores['AdminNombre'], true);
        $AdminUsuario = trim((string)$Valores['AdminUsuario']);
        $AdminPassword = (string)($_POST['AdminPassword'] ?? '');
        $BackupDir = trim((string)$Valores['BackupDir']);

        $NombreEscuela = InstalarNormalizarTexto($Valores['NombreEscuela'], true);
        $ClaveCentroTrabajo = InstalarNormalizarTexto($Valores['ClaveCentroTrabajo'], true);
        $DirectorNombre = InstalarNormalizarTexto($Valores['DirectorNombre'], true);
        $MunicipioEstado = InstalarNormalizarTexto($Valores['MunicipioEstado'], true);
        $TelefonoEscuela = InstalarNormalizarTexto($Valores['TelefonoEscuela']);
        $CorreoEscuela = InstalarNormalizarTexto($Valores['CorreoEscuela']);
        $CicloNombre = InstalarNormalizarTexto($Valores['CicloNombre'], true);
        $FechaInicio = trim((string)$Valores['FechaInicio']);
        $FechaFin = trim((string)$Valores['FechaFin']);
        $PeriodoUno = InstalarNormalizarTexto($Valores['PeriodoUno'], true);
        $PeriodoDos = InstalarNormalizarTexto($Valores['PeriodoDos'], true);
        $PeriodoTres = InstalarNormalizarTexto($Valores['PeriodoTres'], true);

        if ($Host === '' || $UsuarioMysql === '' || $BaseDatos === '' || !InstalarNombreBaseValido($BaseDatos)) {
            throw new Exception('Revisa host, usuario MySQL y nombre de base de datos. La base solo puede usar letras, números y guion bajo.');
        }
        if ($NombreEscuela === '' || mb_strlen($NombreEscuela, 'UTF-8') < 3 || mb_strlen($NombreEscuela, 'UTF-8') > 150) {
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
        if ($AdminNombre === '' || mb_strlen($AdminNombre, 'UTF-8') < 3 || !InstalarSoloLetrasEspacios($AdminNombre)) {
            throw new Exception('Escribe el nombre del administrador. Solo debe contener letras y espacios.');
        }
        if ($AdminUsuario === '' || !preg_match('/^[a-zA-Z0-9._@-]{3,80}$/', $AdminUsuario)) {
            throw new Exception('Revisa el usuario administrador. Debe tener mínimo 3 caracteres y acepta letras, números, punto, guion, guion bajo o @.');
        }
        $ValidacionPassword = InstalarValidarPassword($AdminPassword);
        if ($ValidacionPassword !== true) { throw new Exception($ValidacionPassword); }
        if ($CicloNombre === '' || !InstalarValidarFecha($FechaInicio) || !InstalarValidarFecha($FechaFin) || strtotime($FechaInicio) >= strtotime($FechaFin)) {
            throw new Exception('Revisa el ciclo escolar. Debe tener nombre, fecha de inicio y fecha de fin válida.');
        }
        if ($PeriodoUno === '' || $PeriodoDos === '' || $PeriodoTres === '') {
            throw new Exception('Los tres periodos de evaluación son obligatorios.');
        }
        if (count(array_unique([$PeriodoUno, $PeriodoDos, $PeriodoTres])) !== 3) {
            throw new Exception('Los nombres de los tres periodos no pueden repetirse.');
        }

        // Validación previa: el servidor debe poder guardar la configuración antes de iniciar la base.
        InstalarVerificarEscritura($LocalConfigFile);
        if (!is_dir(dirname($LockFile))) { @mkdir(dirname($LockFile), 0775, true); }
        if (!is_writable(dirname($LockFile))) {
            throw new Exception('La carpeta storage no tiene permisos de escritura para crear install.lock. Ruta: ' . dirname($LockFile) . ' | Permisos: ' . InstalarFormatoPermisos(dirname($LockFile)) . ' | Usuario PHP: ' . InstalarUsuarioPhp());
        }

        $DsnServidor = 'mysql:host=' . $Host . ';charset=utf8mb4';
        $PdoInstall = new PDO($DsnServidor, $UsuarioMysql, $PasswordMysql, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        $Sql = file_get_contents($SqlFile);
        if ($Sql === false || trim($Sql) === '') { throw new Exception('El SQL de instalación está vacío.'); }
        $Sql = str_replace('{{SGCE_DB_NAME}}', str_replace('`', '``', $BaseDatos), $Sql);
        foreach (InstalarSepararSql($Sql) as $Sentencia) { $PdoInstall->exec($Sentencia); }

        $DsnDb = 'mysql:host=' . $Host . ';dbname=' . $BaseDatos . ';charset=utf8mb4';
        $PdoDb = new PDO($DsnDb, $UsuarioMysql, $PasswordMysql, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        $PdoDb->beginTransaction();
        $StmtAdmin = $PdoDb->prepare("INSERT INTO Usuarios (Username, Password, NombreCompleto, Rol, Activo) VALUES (?, ?, ?, 'admin', 1)");
        $StmtAdmin->execute([$AdminUsuario, password_hash($AdminPassword, PASSWORD_DEFAULT), $AdminNombre]);
        $AdminId = (int)$PdoDb->lastInsertId();

        InstalarGuardarConfiguracion($PdoDb, [
            'NombreEscuela' => $NombreEscuela,
            'ClaveCentroTrabajo' => $ClaveCentroTrabajo,
            'DirectorNombre' => $DirectorNombre,
            'MunicipioEstado' => $MunicipioEstado,
            'TelefonoEscuela' => $TelefonoEscuela,
            'CorreoEscuela' => $CorreoEscuela,
            'LemaInstitucional' => '',
            'ColorInstitucional' => '#7A0818',
            'SistemaNombre' => 'SGCE',
            'InstalacionFecha' => date('Y-m-d H:i:s'),
        ]);

        $PdoDb->prepare('UPDATE CiclosEscolares SET Activo = 0')->execute();
        $StmtCiclo = $PdoDb->prepare('INSERT INTO CiclosEscolares (Nombre, FechaInicio, FechaFin, Activo) VALUES (?, ?, ?, 1)');
        $StmtCiclo->execute([$CicloNombre, $FechaInicio, $FechaFin]);
        $CicloId = (int)$PdoDb->lastInsertId();
        $StmtPeriodo = $PdoDb->prepare('INSERT INTO PeriodosEvaluacion (CicloId, Nombre, Orden, Activo) VALUES (?, ?, ?, 1)');
        $StmtPeriodo->execute([$CicloId, $PeriodoUno, 1]);
        $StmtPeriodo->execute([$CicloId, $PeriodoDos, 2]);
        $StmtPeriodo->execute([$CicloId, $PeriodoTres, 3]);

        $StmtBitacora = $PdoDb->prepare('INSERT INTO BitacoraMovimientos (UsuarioId, Rol, Accion, TablaAfectada, RegistroId, Detalle, Ip) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $StmtBitacora->execute([$AdminId, 'admin', 'INSTALACION_INICIAL', 'ConfiguracionSistema', null, 'INSTALACIÓN FINAL DESDE CERO', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
        $PdoDb->commit();

        if (!is_dir(dirname($LockFile))) { @mkdir(dirname($LockFile), 0755, true); }
        if (!is_dir($BackupDir)) { @mkdir($BackupDir, 0755, true); }

        $ConfigExport = "<?php\nreturn [\n" .
            "    'host' => " . var_export($Host, true) . ",\n" .
            "    'database' => " . var_export($BaseDatos, true) . ",\n" .
            "    'username' => " . var_export($UsuarioMysql, true) . ",\n" .
            "    'password' => " . var_export($PasswordMysql, true) . ",\n" .
            "    'charset' => 'utf8mb4',\n" .
            "    'timezone' => 'America/Mexico_City',\n" .
            "    'backup_dir' => " . var_export($BackupDir, true) . ",\n" .
            "    'production' => true,\n" .
            "];\n";
        InstalarEscribirArchivoSeguro($LocalConfigFile, $ConfigExport);

        $LockOk = file_put_contents($LockFile, 'SGCE INSTALADO: ' . date('Y-m-d H:i:s') . PHP_EOL, LOCK_EX);
        if ($LockOk === false) {
            $Error = error_get_last();
            throw new Exception('La base y la configuración se crearon, pero no se pudo escribir storage/install.lock. Detalle: ' . (($Error['message'] ?? 'sin detalle')));
        }
        InstalarEliminarDirectorio(__DIR__ . '/install', $DetallesEliminacion);
        register_shutdown_function(function(){ @unlink(__FILE__); });

        $Mensaje = 'Instalación completada correctamente. Ya puedes entrar al sistema con el administrador inicial.';
        if ($DetallesEliminacion) { error_log('SGCE instalador: revisar limpieza automática: ' . implode(' | ', $DetallesEliminacion)); }
        $Tipo = $DetallesEliminacion ? 'warning' : 'success';
        $YaInstalado = true;
    } catch (Exception $E) {
        if (isset($PdoDb) && $PdoDb instanceof PDO && $PdoDb->inTransaction()) { $PdoDb->rollBack(); }
        error_log('SGCE instalador: ' . $E->getMessage());
        $Mensaje = InstalarModoDebug()
            ? 'Error al instalar: ' . $E->getMessage()
            : 'No se pudo completar la instalación. Verifica los datos capturados, la conexión MySQL y los permisos de las carpetas config y storage.';
        $Tipo = 'danger';
    }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Instalación SGCE</title>
<link rel="icon" href="favicon.ico">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/sgce-base.css?v=1.0.0">
</head>
<body class="SgceBody SgceInstallerPage">
<main class="SgceModuleWrap" style="max-width:1120px">
    <section class="Top SgceInstallerHero">
        <div class="TopLeft">
            <div class="IconBox"><i class="fa-solid fa-shield-halved"></i></div>
            <div>
                <h1>INSTALACIÓN SGCE</h1>
                <p>Configura la escuela, crea el ciclo escolar inicial y registra el administrador principal.</p>
            </div>
        </div>
    </section>

    <?php if ($Mensaje !== ''): ?>
        <div class="alert alert-<?= HInst($Tipo) ?> SgceInstallerAlert border-0 shadow-sm rounded-4 mt-4 fw-semibold" role="alert">
            <div class="SgceInstallerAlertBody">
                <i class="fa-solid <?= $Tipo === 'success' ? 'fa-circle-check' : ($Tipo === 'warning' ? 'fa-triangle-exclamation' : 'fa-circle-xmark') ?> me-2"></i>
                <span><?= HInst($Mensaje) ?></span>
            </div>
            <button type="button" class="SgceInstallerAlertClose" aria-label="Cerrar mensaje" data-sgce-dismiss onclick="var A=this.closest('.SgceInstallerAlert,.alert');if(A){A.classList.add('SgceAlertLeaving');setTimeout(function(){A.remove();},180);}">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    <?php endif; ?>

    <?php if ($YaInstalado): ?>
        <section class="SgcePanel mt-4 p-4 SgceInstallerCard">
            <h2 class="h4 fw-bold text-success"><i class="fa-solid fa-circle-check me-2"></i>Sistema instalado</h2>
            <p class="mb-3">El instalador quedó bloqueado. Entra al sistema desde la pantalla principal.</p>
            <a href="index.php" class="BtnPrimary text-decoration-none d-inline-flex align-items-center gap-2"><i class="fa-solid fa-right-to-bracket"></i> Ir al login</a>
        </section>
    <?php else: ?>
        <section class="SgcePanel mt-4 p-4 SgceInstallerCard">
            <div class="SgceInstallerTitle">
                <span><i class="fa-solid fa-database"></i></span>
                <div>
                    <h2>Configuración inicial del sistema</h2>
                    <p>Prepara el sistema con los datos oficiales de la escuela, el ciclo escolar inicial y el administrador principal.</p>
                </div>
            </div>
            <div class="SgceInstallerWarning">
                <i class="fa-solid fa-circle-info"></i>
                <div><strong>Importante:</strong> utiliza una base de datos exclusiva para SGCE. La instalación preparará esa base desde cero.</div>
            </div>
            <form method="post" class="row g-3 mt-2" id="SgceInstallerForm">
                <div class="col-12"><h3 class="SgceInstallerSectionTitle"><i class="fa-solid fa-server"></i> Conexión MySQL</h3></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Host MySQL</label><input class="form-control FormControl" name="Host" value="<?= HInst($Valores['Host']) ?>" required></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Base de datos</label><input class="form-control FormControl" name="BaseDatos" value="<?= HInst($Valores['BaseDatos']) ?>" required maxlength="64" pattern="[A-Za-z0-9_]{1,64}" title="Solo letras, números y guion bajo."></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Usuario MySQL</label><input class="form-control FormControl" name="UsuarioMysql" value="<?= HInst($Valores['UsuarioMysql']) ?>" required></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Contraseña MySQL</label><input class="form-control FormControl" type="password" name="PasswordMysql" value="<?= HInst($Valores['PasswordMysql']) ?>"></div>
                <div class="col-12"><label class="fw-bold mb-2">Carpeta de respaldos</label><input class="form-control FormControl" name="BackupDir" value="<?= HInst($Valores['BackupDir']) ?>" required></div>

                <div class="col-12"><h3 class="SgceInstallerSectionTitle"><i class="fa-solid fa-school"></i> Datos oficiales de la escuela</h3></div>
                <div class="col-md-8"><label class="fw-bold mb-2">Nombre oficial de la escuela</label><input class="form-control FormControl InputUpper" name="NombreEscuela" value="<?= HInst($Valores['NombreEscuela']) ?>" required minlength="3" maxlength="150" placeholder="Ej. SECUNDARIA TÉCNICA 101"></div>
                <div class="col-md-4"><label class="fw-bold mb-2">CCT / Clave</label><input class="form-control FormControl InputUpper" name="ClaveCentroTrabajo" value="<?= HInst($Valores['ClaveCentroTrabajo']) ?>" maxlength="30" pattern="[A-Z0-9\-]{0,30}" title="Solo letras, números o guion." placeholder="Opcional"></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Director(a)</label><input class="form-control FormControl InputUpper" name="DirectorNombre" value="<?= HInst($Valores['DirectorNombre']) ?>" maxlength="120" pattern="[A-ZÁÉÍÓÚÜÑ .'-]*" title="Solo letras y espacios." placeholder="Opcional"></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Municipio y estado</label><input class="form-control FormControl InputUpper" name="MunicipioEstado" value="<?= HInst($Valores['MunicipioEstado']) ?>" maxlength="120" placeholder="Ej. ARANDAS, JALISCO"></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Teléfono</label><input class="form-control FormControl InputDigits" type="tel" name="TelefonoEscuela" value="<?= HInst($Valores['TelefonoEscuela']) ?>" inputmode="numeric" autocomplete="tel" minlength="7" maxlength="15" pattern="\d{7,15}" title="Solo números, mínimo 7 y máximo 15 dígitos." placeholder="Opcional"></div>
                <div class="col-md-6"><label class="fw-bold mb-2">Correo institucional</label><input class="form-control FormControl" type="email" name="CorreoEscuela" value="<?= HInst($Valores['CorreoEscuela']) ?>" maxlength="120" autocomplete="email" placeholder="Opcional: direccion@escuela.com"></div>

                <div class="col-12"><h3 class="SgceInstallerSectionTitle"><i class="fa-solid fa-calendar-days"></i> Ciclo escolar inicial</h3></div>
                <div class="col-md-4"><label class="fw-bold mb-2">Nombre del ciclo</label><input class="form-control FormControl InputUpper" name="CicloNombre" value="<?= HInst($Valores['CicloNombre']) ?>" required maxlength="40"></div>
                <div class="col-md-4"><label class="fw-bold mb-2">Fecha de inicio</label><input class="form-control FormControl" type="date" name="FechaInicio" value="<?= HInst($Valores['FechaInicio']) ?>" required></div>
                <div class="col-md-4"><label class="fw-bold mb-2">Fecha de fin</label><input class="form-control FormControl" type="date" name="FechaFin" value="<?= HInst($Valores['FechaFin']) ?>" required></div>
                <div class="col-md-4"><label class="fw-bold mb-2">Periodo 1</label><input class="form-control FormControl InputUpper" name="PeriodoUno" value="<?= HInst($Valores['PeriodoUno']) ?>" required maxlength="80"></div>
                <div class="col-md-4"><label class="fw-bold mb-2">Periodo 2</label><input class="form-control FormControl InputUpper" name="PeriodoDos" value="<?= HInst($Valores['PeriodoDos']) ?>" required maxlength="80"></div>
                <div class="col-md-4"><label class="fw-bold mb-2">Periodo 3</label><input class="form-control FormControl InputUpper" name="PeriodoTres" value="<?= HInst($Valores['PeriodoTres']) ?>" required maxlength="80"></div>

                <div class="col-12"><h3 class="SgceInstallerSectionTitle"><i class="fa-solid fa-user-shield"></i> Administrador inicial</h3></div>
                <div class="col-md-4"><label class="fw-bold mb-2">Nombre del administrador</label><input class="form-control FormControl InputUpper" name="AdminNombre" value="<?= HInst($Valores['AdminNombre']) ?>" required minlength="3" maxlength="120" pattern="[A-ZÁÉÍÓÚÜÑ .'-]+" title="Solo letras y espacios." placeholder="NOMBRE COMPLETO"></div>
                <div class="col-md-4"><label class="fw-bold mb-2">Usuario administrador</label><input class="form-control FormControl" name="AdminUsuario" value="<?= HInst($Valores['AdminUsuario']) ?>" required minlength="3" maxlength="80" pattern="[A-Za-z0-9._@-]{3,80}" autocomplete="username" placeholder="El cliente define su usuario"></div>
                <div class="col-md-4"><label class="fw-bold mb-2">Contraseña administrador</label><input class="form-control FormControl" type="password" name="AdminPassword" required minlength="8" autocomplete="new-password" placeholder="Mínimo 8, mayúscula, minúscula, número y símbolo"></div>
                <div class="col-12"><label class="fw-bold mb-2">Confirmación</label><input class="form-control FormControl" name="ConfirmarInstalacion" placeholder="INSTALAR SGCE" required></div>
                <div class="col-12"><button type="submit" class="BtnPrimary SgceInstallerBtn border-0"><i class="fa-solid fa-wand-magic-sparkles"></i> Instalar sistema</button></div>
            </form>
        </section>
    <?php endif; ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/Instalar.js?v=1.0.0"></script>
</body>
</html>
