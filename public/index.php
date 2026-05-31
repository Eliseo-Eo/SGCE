<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
require_once dirname(__DIR__) . '/config/Conexion.php';

$UsuarioActivo = VerificarSesionCookie($Pdo);

if ($UsuarioActivo) {
    if (SgceTieneRol($UsuarioActivo, ['maestro'])) {
        header('Location: Maestro.php');
        exit;
    }

    if (SgceTieneRol($UsuarioActivo, ['admin','administrativo'])) {
        header('Location: Admin.php?Tab=inicio');
        exit;
    }
}

$Error = '';
$ConfigSistema = SgceObtenerConfiguracion($Pdo);
$NombreEscuelaLogin = trim((string)($ConfigSistema['NombreEscuela'] ?? 'SGCE'));

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    RequerirCsrfPost();

    $Username = trim((string)($_POST['Username'] ?? ''));
    $Password = trim((string)($_POST['Password'] ?? ''));

    if (!empty($Username) && !empty($Password)) {

        if (!RateLimitDisponible($Pdo, 'login', $Username)) {
            $Error = 'Demasiados intentos. Espera 15 minutos e intenta nuevamente.';
        } else {

        $Stmt = $Pdo->prepare('SELECT * FROM Usuarios WHERE Username = ? AND Activo = 1');
        $Stmt->execute([$Username]);

        $User = $Stmt->fetch();

        if ($User && SgcePasswordVerify($Password, $User['Password'])) {

            RateLimitLimpiar($Pdo, 'login', $Username);

            
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }

            $Token = bin2hex(random_bytes(32));

            $Stmt = $Pdo->prepare("
                UPDATE Usuarios 
                SET SessionToken = ?, SessionTokenExpira = DATE_ADD(NOW(), INTERVAL 1 DAY)
                WHERE Id = ?
            ");

            $Stmt->execute([$Token, $User['Id']]);

            setcookie('AuthToken', $Token, [
                'expires' => time() + 86400,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Strict',
                'secure' => EsHttps()
            ]);

            
            RegistrarBitacora($Pdo, $User, 'INICIO_SESION', 'Usuarios', $User['Id'], 'USUARIO INICIÓ SESIÓN');

            $RolDestino = SgceNormalizarRolSistema($User['Rol'] ?? '');
            if ($RolDestino === 'maestro') {
                header('Location: Maestro.php');
            } elseif (in_array($RolDestino, ['admin', 'administrativo'], true)) {
                header('Location: Admin.php?Tab=inicio');
            } else {
                header('Location: Logout.php');
            }

            exit;

        } else {

            RateLimitRegistrarFallo($Pdo, 'login', $Username, 5, 15);
            $Error = 'Usuario o contraseña incorrectos';

        }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    
    
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <link rel="apple-touch-icon" href="favicon.png">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= HGlobal($NombreEscuelaLogin) ?> | Sistema Escolar</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/sgce-base.min.css?cache=sgce2026final">
<?= SgceEstilosTema($Pdo) ?>
</head>

<body class="LoginPage">

<div class="Overlay"></div>

<div class="ContainerPrincipal">

    <div class="GridLogin">

        

        <div class="PanelIzquierdo">

            <div class="LogoSistema">
                <span class="SgceColorIcon" aria-hidden="true">🏫</span>
            </div>

            <h1 class="TituloSistema">
                <?= HGlobal($NombreEscuelaLogin) ?>
            </h1>

            <p class="SubtituloSistema">
                Sistema Integral de Gestión Escolar
            </p>

            <p class="DescripcionSistema">

                Plataforma profesional para la administración académica,
                control escolar, asistencia, evaluación de alumnos,
                generación de reportes y seguimiento docente.

            </p>

            <div class="Caracteristicas">

                <div class="CardCaracteristica">

                    <div class="IconoCaracteristica">
                        <span class="SgceColorIcon" aria-hidden="true">🎓</span>
                    </div>

                    <div class="TituloCaracteristica">
                        Calificaciones
                    </div>

                    <div class="TextoCaracteristica">
                        Control y evaluación académica en tiempo real.
                    </div>

                </div>

                <div class="CardCaracteristica">

                    <div class="IconoCaracteristica">
                        <span class="SgceColorIcon" aria-hidden="true">📅</span>
                    </div>

                    <div class="TituloCaracteristica">
                        Asistencias
                    </div>

                    <div class="TextoCaracteristica">
                        Registro rápido e intuitivo de asistencia.
                    </div>

                </div>

                <div class="CardCaracteristica">

                    <div class="IconoCaracteristica">
                        <span class="SgceColorIcon" aria-hidden="true">📄</span>
                    </div>

                    <div class="TituloCaracteristica">
                        Reportes PDF
                    </div>

                    <div class="TextoCaracteristica">
                        Exportaciones profesionales automáticas.
                    </div>

                </div>

                <div class="CardCaracteristica">

                    <div class="IconoCaracteristica">
                        <span class="SgceColorIcon" aria-hidden="true">🛡️</span>
                    </div>

                    <div class="TituloCaracteristica">
                        Seguridad
                    </div>

                    <div class="TextoCaracteristica">
                        Acceso protegido y sesiones seguras.
                    </div>

                </div>

            </div>

        </div>

        

        <div class="PanelDerecho">

            <div class="LoginHeader">

                <div class="LoginIcon">
                    <span class="SgceColorIcon" aria-hidden="true">🎓</span>
                </div>

                <h2 class="TituloLogin">
                    Bienvenido
                </h2>

                <p class="SubtituloLogin">
                    Inicia sesión para acceder al sistema
                </p>

            </div>

            <?php if($Error): ?>

                <div class="AlertError">

                    <i class="fa-solid fa-circle-exclamation me-2"></i>

                    <?= HGlobal($Error) ?>

                </div>

            <?php endif; ?>

            <form method="POST">
                    <?php echo CampoCsrf(); ?>

                <div class="InputContainer">

                    <label class="InputLabel">
                        Usuario
                    </label>

                    <div class="input-group InputGroupCustom">

                        <span class="InputIcon">
                            <span class="SgceColorIcon" aria-hidden="true">👤</span>
                        </span>

                        <input
                            type="text"
                            name="Username"
                            class="form-control InputCustom TextoLibre"
                            placeholder="INGRESA TU USUARIO"
                            required
                        >

                    </div>

                </div>

                <div class="InputContainer">

                    <label class="InputLabel">
                        Contraseña
                    </label>

                    <div class="input-group InputGroupCustom">

                        <span class="InputIcon">
                            <span class="SgceColorIcon" aria-hidden="true">🔒</span>
                        </span>

                        <input
                            type="password"
                            name="Password"
                            id="PasswordLogin"
                            class="form-control InputCustom TextoLibre"
                            placeholder="CONTRASEÑA"
                            required
                        >

                        <button
                            type="button"
                            class="BtnVerPassword"
                            id="TogglePasswordLogin"
                            title="MOSTRAR U OCULTAR CONTRASEÑA"
                            aria-label="MOSTRAR U OCULTAR CONTRASEÑA"
                        >
                            <i class="fa-solid fa-eye"></i>
                        </button>

                    </div>

                </div>

                <button type="submit" class="btn BtnLogin w-100">

                    <span class="SgceColorIcon me-2" aria-hidden="true">➡️</span>

                    ACCEDER AL SISTEMA

                </button>

            </form>

            <div class="BtnConsultaPublicaGrid">
                <a href="ConsultaPadre.php" class="BtnConsultaPadre BtnConsultaAsistencia">
                    <span class="SgceColorIcon" aria-hidden="true">📅</span>
                    <span>ASISTENCIAS</span>
                </a>
                <a href="ConsultaCalificaciones.php" class="BtnConsultaPadre BtnConsultaCalificaciones">
                    <span class="SgceColorIcon" aria-hidden="true">⭐</span>
                    <span>CALIFICACIONES</span>
                </a>
            </div>

            <div class="FooterLogin">

                <span class="SgceColorIcon" aria-hidden="true">💻</span>

                Plataforma Escolar Profesional · <?= HGlobal($NombreEscuelaLogin) ?>

            </div>

        </div>

    </div>

</div>






<?php ImprimirCsrfScript(); ?>
<script src="assets/js/sgce-shared.js?cache=sgce2026final"></script>
<script src="assets/js/index.js?cache=sgce2026final"></script>
</body>

</html>
