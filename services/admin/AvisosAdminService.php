<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceAvisosAdminPreparar(PDO $Pdo, array $UserSession): array {
    function HAviso($Texto) {
        return htmlspecialchars((string)$Texto, ENT_QUOTES, 'UTF-8');
    }


    function MayusAviso($Valor) {
        $Valor = trim((string)$Valor);
        $Valor = preg_replace('/\s+/u', ' ', $Valor);

        if ($Valor === '') {
            return '';
        }

        if (function_exists('mb_strtoupper')) {
            return mb_strtoupper($Valor, 'UTF-8');
        }

        return strtoupper($Valor);
    }


    function PublicoAvisoValido($Publico) {
        $Publico = MayusAviso($Publico);
        return in_array($Publico, ['TODOS', 'MAESTROS', 'PADRES'], true) ? $Publico : 'TODOS';
    }


    function RedirectAvisos() {
        header('Location: AvisosAdmin.php');
        exit;
    }





    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        RequerirCsrfPost();




        if (isset($_POST['CrearAviso'])) {

            $Titulo = MayusAviso($_POST['Titulo'] ?? '');
            $Mensaje = MayusAviso($_POST['Mensaje'] ?? '');
            $Publico = PublicoAvisoValido($_POST['Publico'] ?? 'TODOS');

            if ($Titulo === '' || $Mensaje === '') {
                $_SESSION['Mensaje'] = 'Completa título y mensaje para publicar el aviso.';
                $_SESSION['MensajeTipo'] = 'danger';
                RedirectAvisos();
            }

            try {
                $Stmt = $Pdo->prepare("\n                INSERT INTO Avisos (Titulo, Mensaje, Publico, Activo)\n                VALUES (?, ?, ?, 1)\n            ");
                $Stmt->execute([$Titulo, $Mensaje, $Publico]);

                RegistrarBitacora($Pdo, $UserSession, 'CREAR_AVISO', 'Avisos', $Pdo->lastInsertId(), 'AVISO PUBLICADO PARA ' . $Publico);

                $_SESSION['Mensaje'] = 'Aviso publicado correctamente.';
                $_SESSION['MensajeTipo'] = 'success';
            } catch (Exception $E) {
                $_SESSION['Mensaje'] = 'Error al publicar el aviso.';
                $_SESSION['MensajeTipo'] = 'danger';
            }

            RedirectAvisos();
        }




        if (isset($_POST['EditarAviso'])) {

            $Id = intval($_POST['AvisoId'] ?? 0);
            $Titulo = MayusAviso($_POST['Titulo'] ?? '');
            $Mensaje = MayusAviso($_POST['Mensaje'] ?? '');
            $Publico = PublicoAvisoValido($_POST['Publico'] ?? 'TODOS');

            if ($Id <= 0 || $Titulo === '' || $Mensaje === '') {
                $_SESSION['Mensaje'] = 'Datos inválidos para editar el aviso.';
                $_SESSION['MensajeTipo'] = 'danger';
                RedirectAvisos();
            }

            try {
                $Stmt = $Pdo->prepare("\n                UPDATE Avisos\n                SET Titulo = ?, Mensaje = ?, Publico = ?\n                WHERE Id = ?\n            ");
                $Stmt->execute([$Titulo, $Mensaje, $Publico, $Id]);

                RegistrarBitacora($Pdo, $UserSession, 'EDITAR_AVISO', 'Avisos', $Id, 'AVISO ACTUALIZADO');

                $_SESSION['Mensaje'] = 'Aviso actualizado correctamente.';
                $_SESSION['MensajeTipo'] = 'success';
            } catch (Exception $E) {
                $_SESSION['Mensaje'] = 'Error al actualizar el aviso.';
                $_SESSION['MensajeTipo'] = 'danger';
            }

            RedirectAvisos();
        }




        if (isset($_POST['ActivarAviso'])) {

            $Id = intval($_POST['ActivarAviso']);

            if ($Id > 0) {
                try {
                    $Pdo->prepare("UPDATE Avisos SET Activo = 1 WHERE Id = ?")->execute([$Id]);
                    RegistrarBitacora($Pdo, $UserSession, 'ACTIVAR_AVISO', 'Avisos', $Id, 'AVISO ACTIVADO');

                    $_SESSION['Mensaje'] = 'Aviso activado correctamente.';
                    $_SESSION['MensajeTipo'] = 'success';
                } catch (Exception $E) {
                    $_SESSION['Mensaje'] = 'Error al activar el aviso.';
                    $_SESSION['MensajeTipo'] = 'danger';
                }
            }

            RedirectAvisos();
        }




        if (isset($_POST['DesactivarAviso'])) {

            $Id = intval($_POST['DesactivarAviso']);

            if ($Id > 0) {
                try {
                    $Pdo->prepare("UPDATE Avisos SET Activo = 0 WHERE Id = ?")->execute([$Id]);
                    RegistrarBitacora($Pdo, $UserSession, 'DESACTIVAR_AVISO', 'Avisos', $Id, 'AVISO DESACTIVADO');

                    $_SESSION['Mensaje'] = 'Aviso desactivado correctamente.';
                    $_SESSION['MensajeTipo'] = 'success';
                } catch (Exception $E) {
                    $_SESSION['Mensaje'] = 'Error al desactivar el aviso.';
                    $_SESSION['MensajeTipo'] = 'danger';
                }
            }

            RedirectAvisos();
        }
    }





    $PaginaAvisos = SgcePaginaActual('PagAvisos', 1);
    $PorPaginaAvisos = 7;
    [$OffsetAvisos, $LimitAvisos] = SgceLimitOffset($PaginaAvisos, $PorPaginaAvisos);

    $TotalAvisos = (int)$Pdo->query("SELECT COUNT(*) FROM Avisos")->fetchColumn();

    $StmtAvisos = $Pdo->prepare("
        SELECT Id, Titulo, Mensaje, Publico, Activo, FechaCreacion
        FROM Avisos
        ORDER BY Activo DESC, FechaCreacion DESC, Id DESC
        LIMIT :Limit OFFSET :Offset
    ");
    $StmtAvisos->bindValue(':Limit', $LimitAvisos, PDO::PARAM_INT);
    $StmtAvisos->bindValue(':Offset', $OffsetAvisos, PDO::PARAM_INT);
    $StmtAvisos->execute();
    $Avisos = $StmtAvisos->fetchAll();

    return compact('PaginaAvisos', 'PorPaginaAvisos', 'OffsetAvisos', 'LimitAvisos', 'TotalAvisos', 'Avisos');
}
