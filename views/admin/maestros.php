<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
?>
<?php if ($TabActual === 'maestros'): ?>
<div class="tab-pane fade show active SgceActivePane" id="maestros">
            <div class="row MaestrosLayoutRow GruposLayoutRow">

                <div class="col-xl-3 col-lg-4 MaestrosSideCol GruposSideCol">

                    <div class="card card-custom MaestrosSideCard GruposSideCard MaestrosRegisterCard GruposRegisterCard mb-3">
                        <div class="card-header-custom MaestrosCardTitle GruposCardTitle">
                            <span class="SgceColorIcon SgceTitleIcon" aria-hidden="true">📝</span> Registrar Maestro
                        </div>

                        <div class="card-body">
                            <form method="POST" class="MaestrosFormStack GruposFormStack">
                    <?php echo CampoCsrf(); ?>
                                <input type="hidden" name="AltaMaestro">
                                <input type="hidden" name="Tab" value="maestros">

                                <div class="MaestrosFieldGroup GruposFieldGroup">
                                    <label>Nombre completo</label>
                                    <input type="text"
                                           name="Nombre"
                                           class="form-control form-control-sm SoloLetrasMayus MaestrosInput GruposInput"
                                           placeholder="NOMBRE COMPLETO"
                                           maxlength="140"
                                           required
                                           pattern="^[A-ZÁÉÍÓÚÜÑ\s]+$"
                                           title="Solo letras y espacios"
                                           autocomplete="off">
                                </div>

                                <div class="MaestrosFieldGroup GruposFieldGroup">
                                    <label>Usuario</label>
                                    <input type="text" name="User" class="form-control form-control-sm TextoLibre MaestrosInput GruposInput" placeholder="USUARIO" maxlength="80" required autocomplete="off">
                                </div>

                                <div class="MaestrosFieldGroup GruposFieldGroup">
                                    <label>Contraseña</label>
                                    <input type="password" name="Pass" class="form-control form-control-sm TextoLibre MaestrosInput GruposInput" placeholder="CONTRASEÑA" required autocomplete="off">
                                </div>

                                <button type="submit" id="BtnGuardarMaestroVerdeMetalico" class="BtnMaestroGuardarMetalico w-100">
                                    <span class="SgceColorIcon" aria-hidden="true">💾</span> Guardar Maestro
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card card-custom MaestrosSideCard GruposSideCard MaestrosImportCard GruposImportCard">
                        <div class="card-header-custom MaestrosCardTitle MaestrosImportTitle GruposCardTitle GruposImportTitle">
                            <span class="SgceColorIcon SgceTitleIcon" aria-hidden="true">📥</span> Importar CSV / Excel
                        </div>

                        <div class="card-body">
                            <form action="Importar.php" method="POST" enctype="multipart/form-data" class="MaestrosFormStack GruposFormStack" data-sgce-confirm="import" data-sgce-confirm-title="CONFIRMAR IMPORTACIÓN" data-sgce-confirm-subtitle="IMPORTAR DOCENTES" data-sgce-confirm-message="¿REALMENTE DESEAS IMPORTAR ESTE ARCHIVO DE DOCENTES?" data-sgce-confirm-detail="Se procesará el archivo seleccionado para registrar docentes. Revisa que el formato sea NOMBRE, USUARIO, CONTRASEÑA antes de continuar." data-sgce-confirm-button="SÍ, IMPORTAR DOCENTES" data-sgce-confirm-loading="IMPORTANDO DOCENTES..." data-sgce-confirm-icon="fa-file-excel">
                    <?php echo CampoCsrf(); ?>
                                <input type="hidden" name="ImportarDocentes" value="1">
                                <input type="hidden" name="Tab" value="maestros">

                                <p class="MaestrosHelpText GruposHelpText">
                                    FORMATO CSV O EXCEL: <code>NOMBRE, USUARIO, CONTRASEÑA</code>
                                </p>

                                <div class="MaestrosFieldGroup GruposFieldGroup">
                                    <label>Archivo CSV o Excel</label>
                                    <input type="file" name="CsvDocentes" class="form-control form-control-sm MaestrosInput MaestrosFileInput GruposInput" accept=".csv,.xlsx" required>
                                </div>

                                <div class="SgceImportActions">
