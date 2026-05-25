<?php
require 'Conexion.php';

$UsuarioActivo = VerificarSesionCookie($Pdo);

if ($UsuarioActivo) {
    if ($UsuarioActivo['Rol'] === 'admin') { header('Location: Admin.php'); exit; }
    if ($UsuarioActivo['Rol'] === 'maestro') { header('Location: Maestro.php'); exit; }
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
            
            $Stmt = $Pdo->prepare("UPDATE Usuarios SET SessionToken = ? WHERE Id = ?");
            $Stmt->execute([$Token, $User['Id']]);

            setcookie('AuthToken', $Token, [
                'expires' => time() + 86400,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Strict'
            ]);

            if ($User['Rol'] == 'admin') { header('Location: Admin.php'); } 
            else { header('Location: Maestro.php'); }
            exit;
        } else { 
            $Error = 'Usuario O Contraseña Incorrectos.'; 
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>EST 101 - Control Escolar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #F8F9FA; font-family: sans-serif; }
        .CardLogin { border: none; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .BtnGuinda { background-color: #7A0818; color: white; border: none; }
        .BtnGuinda:hover { background-color: #56040E; color: white; }
        .TextGuinda { color: #7A0818; }
    </style>
</head>
<body class="d-flex align-items-center" style="height: 100vh;">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="text-center mb-4">
                <h2 class="fw-bold TextGuinda"><i class="fa-solid fa-school"></i> EST 101</h2>
                <h6 class="text-muted">Control De Calificaciones</h6>
            </div>
            <div class="card CardLogin">
                <div class="card-body p-4">
                    <?php if($Error): ?> <div class="alert alert-danger py-2 fs-6"><i class="fa-solid fa-triangle-exclamation"></i> <?= $Error ?></div> <?php endif; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Usuario</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="fa-solid fa-user text-secondary"></i></span>
                                <input type="text" name="Username" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Contraseña</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="fa-solid fa-lock text-secondary"></i></span>
                                <input type="password" name="Password" class="form-control" required>
                            </div>
                        </div>
                        <button type="submit" class="btn BtnGuinda w-100 py-2 fw-bold"><i class="fa-solid fa-right-to-bracket"></i> Iniciar Sesión</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>