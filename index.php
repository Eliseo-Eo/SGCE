<?php
require 'Conexion.php';

$UsuarioActivo = VerificarSesionCookie($Pdo);

if ($UsuarioActivo) {
    if ($UsuarioActivo['Rol'] === 'admin') {
        header('Location: Admin.php');
        exit;
    }

    if ($UsuarioActivo['Rol'] === 'maestro') {
        header('Location: Maestro.php');
        exit;
    }
}

$Error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $Username = trim($_POST['Username']);
    $Password = trim($_POST['Password']);

    if (!empty($Username) && !empty($Password)) {

        $Stmt = $Pdo->prepare('SELECT * FROM Usuarios WHERE Username = ?');
        $Stmt->execute([$Username]);

        $User = $Stmt->fetch();

        if ($User && $Password === $User['Password']) {

            $Token = bin2hex(random_bytes(32));

            $Stmt = $Pdo->prepare("
                UPDATE Usuarios 
                SET SessionToken = ? 
                WHERE Id = ?
            ");

            $Stmt->execute([$Token, $User['Id']]);

            setcookie('AuthToken', $Token, [
                'expires' => time() + 86400,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Strict'
            ]);

            if ($User['Rol'] == 'admin') {
                header('Location: Admin.php');
            } else {
                header('Location: Maestro.php');
            }

            exit;

        } else {

            $Error = 'Usuario o contraseña incorrectos';

        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>EST 101 | Sistema Escolar</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

    <style>

        :root{
            --Guinda:#7A0818;
            --GuindaHover:#5E0612;
            --Fondo:#EEF2F7;
            --Texto:#1F2937;
            --TextoClaro:#6B7280;
            --Blanco:#FFFFFF;
        }

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        body{

            min-height:100vh;

            background:
            linear-gradient(
                135deg,
                rgba(122,8,24,0.96),
                rgba(45,5,12,0.92)
            ),
            url('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?q=80&w=1400&auto=format&fit=crop');

            background-size:cover;
            background-position:center;

            font-family:'Segoe UI', sans-serif;

            overflow-x:hidden;
        }

        .Overlay{
            position:absolute;
            inset:0;
            backdrop-filter:blur(6px);
            background:rgba(0,0,0,0.15);
        }

        .ContainerPrincipal{

            position:relative;
            z-index:2;

            min-height:100vh;

            display:flex;
            justify-content:center;
            align-items:center;

            padding:30px;
        }

        .GridLogin{

            width:100%;
            max-width:1200px;

            display:grid;
            grid-template-columns:1fr 480px;

            border-radius:28px;

            overflow:hidden;

            box-shadow:
            0 20px 60px rgba(0,0,0,0.35);

            background:rgba(255,255,255,0.08);

            backdrop-filter:blur(20px);
        }

        .PanelIzquierdo{

            padding:60px;

            color:white;

            display:flex;
            flex-direction:column;
            justify-content:center;
        }

        .LogoSistema{

            width:100px;
            height:100px;

            border-radius:25px;

            background:rgba(255,255,255,0.15);

            display:flex;
            align-items:center;
            justify-content:center;

            font-size:42px;

            margin-bottom:30px;

            border:1px solid rgba(255,255,255,0.2);
        }

        .TituloSistema{

            font-size:4rem;
            font-weight:900;
            line-height:1;
            margin-bottom:10px;
        }

        .SubtituloSistema{

            font-size:1.1rem;
            opacity:0.9;
            margin-bottom:35px;
        }

        .DescripcionSistema{

            font-size:1rem;
            line-height:1.8;
            color:rgba(255,255,255,0.85);

            max-width:520px;
        }

        .Caracteristicas{

            margin-top:40px;

            display:grid;
            grid-template-columns:repeat(2,1fr);

            gap:18px;
        }

        .CardCaracteristica{

            background:rgba(255,255,255,0.08);

            border:1px solid rgba(255,255,255,0.12);

            border-radius:18px;

            padding:18px;

            transition:0.25s;
        }

        .CardCaracteristica:hover{

            transform:translateY(-4px);

            background:rgba(255,255,255,0.12);
        }

        .IconoCaracteristica{

            width:55px;
            height:55px;

            border-radius:14px;

            display:flex;
            align-items:center;
            justify-content:center;

            background:rgba(255,255,255,0.15);

            font-size:22px;

            margin-bottom:15px;
        }

        .TituloCaracteristica{

            font-weight:700;
            margin-bottom:5px;
        }

        .TextoCaracteristica{

            font-size:0.92rem;
            opacity:0.85;
        }

        .PanelDerecho{

            background:white;

            padding:55px 45px;

            display:flex;
            flex-direction:column;
            justify-content:center;
        }

        .LoginHeader{

            text-align:center;
            margin-bottom:35px;
        }

        .LoginIcon{

            width:90px;
            height:90px;

            margin:auto;

            border-radius:24px;

            background:linear-gradient(
                135deg,
                var(--Guinda),
                #A10D26
            );

            display:flex;
            align-items:center;
            justify-content:center;

            color:white;

            font-size:36px;

            margin-bottom:20px;

            box-shadow:
            0 10px 25px rgba(122,8,24,0.35);
        }

        .TituloLogin{

            font-size:2rem;
            font-weight:800;
            color:var(--Texto);
        }

        .SubtituloLogin{

            color:var(--TextoClaro);
            margin-top:8px;
        }

        .InputContainer{

            margin-bottom:24px;
        }

        .InputLabel{

            font-weight:700;
            color:var(--Texto);

            margin-bottom:10px;

            display:block;
        }

        .InputGroupCustom{

            background:#F8FAFC;

            border:2px solid #E5E7EB;

            border-radius:18px;

            overflow:hidden;

            transition:0.2s;
        }

        .InputGroupCustom:focus-within{

            border-color:var(--Guinda);

            box-shadow:
            0 0 0 5px rgba(122,8,24,0.08);
        }

        .InputIcon{

            width:65px;

            display:flex;
            align-items:center;
            justify-content:center;

            color:#6B7280;

            font-size:18px;
        }

        .InputCustom{

            border:none !important;

            background:transparent !important;

            height:60px;

            font-size:1rem;
        }

        .InputCustom:focus{

            box-shadow:none !important;
        }

        .BtnLogin{

            height:60px;

            border:none;

            border-radius:18px;

            background:linear-gradient(
                135deg,
                var(--Guinda),
                #A10D26
            );

            color:white;

            font-weight:800;

            font-size:1rem;

            transition:0.25s;

            box-shadow:
            0 12px 25px rgba(122,8,24,0.25);
        }

        .BtnLogin:hover{

            transform:translateY(-3px);

            box-shadow:
            0 18px 35px rgba(122,8,24,0.35);

            background:linear-gradient(
                135deg,
                #8E0A1D,
                #C11231
            );
        }

        .AlertError{

            background:#FEE2E2;

            color:#991B1B;

            border:none;

            border-radius:16px;

            padding:16px;

            font-size:0.95rem;

            margin-bottom:25px;
        }

        .FooterLogin{

            margin-top:28px;

            text-align:center;

            color:#9CA3AF;

            font-size:0.9rem;
        }

        @media(max-width:992px){

            .GridLogin{
                grid-template-columns:1fr;
            }

            .PanelIzquierdo{
                display:none;
            }

            .PanelDerecho{
                padding:40px 30px;
            }
        }

    </style>

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
                EST 101
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
                            class="form-control InputCustom"
                            placeholder="Ingresa tu usuario"
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
                            class="form-control InputCustom"
                            placeholder="••••••••"
                            required
                        >

                    </div>

                </div>

                <button type="submit" class="btn BtnLogin w-100">

                    <i class="fa-solid fa-right-to-bracket me-2"></i>

                    ACCEDER AL SISTEMA

                </button>

            </form>

            <div class="FooterLogin">

                <i class="fa-solid fa-code"></i>

                Plataforma Escolar Profesional · EST 101

            </div>

        </div>

    </div>

</div>

</body>

</html>