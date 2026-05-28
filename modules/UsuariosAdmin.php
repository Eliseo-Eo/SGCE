<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
require_once dirname(__DIR__) . '/config/Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession || !SgcePuedeGestionarUsuarios($UserSession)) { header('Location: index.php'); exit; }

$Roles = SgceRolesSistema();
$Mensaje = trim((string)($_GET['M'] ?? ''));
$Error = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    RequerirCsrfPost();
    $Accion = (string)($_POST['Accion'] ?? '');

    try {
        if ($Accion === 'CrearUsuario') {
            $NombreCompleto = SgceNormalizarTextoUsuarios($_POST['NombreCompleto'] ?? '');
            $Username = trim((string)($_POST['Username'] ?? ''));
            $Password = trim((string)($_POST['Password'] ?? ''));
            $Rol = trim((string)($_POST['Rol'] ?? ''));

            if ($NombreCompleto === '' || $Username === '' || $Password === '' || !SgceValidarRolUsuario($Rol, $Roles)) {
                throw new Exception('Completa nombre, usuario, contraseña y rol válido.');
            }

            $ValidacionPassword = SgceValidarPasswordFuerte($Password);
            if ($ValidacionPassword !== true) { throw new Exception($ValidacionPassword); }

            if (!preg_match('/^[a-zA-Z0-9._@-]{3,80}$/', $Username)) {
                throw new Exception('El usuario debe tener de 3 a 80 caracteres y solo puede usar letras, números, punto, guion, guion bajo o @.');
            }

            $StmtExiste = $Pdo->prepare("SELECT COUNT(*) FROM Usuarios WHERE Username = ?");
            $StmtExiste->execute([$Username]);
            if ((int)$StmtExiste->fetchColumn() > 0) {
                throw new Exception('Ya existe un usuario con ese nombre de acceso.');
            }

            $Stmt = $Pdo->prepare("INSERT INTO Usuarios (Username, Password, NombreCompleto, Rol, Activo, SessionToken) VALUES (?, ?, ?, ?, 1, NULL)");
            $Stmt->execute([$Username, SgcePasswordHash($Password), $NombreCompleto, $Rol]);
            RegistrarBitacora($Pdo, $UserSession, 'CREAR_USUARIO', 'Usuarios', (int)$Pdo->lastInsertId(), 'ALTA DE USUARIO CON ROL: ' . strtoupper($Rol));
            header('Location: UsuariosAdmin.php?M=' . urlencode('Usuario creado correctamente'));
            exit;
        }

        if ($Accion === 'EditarUsuario') {
            $Id = (int)($_POST['Id'] ?? 0);
            $NombreCompleto = SgceNormalizarTextoUsuarios($_POST['NombreCompleto'] ?? '');
            $Username = trim((string)($_POST['Username'] ?? ''));
            $Password = trim((string)($_POST['Password'] ?? ''));
            $Rol = trim((string)($_POST['Rol'] ?? ''));
            $Activo = isset($_POST['Activo']) ? 1 : 0;

            if ($Id <= 0 || $NombreCompleto === '' || $Username === '' || !SgceValidarRolUsuario($Rol, $Roles)) {
                throw new Exception('Datos incompletos o rol inválido.');
            }

            if ($Password !== '') {
                $ValidacionPassword = SgceValidarPasswordFuerte($Password);
                if ($ValidacionPassword !== true) { throw new Exception($ValidacionPassword); }
            }

            if (!preg_match('/^[a-zA-Z0-9._@-]{3,80}$/', $Username)) {
                throw new Exception('El usuario debe tener de 3 a 80 caracteres y solo puede usar letras, números, punto, guion, guion bajo o @.');
            }

            $StmtActual = $Pdo->prepare("SELECT Id, Rol, Activo FROM Usuarios WHERE Id = ? LIMIT 1");
            $StmtActual->execute([$Id]);
            $Actual = $StmtActual->fetch();
            if (!$Actual) { throw new Exception('El usuario no existe.'); }

            if ((int)$Id === (int)$UserSession['Id'] && ($Activo === 0 || $Rol !== 'admin')) {
                throw new Exception('No puedes quitarte a ti mismo el acceso de administrador ni desactivarte.');
            }

            if ($Actual['Rol'] === 'admin' && ($Rol !== 'admin' || $Activo === 0) && SgceContarAdminsActivos($Pdo) <= 1) {
                throw new Exception('Debe existir al menos un administrador activo.');
            }

            $StmtExiste = $Pdo->prepare("SELECT COUNT(*) FROM Usuarios WHERE Username = ? AND Id <> ?");
            $StmtExiste->execute([$Username, $Id]);
            if ((int)$StmtExiste->fetchColumn() > 0) {
                throw new Exception('Ya existe otro usuario con ese nombre de acceso.');
            }

            $SessionSql = $Activo ? '' : ', SessionToken = NULL, SessionTokenExpira = NULL';
            $PasswordSql = $Password !== '' ? ', Password = ?' : '';
            $Params = [$Username, $NombreCompleto, $Rol, $Activo];
            if ($Password !== '') { $Params[] = SgcePasswordHash($Password); }
            $Params[] = $Id;
            $Stmt = $Pdo->prepare("UPDATE Usuarios SET Username = ?, NombreCompleto = ?, Rol = ?, Activo = ? $PasswordSql $SessionSql WHERE Id = ?");
            $Stmt->execute($Params);
            RegistrarBitacora($Pdo, $UserSession, 'EDITAR_USUARIO', 'Usuarios', $Id, 'EDICIÓN DE USUARIO. ROL: ' . strtoupper($Rol));
            header('Location: UsuariosAdmin.php?M=' . urlencode('Usuario actualizado correctamente'));
            exit;
        }

        if ($Accion === 'DesactivarUsuario') {
            $Id = (int)($_POST['Id'] ?? 0);
            if ($Id <= 0) { throw new Exception('Usuario inválido.'); }
            if ((int)$Id === (int)$UserSession['Id']) { throw new Exception('No puedes desactivar tu propio usuario.'); }

            $StmtActual = $Pdo->prepare("SELECT Rol FROM Usuarios WHERE Id = ? LIMIT 1");
            $StmtActual->execute([$Id]);
            $RolActual = (string)$StmtActual->fetchColumn();
            if ($RolActual === '') { throw new Exception('El usuario no existe.'); }
            if ($RolActual === 'admin' && SgceContarAdminsActivos($Pdo) <= 1) {
                throw new Exception('Debe existir al menos un administrador activo.');
            }

            $Stmt = $Pdo->prepare("UPDATE Usuarios SET Activo = 0, SessionToken = NULL WHERE Id = ?");
            $Stmt->execute([$Id]);
            RegistrarBitacora($Pdo, $UserSession, 'DESACTIVAR_USUARIO', 'Usuarios', $Id, 'USUARIO DESACTIVADO DESDE MÓDULO DE USUARIOS');
            header('Location: UsuariosAdmin.php?M=' . urlencode('Usuario desactivado correctamente'));
            exit;
        }

        if ($Accion === 'ReactivarUsuario') {
            $Id = (int)($_POST['Id'] ?? 0);
            if ($Id <= 0) { throw new Exception('Usuario inválido.'); }
            $Stmt = $Pdo->prepare("UPDATE Usuarios SET Activo = 1 WHERE Id = ?");
            $Stmt->execute([$Id]);
            RegistrarBitacora($Pdo, $UserSession, 'REACTIVAR_USUARIO', 'Usuarios', $Id, 'USUARIO REACTIVADO DESDE MÓDULO DE USUARIOS');
            header('Location: UsuariosAdmin.php?M=' . urlencode('Usuario reactivado correctamente'));
            exit;
        }
    } catch (Exception $E) {
        $Error = $E->getMessage();
    }
}