<button type="submit" id="BtnImportarMaestroAzulMetalico" class="BtnMaestroImportarMetalico SgceImportMainBtn">
                                        <span class="SgceColorIcon" aria-hidden="true">☁️</span> Cargar Archivo
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>

                <div class="col-xl-9 col-lg-8 MaestrosTableCol GruposTableCol">

                    <div class="card card-custom p-3 MaestrosTableCard GruposTableCard">

                        <div class="d-flex justify-content-between align-items-center mb-3 MaestrosTableTop GruposTableTop">
                            <h6 class="mb-0 text-muted SgceInlineTitle"><span class="SgceColorIcon SgceTitleIcon" aria-hidden="true">👥</span><span>Docentes Registrados</span></h6>

                            <div class="input-group input-group-sm search-container w-50">
                                <span class="input-group-text">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </span>

                                <input type="text" id="SearchMaestros" class="form-control" placeholder="Buscar docente o usuario...">
                            </div>
                        </div>

                        <div class="table-responsive">

                            <table class="table table-hover text-center align-middle" id="TableMaestros">

                                <thead>
                                    <tr>
                                        <th class="text-start">Nombre</th>
                                        <th>Usuario</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    <?php foreach($MaestrosTabla as $M): ?>
                                    <tr>
                                        <td class="text-start searchable"><?= htmlspecialchars($M['NombreCompleto'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="searchable"><?= htmlspecialchars($M['Username'], ENT_QUOTES, 'UTF-8') ?></td>

                                        <td class="text-center">
                                            <div class="AdminActions">
<button class="ActionBtn ActionEdit BtnTeacherEdit" data-bs-toggle="modal" data-bs-target="#EM<?= (int)$M['Id'] ?>">
                                                <i class="fa-solid fa-pen-to-square"></i><span>Editar</span>
                                            </button>

                                            <form method="POST" class="m-0 p-0" data-confirm-delete="DOCENTE" data-confirm-message="¿DESEAS DESACTIVAR ESTE DOCENTE? SI TIENE ASIGNACIONES ACTIVAS EL SISTEMA LO BLOQUEARÁ HASTA HACER RELEVO/INTERINATO.">
                    <?php echo CampoCsrf(); ?>
                                                <input type="hidden" name="Tab" value="maestros">
                                                <button type="submit" name="DelMaestro" value="<?= (int)$M['Id'] ?>" class="ActionBtn ActionDelete BtnTeacherDelete">
                                                    <i class="fa-solid fa-trash-can"></i><span>Desactivar</span>
                                                </button>
                                            </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                        
                        <div id="PagerMaestros" class="SgcePagerServer SgceClientPager"></div>

                    </div>

                </div>

            </div>

            <?php foreach($MaestrosTabla as $M): ?>
            <div class="modal fade" id="EM<?= (int)$M['Id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered modal-sm">
                    <div class="modal-content">

                        <form method="POST">
                    <?php echo CampoCsrf(); ?>
                            <div class="modal-body">

                                <h6 class="mb-3 border-bottom pb-2">Modificar Docente</h6>

                                <input type="hidden" name="EditMaestro">
                                <input type="hidden" name="Tab" value="maestros">
                                <input type="hidden" name="Id" value="<?= (int)$M['Id'] ?>">

                                <label class="small text-muted">Nombre</label>
                                <input type="text"
                                       name="Nombre"
                                       value="<?= htmlspecialchars($M['NombreCompleto'], ENT_QUOTES, 'UTF-8') ?>"
                                       class="form-control form-control-sm mb-2 SoloLetrasMayus"
                                       required
                                       pattern="^[A-ZÁÉÍÓÚÜÑ\s]+$"
                                       title="Solo letras y espacios"
                                       autocomplete="off">

                                <label class="small text-muted">USUARIO</label>
                                <input type="text"
                                       name="User"
                                       value="<?= htmlspecialchars($M['Username'], ENT_QUOTES, 'UTF-8') ?>"
                                       class="form-control form-control-sm mb-2 TextoLibre"
                                       required
                                       autocomplete="off">

                                <label class="small text-muted">CONTRASEÑA</label>
                                <input type="password"
                                       name="Pass"
                                       value="" placeholder="NUEVA CONTRASEÑA OPCIONAL"
                                       class="form-control form-control-sm mb-3 TextoLibre"
                                       autocomplete="off">

                                <button class="btn btn-sm btn-success w-100"><i class="fa-solid fa-floppy-disk"></i> Guardar Cambios</button>

                            </div>
                        </form>

                    </div>
                </div>
            </div>
            <?php endforeach; ?>

        </div>

        

        
<?php endif; ?>
