<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
/*
    Archivo: AvisosAdmin.php
    Descripción: Módulo administrativo para administrar avisos y comunicados.
    Permite crear, editar, activar y desactivar avisos para maestros, padres o todo el sistema.
    Todos los datos visibles se normalizan en mayúsculas para mantener uniforme el sistema SGCE.
*/

require_once dirname(__DIR__) . '/config/Conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$UserSession = VerificarSesionCookie($Pdo);

if (!$UserSession || !SgcePuedeGestionarAvisos($UserSession)) {
    header('Location: index.php');
    exit;
}

// =====================================================
// FUNCIONES AUXILIARES
// =====================================================

// Sanitiza texto para imprimirlo seguro en HTML.
function HAviso($Texto) {
    return htmlspecialchars((string)$Texto, ENT_QUOTES, 'UTF-8');
}

// Normaliza textos de avisos a mayúsculas, respetando acentos y Ñ cuando mbstring está disponible.
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

// Público permitido para avisos.
function PublicoAvisoValido($Publico) {
    $Publico = MayusAviso($Publico);
    return in_array($Publico, ['TODOS', 'MAESTROS', 'PADRES'], true) ? $Publico : 'TODOS';
}

// Redirección segura para evitar reenvío de formularios.
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

$PaginaAvisos = SgcePaginaActual('PagAvisos', 1);
$PorPaginaAvisos = 7;
[$OffsetAvisos, $LimitAvisos] = SgceLimitOffset($PaginaAvisos, $PorPaginaAvisos);

$TotalAvisos = (int)$Pdo->query("SELECT COUNT(*) FROM Avisos")->fetchColumn();

