<?php
require_once dirname(__DIR__) . '/config/Conexion.php';

$UsuarioActivo = VerificarSesionCookie($Pdo);

if ($UsuarioActivo) {
    if ($UsuarioActivo['Rol'] === 'maestro') {
        header('Location: Maestro.php');
        exit;
    }

    if (in_array($UsuarioActivo['Rol'], ['admin','director','secretario','coordinador','prefecto'], true)) {
        header('Location: ' . ($UsuarioActivo['Rol'] === 'admin' ? 'Admin.php?Tab=inicio' : 'ReportesAdmin.php'));
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

            // Registro el inicio de sesión en la bitácora para saber quién entró al sistema.
            RegistrarBitacora($Pdo, $User, 'INICIO_SESION', 'Usuarios', $User['Id'], 'USUARIO INICIÓ SESIÓN');

            if ($User['Rol'] === 'maestro') {
                header('Location: Maestro.php');
            } elseif ($User['Rol'] === 'admin') {
                header('Location: Admin.php?Tab=inicio');
            } else {
                header('Location: ReportesAdmin.php');
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

    
    <!-- FAVICON DEL SISTEMA: ICONO QUE APARECE EN LA PESTAÑA DEL NAVEGADOR -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <link rel="apple-touch-icon" href="favicon.png">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= HGlobal($NombreEscuelaLogin) ?> | Sistema Escolar</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/sgce-base.css?v=1.0.0">
<?= SgceEstilosTema($Pdo) ?>
</head>

<body>

<div class="Overlay"></div>

<div class="ContainerPrincipal">

    <div class="GridLogin">

        <!-- PANEL IZQUIERDO -->

        <div class="PanelIzquierdo">

            <div class="LogoSistema">
                <i class="fa-solid fa-school"></i>
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
                        <i class="fa-solid fa-chart-line"></i>
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
                        <i class="fa-solid fa-user-check"></i>
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
                        <i class="fa-solid fa-file-pdf"></i>
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
                        <i class="fa-solid fa-shield-halved"></i>
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

        <!-- LOGIN -->

        <div class="PanelDerecho">

            <div class="LoginHeader">

                <div class="LoginIcon">
                    <i class="fa-solid fa-user-graduate"></i>
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

                    <?= $Error ?>

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
                            <i class="fa-solid fa-user"></i>
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
                            <i class="fa-solid fa-lock"></i>
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

                    <i class="fa-solid fa-right-to-bracket me-2"></i>

                    ACCEDER AL SISTEMA

                </button>

            </form>

            <a href="ConsultaPadre.php" class="BtnConsultaPadre">
                <i class="fa-solid fa-user-shield"></i>
                CONSULTA DE ASISTENCIA PARA PADRES
            </a>

            <div class="FooterLogin">

                <i class="fa-solid fa-code"></i>

                Plataforma Escolar Profesional · <?= HGlobal($NombreEscuelaLogin) ?>

            </div>

        </div>

    </div>

</div>






<?php ImprimirCsrfScript(); ?>
<script src="assets/js/sgce-shared.js?v=1.0.0"></script>
<script src="assets/js/index.js?v=1.0.0"></script>
</body>

</html>