$FiltroRol = trim((string)($_GET['Rol'] ?? ''));
$FiltroEstado = trim((string)($_GET['Estado'] ?? 'activos'));
$Buscar = trim((string)($_GET['Buscar'] ?? ''));
$Pagina = SgcePaginaActual('PagUsuarios', 1);
$PorPagina = 7;
[$Offset, $Limit] = SgceLimitOffset($Pagina, $PorPagina);

$Where = [];
$Params = [];
if ($FiltroRol !== '' && SgceValidarRolUsuario($FiltroRol, $Roles)) { $Where[] = 'Rol = ?'; $Params[] = $FiltroRol; }
if ($FiltroEstado === 'activos') { $Where[] = 'Activo = 1'; }
elseif ($FiltroEstado === 'inactivos') { $Where[] = 'Activo = 0'; }
if ($Buscar !== '') {
    $Where[] = '(NombreCompleto LIKE ? OR Username LIKE ?)';
    $Params[] = '%' . $Buscar . '%';
    $Params[] = '%' . $Buscar . '%';
}
$WhereSql = $Where ? ('WHERE ' . implode(' AND ', $Where)) : '';

$StmtTotal = $Pdo->prepare("SELECT COUNT(*) FROM Usuarios $WhereSql");
$StmtTotal->execute($Params);
$TotalUsuarios = (int)$StmtTotal->fetchColumn();