$StmtAvisos = $Pdo->prepare("
    SELECT *
    FROM Avisos
    ORDER BY Activo DESC, FechaCreacion DESC, Id DESC
    LIMIT :Limit OFFSET :Offset
");
$StmtAvisos->bindValue(':Limit', $LimitAvisos, PDO::PARAM_INT);
$StmtAvisos->bindValue(':Offset', $OffsetAvisos, PDO::PARAM_INT);
$StmtAvisos->execute();
$Avisos = $StmtAvisos->fetchAll();
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
<link rel="stylesheet" href="assets/css/sgce-base.css?cache=sgce2026final">
<?= SgceEstilosTema($Pdo) ?>
</head>
<body class="AvisosBody">

<main class="SgcePageWrap SgceModuleWrap AvisosWrap">

    <section class="SgceHero AvisosHero">
        <div class="SgceHeroInfo">
            <div class="SgceHeroIcon">
                <i class="fa-solid fa-bullhorn"></i>
            </div>
            <div>
                <h1>AVISOS Y COMUNICADOS</h1>
                <p>Publica avisos para maestros, padres o todo el sistema.</p>
            </div>
        </div>
        <div class="SgceHeroActions">
            <a href="Admin.php?Tab=inicio" class="SgceBtnVolverInicio" title="Volver al inicio" aria-label="Volver al inicio"><i class="fa-solid fa-house"></i><span>Volver al inicio</span></a>
        </div>
    </section>

    <?php if (isset($_SESSION['Mensaje'])): ?>
        <div class="alert alert-<?= HAviso($_SESSION['MensajeTipo'] ?? 'success') ?> AlertAuto alert-dismissible fade show mb-4">
            <i class="fa-solid fa-circle-info me-2"></i>
            <?= HAviso($_SESSION['Mensaje']) ?>
            <?php unset($_SESSION['Mensaje'], $_SESSION['MensajeTipo']); ?>
            <button class="btn-close" data-bs-dismiss="alert" type="button" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>

    <section class="AvisosLayout">
        <div class="SgceCard AvisosFormCard">
            <div class="SgceCardHeaderLine">
                <div class="SgceMiniIcon"><i class="fa-solid fa-plus"></i></div>
                <div>
                    <h2>NUEVO AVISO</h2>
                    <p>Captura un comunicado y define a quién se mostrará.</p>
                </div>
            </div>

            <form method="POST" class="AvisosForm">
                <?php echo CampoCsrf(); ?>
                <input type="hidden" name="CrearAviso" value="1">

                <label>TÍTULO</label>
                <input name="Titulo" class="form-control" required placeholder="TÍTULO DEL AVISO" autocomplete="off">

                <label>PÚBLICO</label>
                <select name="Publico" class="form-select">
                    <option value="TODOS">TODOS</option>
                    <option value="MAESTROS">MAESTROS</option>
                    <option value="PADRES">PADRES</option>
                </select>

                <label>MENSAJE</label>
                <textarea name="Mensaje" class="form-control" rows="6" required placeholder="ESCRIBE EL COMUNICADO"></textarea>

                <button class="BtnPrimary AvisosSubmit BtnAvisoPublish" type="submit">
                    <i class="fa-solid fa-paper-plane"></i>
                    <span>PUBLICAR AVISO</span>
                </button>
            </form>
        </div>

        <div class="SgceCard AvisosTableCard">
            <div class="SgceCardHeaderLine AvisosTableHeader AvisosTableHeaderClean">
                <div class="SgceMiniIcon"><i class="fa-solid fa-list-check"></i></div>
                <div>
                    <h2>AVISOS REGISTRADOS</h2>
                </div>
            </div>

            <div class="table-responsive AvisosTableResponsive">
                <table class="table align-middle AvisosTable">
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
                                <td class="AvisoFecha"><?= HAviso(date('d/m/Y H:i', strtotime($A['FechaCreacion']))) ?></td>
                                <td class="fw-bold AvisoTituloTabla"><?= HAviso($A['Titulo']) ?></td>
                                <td><span class="BadgePublico"><?= HAviso($A['Publico']) ?></span></td>
                                <td>
                                    <span class="BadgeEstado <?= $EstaActivo ? '' : 'BadgeInactivo' ?>">
                                        <?= $EstaActivo ? 'ACTIVO' : 'INACTIVO' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="AccionesAviso">
                                        <button type="button" class="ActionBtn BtnAvisoEdit" data-bs-toggle="modal" data-bs-target="#ModalEditarAviso<?= $AvisoId ?>">
                                            <i class="fa-solid fa-pen-to-square"></i><span>EDITAR</span>
                                        </button>

                                        <?php if ($EstaActivo): ?>
                                            <button type="button" class="ActionBtn BtnAvisoDeactivate" data-bs-toggle="modal" data-bs-target="#ModalDesactivarAviso<?= $AvisoId ?>">
                                                <i class="fa-solid fa-ban"></i><span>DESACTIVAR</span>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="ActionBtn BtnAvisoActivate" data-bs-toggle="modal" data-bs-target="#ModalActivarAviso<?= $AvisoId ?>">
                                                <i class="fa-solid fa-circle-check"></i><span>ACTIVAR</span>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($Avisos)): ?>
                            <tr>
                                <td colspan="5" class="py-5 text-center text-muted fw-bold">SIN AVISOS REGISTRADOS.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?= SgceRenderPager('PagAvisos', $PaginaAvisos, $TotalAvisos, $PorPaginaAvisos) ?>
        </div>
    </section>
</main>
<?php foreach ($Avisos as $A): ?>
<?php
    $AvisoId = (int)$A['Id'];
    $EstaActivoModal = (int)$A['Activo'] === 1;
?>

