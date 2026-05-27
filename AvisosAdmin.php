<?php
/*
    Archivo: AvisosAdmin.php
    Descripción: Módulo administrativo para administrar avisos y comunicados.
    Desde esta pantalla puedo crear, editar, activar y desactivar avisos para maestros, padres o todo el sistema.
    Todos los datos visibles se normalizan en mayúsculas para mantener uniforme el sistema SGCE.
*/

require 'Conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$UserSession = VerificarSesionCookie($Pdo);

if (!$UserSession || !in_array($UserSession['Rol'], ['admin', 'director', 'secretario', 'coordinador'], true)) {
    header('Location: index.php');
    exit;
}

// =====================================================
// FUNCIONES AUXILIARES
// =====================================================

// Escapo texto para imprimirlo seguro en HTML.
function HAviso($Texto) {
    return htmlspecialchars((string)$Texto, ENT_QUOTES, 'UTF-8');
}

// Normalizo textos de avisos a MAYÚSCULAS, respetando acentos y Ñ cuando mbstring está disponible.
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

// Valido que el público del aviso sea uno de los permitidos.
function PublicoAvisoValido($Publico) {
    $Publico = MayusAviso($Publico);
    return in_array($Publico, ['TODOS', 'MAESTROS', 'PADRES'], true) ? $Publico : 'TODOS';
}

// Redirecciono a esta misma pantalla después de cualquier acción para evitar reenvío de formulario.
function RedirectAvisos() {
    header('Location: AvisosAdmin.php');
    exit;
}

// =====================================================
// PROCESAMIENTO DE FORMULARIOS
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    RequerirCsrfPost();

    // ----------------------------
    // CREAR AVISO
    // ----------------------------
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

    // ----------------------------
    // EDITAR AVISO
    // ----------------------------
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

    // ----------------------------
    // ACTIVAR AVISO
    // ----------------------------
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

    // ----------------------------
    // DESACTIVAR AVISO
    // ----------------------------
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

// =====================================================
// CONSULTA DE AVISOS
// =====================================================

$Avisos = $Pdo->query("\n    SELECT *\n    FROM Avisos\n    ORDER BY Activo DESC, FechaCreacion DESC, Id DESC\n    LIMIT 200\n")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SGCE | Avisos</title>

    <!-- FAVICON DEL SISTEMA -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <link rel="apple-touch-icon" href="favicon.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    
    



<!-- SGCE FIX10: Botones de regreso/cerrar sesión con borde tinto fuerte y estilo homologado -->





    <link rel="stylesheet" href="assets/css/sgce-base.css?v=50">
    <link rel="stylesheet" href="assets/css/sgce-shared.css?v=44">
    <link rel="stylesheet" href="assets/css/AvisosAdmin.css?v=44">
</head>
<body>