$Stmt = $Pdo->prepare("SELECT Id, Username, NombreCompleto, Rol, Activo FROM Usuarios $WhereSql ORDER BY Activo DESC, FIELD(Rol,'admin','director','secretario','coordinador','prefecto','maestro'), NombreCompleto ASC LIMIT $Limit OFFSET $Offset");
$Stmt->execute($Params);
$Usuarios = $Stmt->fetchAll();

$Stats = [];
foreach ($Roles as $Key => $Label) {
    $St = $Pdo->prepare("SELECT COUNT(*) FROM Usuarios WHERE Rol = ? AND Activo = 1");
    $St->execute([$Key]);
    $Stats[$Key] = (int)$St->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios y Roles | SGCE</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/sgce-base.css?v=1.0.0">
<?= SgceEstilosTema($Pdo) ?>
</head>
<body>
<div class="MainWrap SgceModuleWrap SgceUsersPage">
    <div class="TopBar">
        <div>
            <h1><i class="fa-solid fa-users-gear"></i> Usuarios y Roles</h1>
            <p>Alta, edición y control de acceso para administradores, maestros y personal escolar.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="Admin.php?Tab=inicio" class="SgceBtnVolverInicio" title="Volver al inicio" aria-label="Volver al inicio"><i class="fa-solid fa-house"></i><span>Volver al inicio</span></a>
        </div>
    </div>

    <?php if ($Mensaje !== ''): ?><div class="alert alert-success shadow-sm"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($Mensaje) ?></div><?php endif; ?>
    <?php if ($Error !== ''): ?><div class="alert alert-danger shadow-sm"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($Error) ?></div><?php endif; ?>

    <div class="StatsGrid SgceUsersStats">
        <?php foreach ($Roles as $Key => $Label): ?>
            <div class="StatCard">
                <span><?= htmlspecialchars($Label) ?></span>
                <strong><?= (int)$Stats[$Key] ?></strong>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="CardPanel SgceUsersCreateCard">
        <h2><i class="fa-solid fa-user-plus"></i> Nuevo usuario</h2>
        <form method="POST" class="row g-3 SgceUsersCreateForm">
            <?= CampoCsrf() ?>
            <input type="hidden" name="Accion" value="CrearUsuario">
            <div class="col-lg-4">
                <label class="form-label">Nombre completo</label>
                <input type="text" name="NombreCompleto" class="form-control UpperInput" required placeholder="NOMBRE COMPLETO">
            </div>
            <div class="col-lg-2">
                <label class="form-label">Usuario</label>
                <input type="text" name="Username" class="form-control" required placeholder="usuario">
            </div>
            <div class="col-lg-2">
                <label class="form-label">Contraseña</label>
                <input type="password" name="Password" class="form-control" required placeholder="contraseña">
            </div>
            <div class="col-lg-2">
                <label class="form-label">Rol</label>
                <select name="Rol" class="form-select" required>
                    <?php foreach ($Roles as $Key => $Label): ?>
                        <option value="<?= htmlspecialchars($Key) ?>"><?= htmlspecialchars($Label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2 d-flex align-items-end">
                <button class="BtnPrimary BtnUserCreateSave w-100" type="submit"><i class="fa-solid fa-floppy-disk"></i> Guardar</button>
            </div>
        </form>
    </div>

    <div class="CardPanel SgceUsersTableCard">
        <div class="SgceUsersTableHeader">
            <h2 class="mb-0"><i class="fa-solid fa-list-check"></i> Usuarios registrados</h2>
            <form method="GET" class="FilterBar SgceUsersFilterBar">
                <input type="text" name="Buscar" value="<?= htmlspecialchars($Buscar) ?>" class="form-control" placeholder="Buscar nombre o usuario">
                <select name="Rol" class="form-select">
                    <option value="">Todos los roles</option>
                    <?php foreach ($Roles as $Key => $Label): ?>
                        <option value="<?= htmlspecialchars($Key) ?>" <?= $FiltroRol===$Key?'selected':'' ?>><?= htmlspecialchars($Label) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="Estado" class="form-select">
                    <option value="activos" <?= $FiltroEstado==='activos'?'selected':'' ?>>Activos</option>
                    <option value="inactivos" <?= $FiltroEstado==='inactivos'?'selected':'' ?>>Inactivos</option>
                    <option value="todos" <?= $FiltroEstado==='todos'?'selected':'' ?>>Todos</option>
                </select>
                <button class="BtnFilter" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table align-middle UsuariosTable SgceUsersTable">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Usuario</th>
                        <th>Contraseña</th>
                        <th>Rol</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($Usuarios as $U): ?>
                    <?php $FormEditar = 'FormEditarUsuario' . (int)$U['Id']; ?>
                    <tr>
                        <td>
                            <input form="<?= $FormEditar ?>" name="NombreCompleto" class="form-control form-control-sm UpperInput" value="<?= htmlspecialchars($U['NombreCompleto']) ?>" required>
                        </td>
                        <td>
                            <input form="<?= $FormEditar ?>" name="Username" class="form-control form-control-sm" value="<?= htmlspecialchars($U['Username']) ?>" required>
                        </td>
                        <td>
                            <input form="<?= $FormEditar ?>" name="Password" class="form-control form-control-sm" value="" placeholder="NUEVA CONTRASEÑA OPCIONAL">
                        </td>
                        <td>
                            <select form="<?= $FormEditar ?>" name="Rol" class="form-select form-select-sm" <?= ((int)$U['Id'] === (int)$UserSession['Id']) ? 'disabled' : '' ?>>
                                <?php foreach ($Roles as $Key => $Label): ?>
                                    <option value="<?= htmlspecialchars($Key) ?>" <?= $U['Rol']===$Key?'selected':'' ?>><?= htmlspecialchars($Label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ((int)$U['Id'] === (int)$UserSession['Id']): ?>
                                <input form="<?= $FormEditar ?>" type="hidden" name="Rol" value="<?= htmlspecialchars($U['Rol']) ?>">
                            <?php endif; ?>
                        </td>
                        <td class="text-center SgceUserStatusCell">
                            <label class="SgceSwitch <?= ((int)$U['Id'] === (int)$UserSession['Id']) ? 'IsDisabled' : '' ?>" title="<?= ((int)$U['Activo'] === 1) ? 'Usuario activo' : 'Usuario inactivo' ?>">
                                <input form="<?= $FormEditar ?>" class="SgceSwitchInput" type="checkbox" name="Activo" <?= ((int)$U['Activo'] === 1) ? 'checked' : '' ?> <?= ((int)$U['Id'] === (int)$UserSession['Id']) ? 'disabled' : '' ?>>
                                <span class="SgceSwitchSlider" aria-hidden="true"></span>
                                <span class="SgceSwitchText"><?= ((int)$U['Activo'] === 1) ? 'Activo' : 'Inactivo' ?></span>
                                <?php if ((int)$U['Id'] === (int)$UserSession['Id']): ?>
                                    <input form="<?= $FormEditar ?>" type="hidden" name="Activo" value="1">
                                <?php endif; ?>
                            </label>
                        </td>
                        <td class="text-center">
                            <div class="ActionsInline">
                                <form id="<?= $FormEditar ?>" method="POST">
                                    <?= CampoCsrf() ?>
                                    <input type="hidden" name="Accion" value="EditarUsuario">
                                    <input type="hidden" name="Id" value="<?= (int)$U['Id'] ?>">
                                    <button type="submit" class="BtnSmall BtnUserSave"><i class="fa-solid fa-pen-to-square"></i> Modificar</button>
                                </form>
                                <?php if ((int)$U['Activo'] === 1): ?>
                                    <?php $FormBaja = 'FormBajaUsuario' . (int)$U['Id']; ?>
                                    <form id="<?= $FormBaja ?>" method="POST">
                                        <?= CampoCsrf() ?>
                                        <input type="hidden" name="Accion" value="DesactivarUsuario">
                                        <input type="hidden" name="Id" value="<?= (int)$U['Id'] ?>">
                                        <button type="button"
                                                class="BtnSmall BtnDisable BtnAbrirBajaUsuario"
                                                data-bs-toggle="modal"
                                                data-bs-target="#ModalBajaUsuario"
                                                data-form-id="<?= $FormBaja ?>"
                                                data-usuario="<?= htmlspecialchars($U['NombreCompleto']) ?>"
                                                <?= ((int)$U['Id'] === (int)$UserSession['Id']) ? 'disabled' : '' ?>>
                                            <i class="fa-solid fa-user-slash"></i> Baja
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST">
                                        <?= CampoCsrf() ?>
                                        <input type="hidden" name="Accion" value="ReactivarUsuario">
                                        <input type="hidden" name="Id" value="<?= (int)$U['Id'] ?>">
                                        <button type="submit" class="BtnSmall BtnReactivate"><i class="fa-solid fa-user-check"></i> Reactivar</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$Usuarios): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No hay usuarios con esos filtros.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?= SgceRenderPager('PagUsuarios', $Pagina, $TotalUsuarios, $PorPagina) ?>
    </div>
</div>

<div class="modal fade" id="ModalBajaUsuario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content DeleteModalContent SgceUserDisableModal">
            <div class="DeleteModalHeader">
                <div class="DeleteIcon"><i class="fa-solid fa-user-slash"></i></div>
                <h4>Desactivar usuario</h4>
                <p>Confirma la baja del acceso seleccionado</p>
            </div>
            <div class="DeleteModalBody">
                <p class="mb-3">El usuario dejará de poder iniciar sesión, pero sus registros se conservarán en el sistema.</p>
                <div class="DeleteWarningBox mb-4">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span id="TextoBajaUsuario">Esta acción desactivará el usuario seleccionado.</span>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <button type="button" class="BtnCancelDelete" data-bs-dismiss="modal">
                            <i class="fa-solid fa-xmark"></i> Cancelar
                        </button>
                    </div>
                    <div class="col-6">
                        <button type="button" class="BtnConfirmDelete BtnConfirmarBajaUsuario">
                            <i class="fa-solid fa-user-slash"></i> Dar de baja
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/sgce-shared.js?v=1.0.0"></script>
<script src="assets/js/UsuariosAdmin.js?v=1.0.0"></script>
</body>
</html>
