<?php
/*
    Archivo: Instalar.php
    Descripción: Instalador básico del sistema SGCE.
    Sirve para revisar conexión, instalar la base desde ControlEscolar.sql y validar que exista el administrador inicial.
    IMPORTANTE: debe eliminarse o renombrarse después de instalar el sistema.
*/



if (session_status() === PHP_SESSION_NONE) { session_start(); }
function ObtenerCsrfToken() {
    if (empty($_SESSION['CsrfToken'])) { $_SESSION['CsrfToken'] = bin2hex(random_bytes(32)); }
    return $_SESSION['CsrfToken'];
}
function RequerirCsrfPost() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { return; }
    $Token = $_POST['CsrfToken'] ?? '';
    if (!is_string($Token) || empty($_SESSION['CsrfToken']) || !hash_equals($_SESSION['CsrfToken'], $Token)) {
        http_response_code(403); die('Solicitud inválida. Recarga la página e intenta nuevamente.');
    }
}
function ImprimirCsrfScript() {
    $Token = htmlspecialchars(ObtenerCsrfToken(), ENT_QUOTES, 'UTF-8');
    echo "";
}

$InstaladorPermitido = file_exists(__DIR__ . '/PERMITIR_INSTALACION.lock');
if (!$InstaladorPermitido) {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Instalador protegido</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">


<!-- SGCE FIX10: Botones de regreso/cerrar sesión con borde tinto fuerte y estilo homologado -->



    <link rel="stylesheet" href="assets/css/sgce-base.css?v=50">
    <link rel="stylesheet" href="assets/css/sgce-shared.css?v=44">
    <link rel="stylesheet" href="assets/css/Instalar.css?v=44">
</head><body class="bg-light"><main class="container py-5"><div class="card shadow border-0 p-4" style="max-width:760px;margin:auto;border-radius:24px"><h1 class="h3 fw-bold text-danger">Instalador bloqueado por seguridad</h1><p>Este archivo puede borrar o reinstalar la base de datos. Para usarlo de forma controlada, crea temporalmente un archivo vacío llamado <strong>PERMITIR_INSTALACION.lock</strong> en esta misma carpeta, ejecuta la instalación y después elimínalo.</p><a class="btn btn-primary SgceBtnInicio" href="index.php">VOLVER A INICIO</a></div></main>


<!-- SGCE FIX12: Homologación final de botones superiores y reportes -->



<script src="assets/js/sgce-shared.js?v=44"></script>
<script src="assets/js/Instalar.js?v=44"></script>
</body></html>';
    exit;
}

$Host = 'localhost';
$Db = 'ControlEscolar';
$User = 'Eo';
$Pass = 'Eo94?';
$Charset = 'utf8mb4';
$Mensaje = '';
$Tipo = 'info';

function HInst($Texto) { return htmlspecialchars((string)$Texto, ENT_QUOTES, 'UTF-8'); }

function EjecutarSqlCompleto($Pdo, $Sql) {
    $Sql = preg_replace('/^--.*$/m', '', $Sql);
    $Sql = preg_replace('/\/\*.*?\*\//s', '', $Sql);
    $Partes = preg_split('/;\s*\n/', $Sql);
    foreach ($Partes as $Parte) {
        $Parte = trim($Parte);
        if ($Parte !== '') {
            $Pdo->exec($Parte);
        }
    }
}

try {
    $PdoServidor = new PDO("mysql:host=$Host;charset=$Charset", $User, $Pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $ConexionOk = true;
} catch (Exception $E) {
    $ConexionOk = false;
    $Mensaje = 'NO SE PUDO CONECTAR AL SERVIDOR MYSQL. REVISA HOST, USUARIO Y CONTRASEÑA.';
    $Tipo = 'danger';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['Instalar']) && $ConexionOk) {
    RequerirCsrfPost();
    $ArchivoSql = __DIR__ . '/ControlEscolar.sql';
    if (!file_exists($ArchivoSql)) {
        $Mensaje = 'NO SE ENCONTRÓ ControlEscolar.sql EN LA MISMA CARPETA.';
        $Tipo = 'danger';
    } else {
        try {
            $Sql = file_get_contents($ArchivoSql);
            EjecutarSqlCompleto($PdoServidor, $Sql);
            $Mensaje = 'BASE DE DATOS INSTALADA CORRECTAMENTE. USUARIO INICIAL: Admin / Admin123. ELIMINA O RENOMBRA Instalar.php POR SEGURIDAD.';
            $Tipo = 'success';
        } catch (Exception $E) {
            $Mensaje = 'ERROR AL INSTALAR LA BASE: ' . $E->getMessage();
            $Tipo = 'danger';
        }
    }
}