<div class="container-fluid px-4 py-4">

    <div class="Top mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h2 class="mb-1" style="font-weight:900;">
                <i class="fa-solid fa-bullhorn me-2"></i>
                AVISOS Y COMUNICADOS
            </h2>
            <div>PUBLICA AVISOS PARA MAESTROS, PADRES O TODO EL SISTEMA.</div>
        </div>

        <a href="Admin.php?Tab=inicio" class="Btn BtnGuinda SgceBtnInicio">
            <i class="fa-solid fa-arrow-left"></i>
            VOLVER A INICIO
        </a>
    </div>

    <?php if (isset($_SESSION['Mensaje'])): ?>
        <div class="alert alert-<?= HAviso($_SESSION['MensajeTipo'] ?? 'success') ?> AlertAuto alert-dismissible fade show mb-4">
            <i class="fa-solid fa-circle-info me-2"></i>
            <?= HAviso($_SESSION['Mensaje']) ?>
            <?php unset($_SESSION['Mensaje'], $_SESSION['MensajeTipo']); ?>
            <button class="btn-close" data-bs-dismiss="alert" type="button" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">

        <div class="col-lg-4">
            <div class="Card CardPadding">
                <h5 class="fw-bold mb-3" style="color:var(--Guinda);">
                    <i class="fa-solid fa-plus-circle me-2"></i>
                    NUEVO AVISO
                </h5>

                <form method="POST">
                    <?php echo CampoCsrf(); ?>
                    <input type="hidden" name="CrearAviso" value="1">

                    <label>TÍTULO</label>
                    <input name="Titulo" class="form-control mb-3" required placeholder="TÍTULO DEL AVISO" autocomplete="off">

                    <label>PÚBLICO</label>
                    <select name="Publico" class="form-select mb-3">
                        <option value="TODOS">TODOS</option>
                        <option value="MAESTROS">MAESTROS</option>
                        <option value="PADRES">PADRES</option>
                    </select>

                    <label>MENSAJE</label>
                    <textarea name="Mensaje" class="form-control mb-3" rows="5" required placeholder="ESCRIBE EL COMUNICADO"></textarea>

                    <button class="Btn BtnSave w-100" type="submit">
                        <i class="fa-solid fa-paper-plane"></i>
                        PUBLICAR AVISO
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="Card CardPadding">
                <h5 class="fw-bold mb-3" style="color:var(--Guinda);">
                    <i class="fa-solid fa-list me-2"></i>
                    AVISOS REGISTRADOS
                </h5>

                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Título</th>
                                <th>Público</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($Avisos as $A): ?>
                                <?php
                                    $AvisoId = (int)$A['Id'];
                                    $EstaActivo = (int)$A['Activo'] === 1;
                                ?>
                                <tr>
                                    <td><?= HAviso(date('d/m/Y H:i', strtotime($A['FechaCreacion']))) ?></td>
                                    <td class="fw-bold"><?= HAviso($A['Titulo']) ?></td>
                                    <td><span class="BadgePublico"><?= HAviso($A['Publico']) ?></span></td>
                                    <td>
                                        <span class="BadgeEstado <?= $EstaActivo ? '' : 'BadgeInactivo' ?>">
                                            <?= $EstaActivo ? 'ACTIVO' : 'INACTIVO' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="AccionesAviso">

                                            <button type="button" class="Btn BtnEdit" data-bs-toggle="modal" data-bs-target="#ModalEditarAviso<?= $AvisoId ?>">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                                EDITAR
                                            </button>

                                            <?php if ($EstaActivo): ?>
                                                <button type="button" class="Btn BtnDanger" data-bs-toggle="modal" data-bs-target="#ModalDesactivarAviso<?= $AvisoId ?>">
                                                    <i class="fa-solid fa-ban"></i>
                                                    DESACTIVAR
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="Btn BtnActivate" data-bs-toggle="modal" data-bs-target="#ModalActivarAviso<?= $AvisoId ?>">
                                                    <i class="fa-solid fa-circle-check"></i>
                                                    ACTIVAR
                                                </button>
                                            <?php endif; ?>

                                        </div>
                                    </td>
                                </tr>

                            <?php endforeach; ?>

                            <?php if (empty($Avisos)): ?>
                                <tr>
                                    <td colspan="5" class="py-5 text-muted fw-bold">SIN AVISOS REGISTRADOS.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>



            </div>
        </div>

    </div>
</div>

<!-- MODALES PARA EDITAR AVISOS - HIJAS DIRECTAS DEL BODY.
     IMPORTANTE: no deben ir dentro de .Card porque .Card:hover usa transform,
     y un ancestor con transform rompe position:fixed de Bootstrap. -->
