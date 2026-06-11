<?php if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); } ?>
<!DOCTYPE html>
<html lang="es">
<head>
<?= SgceLayoutHeadBase('Usuarios y Roles | SGCE', $Pdo, ['assets/css/usuarios-botones-metalicos.css', 'assets/css/components/pagination.css', 'assets/css/components/filter-bars.css', 'assets/css/modules/config-users-layout.css']) ?>
</head>
<body>
<div class="MainWrap SgceModuleWrap SgceUsersPage">
    <div class="TopBar">
        <div class="SgceHeroInfo">
            <div class="SgceHeroIcon"><span class="SgceColorIcon" aria-hidden="true">👥</span></div>
            <div>
                <h1>Usuarios y Roles</h1>
                <p>Alta, edición y control de acceso para administradores, maestros y personal escolar.</p>
            </div>
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
        <h2><span class="SgceColorIcon SgceTitleIcon" aria-hidden="true">👤</span> Nuevo usuario</h2>
        <form method="POST" class="row g-3 SgceUsersCreateForm">
            <?= CampoCsrf() ?>
            <input type="hidden" name="Accion" value="CrearUsuario">
            <div class="col-lg-4">
                <label class="form-label">Nombre completo</label>
                <input type="text" name="NombreCompleto" class="form-control UpperInput" maxlength="140" required placeholder="NOMBRE COMPLETO">
            </div>
            <div class="col-lg-2">
                <label class="form-label">Usuario</label>
                <input type="text" name="Username" class="form-control" maxlength="80" required placeholder="usuario">
            </div>
            <div class="col-lg-2">
                <label class="form-label">Contraseña</label>
                <input type="password" name="Password" class="form-control" required placeholder="contraseña" autocomplete="new-password">
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
                <button id="BtnGuardarUsuarioVerdeMetalico" class="BtnUserCreateSave BtnUsuarioGuardarMetalico w-100" type="submit"><span class="SgceColorIcon" aria-hidden="true">💾</span> Guardar</button>
            </div>
        </form>
    </div>

    <div class="CardPanel SgceUsersTableCard">
        <div class="SgceUsersTableHeader">
            <h2 class="mb-0"><span class="SgceColorIcon SgceTitleIcon" aria-hidden="true">📋</span> Usuarios registrados</h2>
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
                            <input form="<?= $FormEditar ?>" name="NombreCompleto" class="form-control form-control-sm UpperInput" maxlength="140" value="<?= htmlspecialchars($U['NombreCompleto']) ?>" required>
                        </td>
                        <td>
                            <input form="<?= $FormEditar ?>" name="Username" class="form-control form-control-sm" maxlength="80" value="<?= htmlspecialchars($U['Username']) ?>" required>
                        </td>
                        <td>
                            <input form="<?= $FormEditar ?>" type="password" name="Password" class="form-control form-control-sm" value="" placeholder="NUEVA CONTRASEÑA OPCIONAL" autocomplete="new-password">
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
                                        <button type="submit" class="BtnSmall BtnDisable" data-sgce-confirm="delete" data-sgce-confirm-title="DESACTIVAR USUARIO" data-sgce-confirm-subtitle="CONTROL DE ACCESO" data-sgce-confirm-message="¿DESEAS DESACTIVAR ESTE USUARIO?" data-sgce-confirm-detail="El usuario dejará de poder iniciar sesión, pero sus registros se conservarán en el sistema." data-sgce-confirm-button="SÍ, DESACTIVAR" data-sgce-confirm-loading="DESACTIVANDO..." data-sgce-confirm-icon="fa-user-slash" <?= ((int)$U['Id'] === (int)$UserSession['Id']) ? 'disabled' : '' ?>><i class="fa-solid fa-user-slash"></i> Baja</button>
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

<?= SgceLayoutSharedJs() ?>
</body>
</html>