<div class="modal fade" id="ModalEditarAviso<?= $AvisoId ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered ModalEditarPro">
        <div class="modal-content EditModalContent">
            <form method="POST" class="m-0">
                <?php echo CampoCsrf(); ?>
                <input type="hidden" name="EditarAviso" value="1">
                <input type="hidden" name="AvisoId" value="<?= $AvisoId ?>">

                <div class="EditModalHeader">
                    <div class="EditIcon"><i class="fa-solid fa-pen-to-square"></i></div>
                    <h4>EDITAR AVISO</h4>
                    <p>Actualiza el comunicado seleccionado</p>
                </div>

                <div class="EditModalBody">
                    <div class="EditInfoBox">
                        <i class="fa-solid fa-circle-info me-2"></i>
                        LOS CAMBIOS SE GUARDARÁN AL CONFIRMAR.
                    </div>

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

                    <div class="row g-2 mt-3">
                        <div class="col-md-6">
                            <button type="button" class="BtnCancelEdit" data-bs-dismiss="modal">
                                <i class="fa-solid fa-xmark"></i>
                                CANCELAR
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button type="submit" class="BtnSaveEdit BtnAvisoModalSave">
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

<?php if ($EstaActivoModal): ?>
<div class="modal fade ModalAvisoEstado" id="ModalDesactivarAviso<?= $AvisoId ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm ModalEliminarFijo">
        <div class="modal-content DeleteModalContent AvisoConfirmContent">
            <div class="DeleteModalHeader AvisoConfirmHeader HeaderDesactivar">
                <div class="DeleteIcon"><i class="fa-solid fa-ban"></i></div>
                <h4 class="fw-bold mb-1">CONFIRMAR DESACTIVACIÓN</h4>
                <p class="mb-0 opacity-75">AVISO</p>
            </div>
            <div class="DeleteModalBody">
                <p class="fs-6 fw-bold mb-3">¿DESEAS DESACTIVAR ESTE AVISO?</p>
                <div class="AvisoTituloModal mb-3"><?= HAviso($A['Titulo']) ?></div>
                <div class="DeleteWarningBox mb-4">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>
                    Revisa bien antes de confirmar. El aviso dejará de mostrarse, pero podrás activarlo después.
                </div>
                <form method="POST" class="m-0">
                    <?php echo CampoCsrf(); ?>
                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                        <button type="button" class="BtnCancelDelete" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> CANCELAR</button>
                        <button name="DesactivarAviso" value="<?= $AvisoId ?>" type="submit" class="BtnConfirmDelete BtnConfirmDesactivar BtnAvisoModalDeactivate"><i class="fa-solid fa-ban"></i> SÍ, DESACTIVAR</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="modal fade ModalAvisoEstado" id="ModalActivarAviso<?= $AvisoId ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm ModalEliminarFijo">
        <div class="modal-content DeleteModalContent AvisoConfirmContent">
            <div class="DeleteModalHeader AvisoConfirmHeader HeaderActivar">
                <div class="DeleteIcon"><i class="fa-solid fa-circle-check"></i></div>
                <h4 class="fw-bold mb-1">CONFIRMAR ACTIVACIÓN</h4>
                <p class="mb-0 opacity-75">AVISO</p>
            </div>
            <div class="DeleteModalBody">
                <p class="fs-6 fw-bold mb-3">¿DESEAS ACTIVAR ESTE AVISO?</p>
                <div class="AvisoTituloModal mb-3"><?= HAviso($A['Titulo']) ?></div>
                <div class="DeleteWarningBox mb-4">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>
                    Revisa bien antes de confirmar. El aviso volverá a mostrarse al público seleccionado.
                </div>
                <form method="POST" class="m-0">
                    <?php echo CampoCsrf(); ?>
                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                        <button type="button" class="BtnCancelDelete" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> CANCELAR</button>
                        <button name="ActivarAviso" value="<?= $AvisoId ?>" type="submit" class="BtnConfirmDelete BtnConfirmActivar BtnAvisoModalActivate"><i class="fa-solid fa-circle-check"></i> SÍ, ACTIVAR</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endforeach; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php ImprimirCsrfScript(); ?>
<script src="assets/js/sgce-shared.js?cache=sgce2026final"></script>
<script src="assets/js/AvisosAdmin.js?cache=sgce2026final"></script>
</body>
</html>