$EstadoDb = 'NO VERIFICADA';
$EstadoAdmin = 'NO VERIFICADO';
$EstadoTablas = [];
if ($ConexionOk) {
    try {
        $Pdo = new PDO("mysql:host=$Host;dbname=$Db;charset=$Charset", $User, $Pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $EstadoDb = 'EXISTE Y CONECTA';
        $TablasNecesarias = ['Usuarios','Grupos','Alumnos','Asignaciones','Calificaciones','Asistencias','Avisos','BitacoraMovimientos'];
        foreach ($TablasNecesarias as $Tabla) {
            $Stmt = $Pdo->prepare("SHOW TABLES LIKE ?");
            $Stmt->execute([$Tabla]);
            $EstadoTablas[$Tabla] = $Stmt->fetchColumn() ? 'OK' : 'FALTA';
        }
        $EstadoAdmin = ((int)$Pdo->query("SELECT COUNT(*) FROM Usuarios WHERE Rol='admin' AND Activo=1")->fetchColumn() > 0) ? 'EXISTE ADMIN ACTIVO' : 'NO HAY ADMIN ACTIVO';
    } catch (Exception $E) {
        $EstadoDb = 'NO EXISTE O NO CONECTA';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGCE | Instalador</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    
    

</head>
<body>
<div class="container py-4">
    <div class="Top mb-4"><h2 class="fw-bold"><i class="fa-solid fa-screwdriver-wrench me-2"></i>INSTALADOR SGCE</h2><p class="mb-0">REVISA EL SISTEMA E INSTALA LA BASE DESDE CERO.</p></div>
    <?php if($Mensaje): ?><div class="alert alert-<?= HInst($Tipo) ?> rounded-4 shadow-sm border-0"><?= HInst($Mensaje) ?></div><?php endif; ?>
    <div class="row g-4">
        <div class="col-lg-6"><div class="card Card p-4"><h5 class="fw-bold text-danger">ESTADO</h5><p><strong>MYSQL:</strong> <?= $ConexionOk?'CONECTADO':'SIN CONEXIÓN' ?></p><p><strong>BASE:</strong> <?= HInst($EstadoDb) ?></p><p><strong>ADMIN:</strong> <?= HInst($EstadoAdmin) ?></p><?php foreach($EstadoTablas as $T=>$E): ?><span class="badge <?= $E==='OK'?'bg-success':'bg-danger' ?> me-1 mb-1"><?= HInst($T.' '.$E) ?></span><?php endforeach; ?></div></div>
        <div class="col-lg-6"><div class="card Card p-4"><h5 class="fw-bold text-primary">INSTALAR DESDE CERO</h5><p class="text-muted">Esto ejecuta <strong>ControlEscolar.sql</strong>. Si el archivo contiene DROP DATABASE, se borrará la base anterior y se creará limpia.</p><form method="POST" onsubmit="return confirm('ESTO PUEDE BORRAR LA BASE ACTUAL. ¿CONTINUAR?')">
                    <?php echo CampoCsrf(); ?><button name="Instalar" value="1" class="btn btn-danger Btn px-4"><i class="fa-solid fa-database me-2"></i> INSTALAR BASE DE DATOS</button></form><hr><a href="index.php" class="btn btn-outline-dark Btn"><i class="fa-solid fa-right-to-bracket me-2"></i> VOLVER A INICIO</a></div></div>
    </div>
</div>
<?php ImprimirCsrfScript(); ?>
</body>
</html>