<?php foreach ($Avisos as $A): ?>
                    <?php $AvisoId = (int)$A['Id']; ?>
                    <!-- MODAL PARA EDITAR AVISO -->
                    <div class="modal fade" id="ModalEditarAviso<?= $AvisoId ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">

                                            <div class="ModalHeaderEdit">
                                                <div class="ModalIcon">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </div>
                                                <h4 class="fw-bold mb-1">EDITAR AVISO</h4>
                                                <div>ACTUALIZA EL COMUNICADO SELECCIONADO</div>
                                            </div>

                                            <form method="POST">
                    <?php echo CampoCsrf(); ?>
                                                <div class="modal-body">
                                                    <input type="hidden" name="EditarAviso" value="1">
                                                    <input type="hidden" name="AvisoId" value="<?= $AvisoId ?>">

                                                    <label>TÍTULO</label>
                                                    <input name="Titulo" class="form-control mb-3" required value="<?= HAviso($A['Titulo']) ?>">

                                                    <label>PÚBLICO</label>
                                                    <select name="Publico" class="form-select mb-3">
                                                        <option value="TODOS" <?= $A['Publico'] === 'TODOS' ? 'selected' : '' ?>>TODOS</option>
                                                        <option value="MAESTROS" <?= $A['Publico'] === 'MAESTROS' ? 'selected' : '' ?>>MAESTROS</option>
                                                        <option value="PADRES" <?= $A['Publico'] === 'PADRES' ? 'selected' : '' ?>>PADRES</option>
                                                    </select>

                                                    <label>MENSAJE</label>
                                                    <textarea name="Mensaje" class="form-control mb-3" rows="5" required><?= HAviso($A['Mensaje']) ?></textarea>

                                                    <div class="row g-2 mt-2">
                                                        <div class="col-md-6">
                                                            <button type="button" class="Btn BtnCancel w-100" data-bs-dismiss="modal">
                                                                <i class="fa-solid fa-xmark"></i>
                                                                CANCELAR
                                                            </button>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <button type="submit" class="Btn BtnEdit w-100">
                                                                <i class="fa-solid fa-floppy-disk"></i>
                                                                GUARDAR CAMBIOS
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </form>

                                        </div>
                                    </div>
                                </div>

                    <?php $EstaActivoModal = (int)$A['Activo'] === 1; ?>
                    <?php if ($EstaActivoModal): ?>
                    <!-- MODAL PARA DESACTIVAR AVISO -->
                    <div class="modal fade ModalAvisoEstado" id="ModalDesactivarAviso<?= $AvisoId ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-sm ModalEliminarFijo">
                            <div class="modal-content DeleteModalContent AvisoConfirmContent">
                                <div class="DeleteModalHeader AvisoConfirmHeader HeaderDesactivar">
                                    <div class="DeleteIcon">
                                        <i class="fa-solid fa-ban"></i>
                                    </div>
                                    <h4 class="fw-bold mb-1">CONFIRMAR DESACTIVACIÓN</h4>
                                    <p class="mb-0 opacity-75">AVISO</p>
                                </div>
                                <div class="DeleteModalBody">
                                    <p class="fs-6 fw-bold mb-3">¿DESEAS DESACTIVAR ESTE AVISO?</p>
                                    <div class="AvisoTituloModal mb-3">
                                        <?= HAviso($A['Titulo']) ?>
                                    </div>
                                    <div class="DeleteWarningBox mb-4">
                                        <i class="fa-solid fa-triangle-exclamation me-2"></i>
                                        Revisa bien antes de confirmar. El aviso dejará de mostrarse, pero podrás activarlo después.
                                    </div>
                                    <form method="POST" class="m-0">
                                        <?php echo CampoCsrf(); ?>
                                        <div class="d-flex justify-content-center gap-2 flex-wrap">
                                            <button type="button" class="BtnCancelDelete" data-bs-dismiss="modal">
                                                <i class="fa-solid fa-xmark"></i> CANCELAR
                                            </button>
                                            <button name="DesactivarAviso" value="<?= $AvisoId ?>" type="submit" class="BtnConfirmDelete BtnConfirmDesactivar">
                                                <i class="fa-solid fa-ban"></i> SÍ, DESACTIVAR
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- MODAL PARA ACTIVAR AVISO -->
                    <div class="modal fade ModalAvisoEstado" id="ModalActivarAviso<?= $AvisoId ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-sm ModalEliminarFijo">
                            <div class="modal-content DeleteModalContent AvisoConfirmContent">
                                <div class="DeleteModalHeader AvisoConfirmHeader HeaderActivar">
                                    <div class="DeleteIcon">
                                        <i class="fa-solid fa-circle-check"></i>
                                    </div>
                                    <h4 class="fw-bold mb-1">CONFIRMAR ACTIVACIÓN</h4>
                                    <p class="mb-0 opacity-75">AVISO</p>
                                </div>
                                <div class="DeleteModalBody">
                                    <p class="fs-6 fw-bold mb-3">¿DESEAS ACTIVAR ESTE AVISO?</p>
                                    <div class="AvisoTituloModal mb-3">
                                        <?= HAviso($A['Titulo']) ?>
                                    </div>
                                    <div class="DeleteWarningBox mb-4">
                                        <i class="fa-solid fa-triangle-exclamation me-2"></i>
                                        Revisa bien antes de confirmar. El aviso volverá a mostrarse al público seleccionado.
                                    </div>
                                    <form method="POST" class="m-0">
                                        <?php echo CampoCsrf(); ?>
                                        <div class="d-flex justify-content-center gap-2 flex-wrap">
                                            <button type="button" class="BtnCancelDelete" data-bs-dismiss="modal">
                                                <i class="fa-solid fa-xmark"></i> CANCELAR
                                            </button>
                                            <button name="ActivarAviso" value="<?= $AvisoId ?>" type="submit" class="BtnConfirmDelete BtnConfirmActivar">
                                                <i class="fa-solid fa-circle-check"></i> SÍ, ACTIVAR
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>


<!-- SGCE FIX16: modales profesionales para activar/desactivar avisos -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php ImprimirCsrfScript(); ?>






<!-- SGCE FIX12: Homologación final de botones superiores y reportes -->




<!-- SGCE FIX14: centrado final de modales y restauración visual -->




<!-- SGCE FIX17: modales de activar/desactivar igual al diseño de eliminación y centrado blindado -->





<!-- SGCE FIX18: botones de activar/desactivar avisos iguales al estilo tinto de eliminar -->




<!-- SGCE FIX19 DEFINITIVO: botones de activar/desactivar avisos en tinto, sin gris -->



<script src="assets/js/sgce-shared.js?v=44"></script>
<script src="assets/js/AvisosAdmin.js?v=44"></script>
</body>
</html>
