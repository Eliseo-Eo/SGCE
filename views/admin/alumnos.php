<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
?>
<?php if ($TabActual === 'alumnos'): ?>
<div class="tab-pane fade show active SgceActivePane" id="alumnos">
            <div class="row MaestrosLayoutRow AlumnosLayoutRow">

                <div class="col-xl-3 col-lg-4 MaestrosSideCol AlumnosSideCol">

                    <div class="card card-custom MaestrosSideCard AlumnosSideCard AlumnosRegisterCard mb-3">
                        <div class="card-header-custom MaestrosCardTitle AlumnosCardTitle">
                            <span class="SgceColorIcon SgceTitleIcon" aria-hidden="true">🧒</span> Inscribir Alumno
                        </div>

                        <div class="card-body">
                            <form method="POST" class="MaestrosFormStack AlumnosFormStack">
                    <?php echo CampoCsrf(); ?>
                                <input type="hidden" name="AltaAlumno">
                                <input type="hidden" name="Tab" value="alumnos">

                                <div class="MaestrosFieldGroup AlumnosFieldGroup">
                                    <label>Nombre completo</label>
                                    <input type="text"
                                           name="Nombre"
                                           class="form-control form-control-sm MaestrosInput AlumnosInput SoloLetrasMayus"
                                           placeholder="NOMBRE COMPLETO"
                                           maxlength="160"
                                           required
                                           pattern="^[A-ZÁÉÍÓÚÜÑ\s]+$"
                                           title="Solo letras y espacios"
                                           autocomplete="off">
                                </div>

                                <div class="MaestrosFieldGroup AlumnosFieldGroup">
                                    <label>Matrícula <small class="text-muted">(opcional)</small></label>
                                    <input type="text" name="Matricula" class="form-control form-control-sm MaestrosInput AlumnosInput InputUpperAscii" placeholder="AUTOMÁTICA" maxlength="40" autocomplete="off">
                                </div>

                                <div class="MaestrosFieldGroup AlumnosFieldGroup">
                                    <label>Grupo</label>
                                    <select name="GrupoId" class="form-select form-select-sm MaestrosInput AlumnosInput" required>
                                        <option value="">SELECCIONAR...</option>
                                        <?php foreach($Grupos as $G): ?>
                                            <option value="<?= (int)$G['Id'] ?>">
                                                <?= htmlspecialchars(SgceGrupoNombreVisual($G, $TipoPeriodizacionAdmin), ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <button type="submit" id="BtnGuardarAlumnoVerdeMetalico" class="BtnAlumnoGuardarMetalico w-100">
                                    <span class="SgceColorIcon" aria-hidden="true">✅</span> Registrar Alumno
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card card-custom MaestrosSideCard AlumnosSideCard AlumnosImportCard">
                        <div class="card-header-custom MaestrosCardTitle AlumnosCardTitle AlumnosImportTitle">
                            <span class="SgceColorIcon SgceTitleIcon" aria-hidden="true">📥</span> Importar Datos
                        </div>

                        <div class="card-body">
                            <form action="Importar.php" method="POST" enctype="multipart/form-data" class="MaestrosFormStack AlumnosFormStack" data-sgce-confirm="import" data-sgce-confirm-title="CONFIRMAR IMPORTACIÓN" data-sgce-confirm-subtitle="IMPORTAR ALUMNOS" data-sgce-confirm-message="¿REALMENTE DESEAS IMPORTAR ESTE ARCHIVO DE ALUMNOS?" data-sgce-confirm-detail="Si el archivo trae columnas AÑO, GRUPO y TURNO, SGCE importará cada alumno en su grupo. Si solo trae nombres, usará el grupo destino seleccionado." data-sgce-confirm-button="SÍ, IMPORTAR ALUMNOS" data-sgce-confirm-loading="IMPORTANDO ALUMNOS..." data-sgce-confirm-icon="fa-users">
                    <?php echo CampoCsrf(); ?>
                                <input type="hidden" name="ImportarAlumnos" value="1">
                                <input type="hidden" name="Tab" value="alumnos">


                                <div class="MaestrosFieldGroup AlumnosFieldGroup">
                                    <label>Grupo destino <small class="text-muted">(opcional si el archivo trae año, grupo y turno)</small></label>
                                    <select name="GrupoId" class="form-select form-select-sm MaestrosInput AlumnosInput">
                                        <option value="">USAR GRUPO DEL ARCHIVO...</option>
                                        <?php foreach($Grupos as $G): ?>
                                            <option value="<?= (int)$G['Id'] ?>">
                                                <?= htmlspecialchars(SgceGrupoNombreVisual($G, $TipoPeriodizacionAdmin), ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="MaestrosFieldGroup AlumnosFieldGroup">
                                    <label>Archivo CSV o Excel</label>
                                    <input type="file" name="CsvAlumnos" class="form-control form-control-sm MaestrosInput MaestrosFileInput AlumnosInput AlumnosFileInput" accept=".csv,.xlsx" required>
                                </div>

                                <div class="SgceImportActions">
<button type="submit" id="BtnImportarAlumnoAzulMetalico" class="BtnAlumnoImportarMetalico SgceImportMainBtn">
                                        <span class="SgceColorIcon" aria-hidden="true">☁️</span> Cargar Archivo
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>

                <div class="col-xl-9 col-lg-8 MaestrosTableCol AlumnosTableCol">

                    <div class="card card-custom p-3 AlumnosTableCard">

                        <div class="SgceTableHeaderStack mb-3 AlumnosTableTop">
                            <div class="SgceTableHeaderTitle">
                                <h6 class="mb-0 text-muted SgceInlineTitle"><span class="SgceColorIcon SgceTitleIcon" aria-hidden="true">🎓</span><span>Padrón de Alumnos</span></h6>
                            </div>

                            <form method="GET" class="SgceFilterBar SgceFilterBarAlumnos SgceServerFilterBar" data-sgce-server-filter="1">
                                <input type="hidden" name="Tab" value="alumnos">
                                <input type="hidden" name="PagAlumnos" value="1">
                                <div class="input-group input-group-sm search-container SgceFilterSearch">
                                    <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                                    <input type="text" id="SearchAlumnos" name="BuscarAlumnos" value="<?= htmlspecialchars((string)($FiltroAlumnos['buscar'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="form-control" placeholder="Buscar alumno...">
                                </div>

                                <select name="EtapaFiltro" class="form-select form-select-sm SgceQuickFilter" aria-label="Filtrar alumnos por etapa">
                                    <option value="">Etapa</option>
                                    <?php foreach($FiltroAlumnosEtapas as $O): ?>
                                        <option value="<?= htmlspecialchars($O['Value'], ENT_QUOTES, 'UTF-8') ?>"<?= $SgceSelected($FiltroAlumnos['etapa'] ?? '', $O['Value']) ?>><?= htmlspecialchars($O['Label'], ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <select name="GrupoFiltro" class="form-select form-select-sm SgceQuickFilter" aria-label="Filtrar alumnos por grupo">
                                    <option value="">Grupo</option>
                                    <?php foreach($FiltroAlumnosLetras as $O): ?>
                                        <option value="<?= htmlspecialchars($O['Value'], ENT_QUOTES, 'UTF-8') ?>"<?= $SgceSelected($FiltroAlumnos['grupo'] ?? '', $O['Value']) ?>><?= htmlspecialchars($O['Label'], ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <select name="TurnoFiltro" class="form-select form-select-sm SgceQuickFilter" aria-label="Filtrar alumnos por turno">
                                    <option value="">Turno</option>
                                    <?php foreach($FiltroAlumnosTurnos as $O): ?>
                                        <option value="<?= htmlspecialchars($O['Value'], ENT_QUOTES, 'UTF-8') ?>"<?= $SgceSelected($FiltroAlumnos['turno'] ?? '', $O['Value']) ?>><?= htmlspecialchars($O['Label'], ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <a class="SgceClearFiltersBtn" href="Admin.php?Tab=alumnos" title="Limpiar filtros"><i class="fa-solid fa-eraser"></i><span>Limpiar</span></a>
                            </form>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="TableAlumnos" data-sgce-server-paged="1">

                                <thead>
                                    <tr>
                                        <th>Matrícula</th>
                                        <th>Nombre del Alumno</th>
                                        <th class="text-center">Grupo</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>

                                <tbody data-sgce-partial-tbody="alumnos">
                                    <?php foreach($Alumnos as $Al): ?>
                                    <tr data-etapa="<?= htmlspecialchars((string)($Al['Grado'] ?? $Al['EtapaOrden'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-grupo="<?= htmlspecialchars(SgceNormalizarMayusculas((string)($Al['Grupo'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" data-turno="<?= htmlspecialchars(SgceNormalizarMayusculas((string)($Al['Turno'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">
                                        <td class="searchable"><span class="badge bg-light text-dark border"><?= htmlspecialchars($Al['Matricula'] ?: 'AUTOMÁTICA', ENT_QUOTES, 'UTF-8') ?></span></td>
                                        <td class="searchable"><?= htmlspecialchars($Al['NombreCompleto'], ENT_QUOTES, 'UTF-8') ?></td>

                                        <td class="searchable text-center">
                                            <?= $Al['Grado']
                                                ? "<span class='badge AlumnosGroupBadge'>".htmlspecialchars(SgceGrupoNombreVisual($Al, $TipoPeriodizacionAdmin), ENT_QUOTES, 'UTF-8')."</span>"
                                                : '<span class="text-danger small fw-bold">Sin Grupo</span>' ?>
                                        </td>

                                        <td class="text-center">
                                            <div class="AdminActions AlumnosActions">
                                                <a class="ActionBtn BtnStudentFile" href="HistorialAlumno.php?AlumnoId=<?= (int)$Al['Id'] ?>">
                                                    <span class="SgceColorIcon" aria-hidden="true">🗂️</span><span>Expediente</span>
                                                </a>

                                                <button class="ActionBtn BtnStudentEdit" data-bs-toggle="modal" data-bs-target="#EAl<?= (int)$Al['Id'] ?>">
                                                    <i class="fa-solid fa-pen-to-square"></i><span>Editar</span>
                                                </button>

                                                <form method="POST" class="m-0 p-0" data-confirm-delete="ALUMNO" data-confirm-message="¿DESEAS DAR DE BAJA A ESTE ALUMNO? ESTA ACCIÓN NO SE PUEDE DESHACER.">
                    <?php echo CampoCsrf(); ?>
                                                    <input type="hidden" name="Tab" value="alumnos">
                                                    <button type="submit" name="DelAlumno" value="<?= (int)$Al['Id'] ?>" class="ActionBtn BtnStudentDelete">
                                                        <i class="fa-solid fa-trash-can"></i><span>Eliminar</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>

                            </table>
                        </div>

                        <div class="SgcePartialPager" data-sgce-partial-pager="alumnos">


                            <?= SgceRenderPager('PagAlumnos', $PagAlumnos, $TotalAlumnosTabla, $PageSizeAlumnos, ['Tab' => 'alumnos']) ?>


                        </div>

                    </div>

                </div>

            </div>
        </div>

        <div class="SgceAjaxModals" data-sgce-partial-modals="alumnos">
        <?php foreach($Alumnos as $Al): ?>
        <div class="modal fade" id="EAl<?= (int)$Al['Id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">

                    <form method="POST">
                    <?php echo CampoCsrf(); ?>
                        <div class="modal-body">

                            <h5 class="mb-4">Editar Alumno</h5>

                            <input type="hidden" name="EditAlumno">
                            <input type="hidden" name="Tab" value="alumnos">
                            <input type="hidden" name="Id" value="<?= (int)$Al['Id'] ?>">

                            <div class="mb-3">
                                <label class="small">Nombre</label>
                                <input type="text"
                                       name="Nombre"
                                       value="<?= htmlspecialchars($Al['NombreCompleto'], ENT_QUOTES, 'UTF-8') ?>"
                                       class="form-control SoloLetrasMayus"
                                       maxlength="160"
                                       required
                                       pattern="^[A-ZÁÉÍÓÚÜÑ\s]+$"
                                       title="Solo letras y espacios"
                                       autocomplete="off">
                            </div>

                            <div class="mb-3">
                                <label class="small">Matrícula</label>
                                <input type="text" name="Matricula" value="<?= htmlspecialchars((string)($Al['Matricula'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="form-control InputUpperAscii" maxlength="40" placeholder="AUTOMÁTICA">
                            </div>

                            <div class="mb-3">
                                <label class="small">Grupo</label>
                                <select name="GrupoId" class="form-select" required>
                                    <?php foreach($Grupos as $G): ?>
                                        <option value="<?= (int)$G['Id'] ?>" <?= (int)$G['Id'] === (int)$Al['GrupoId'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(SgceGrupoNombreVisual($G, $TipoPeriodizacionAdmin), ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <button class="btn btn-primary w-100"><i class="fa-solid fa-floppy-disk"></i> Guardar Cambios</button>

                        </div>
                    </form>

                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>

        

        
<?php endif; ?>
