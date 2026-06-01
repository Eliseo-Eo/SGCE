<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }



require_once dirname(__DIR__) . '/config/Conexion.php';


$UsuarioActivoLogout = VerificarSesionCookie($Pdo);




if (isset($_COOKIE['AuthToken'])) {

    $Token = trim($_COOKIE['AuthToken']);

    if ($Token !== '') {

        $Stmt = $Pdo->prepare("
            UPDATE Usuarios
            SET SessionToken = NULL, SessionTokenExpira = NULL
            WHERE SessionToken = ?
        ");

        $Stmt->execute([
            $Token
        ]);

        
        if ($UsuarioActivoLogout) {
            RegistrarBitacora($Pdo, $UsuarioActivoLogout, 'CIERRE_SESION', 'Usuarios', $UsuarioActivoLogout['Id'], 'USUARIO CERRÓ SESIÓN');
        }
    }

    

    setcookie(
        'AuthToken',
        '',
        [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Strict',
            'secure' => EsHttps()
        ]
    );
}


SgceCerrarSesionPhpCompleta();


header('Location: index.php');

exit;

?>
