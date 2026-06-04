<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
?>
<?php if ($TabActual === 'grupos'): ?>
<div class="tab-pane fade show active SgceActivePane" id="grupos">
            <div class="row MaestrosLayoutRow GruposLayoutRow">

                <div class="col-xl-3 col-lg-4 MaestrosSideCol GruposSideCol">

                    <div class="card card-custom MaestrosSideCard GruposSideCard GruposRegisterCard mb-3">
                        <div class="card-header-custom MaestrosCardTitle GruposCardTitle">
                            <span class="SgceColorIcon SgceTitleIcon" aria-hidden="true">🧩</span> Crear Grupo
                        </div>

                        <div class="card-body">
                            <form method="POST" class="MaestrosFormStack GruposFormStack">
                    <?php echo CampoCsrf(); ?>
                                <input type="hidden" name="AltaGrupo">
                                <input type="hidden" name="Tab" value="grupos">

                                <?php if (!empty($EtapasAcademicas)): ?>
                                <div class="MaestrosFieldGroup GruposFieldGroup">
                                    <label>Etapa académica</label>
                                    <select name="EtapaId" class="form-select form-select-sm MaestrosInput GruposInput" required>
                                        <option value="">SELECCIONA...</option>
                                        <?php foreach($EtapasAcademicas as $Et): ?>
                                            <?php $NombreEtapaOpcion = SgceEtapaNombreVisual($Et, $TipoPeriodizacionAdmin); ?>
                                            <option value="<?= (int)$Et['Id'] ?>"><?= htmlspecialchars($NombreEtapaOpcion, ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php else: ?>
                                <div class="MaestrosFieldGroup GruposFieldGroup">
                                    <label><?= htmlspecialchars($EtiquetaEtapaAdmin, ENT_QUOTES, 'UTF-8') ?></label>
                                    <input type="text" name="Grado" maxlength="40" class="form-control form-control-sm MaestrosInput GruposInput InputUpper" placeholder="EJ: AÑO 1, SEMESTRE 1, MÓDULO 1" required autocomplete="off">
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($OfertaActiva['UsaProgramas'])): ?>
                                <div class="MaestrosFieldGroup GruposFieldGroup">
                                    <label>Programa educativo</label>
                                    <select name="ProgramaId" class="form-select form-select-sm MaestrosInput GruposInput" required>
                                        <option value="">SELECCIONA PROGRAMA...</option>
                                        <?php foreach($ProgramasActivos as $Ca): ?>
                                            <option value="<?= (int)$Ca['Id'] ?>"><?= htmlspecialchars($Ca['Nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php endif; ?>

                                <div class="MaestrosFieldGroup GruposFieldGroup">
                                    <label>Grupo</label>
                                    <input type="text"
                                           name="Grupo"
                                           class="form-control form-control-sm MaestrosInput GruposInput InputUpperAscii"
                                           placeholder="GRUPO (EJ: A)"
                                           required
                                           pattern="^[A-Z]+$"
                                           autocomplete="off">
                                </div>

                                <div class="MaestrosFieldGroup GruposFieldGroup">
                                    <label>Turno</label>
                                    <select name="Turno" class="form-select form-select-sm MaestrosInput GruposInput" required>
                                        <option value="">SELECCIONA TURNO...</option>
                                        <option value="MATUTINO">MATUTINO</option>
                                        <option value="VESPERTINO">VESPERTINO</option>
                                    </select>
                                </div>

                                <button type="submit" id="BtnGuardarGrupoVerdeMetalico" class="BtnGrupoGuardarMetalico w-100">
                                    <span class="SgceColorIcon" aria-hidden="true">✅</span> Guardar Grupo
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card card-custom MaestrosSideCard GruposSideCard GruposImportCard">
                        <div class="card-header-custom MaestrosCardTitle MaestrosImportTitle GruposCardTitle GruposImportTitle">
                            <span class="SgceColorIcon SgceTitleIcon" aria-hidden="true">📥</span> Importar CSV / Excel
                        </div>

                        <div class="card-body">
                            <form action="Importar.php" method="POST" enctype="multipart/form-data" class="MaestrosFormStack GruposFormStack" data-sgce-confirm="import" data-sgce-confirm-title="CONFIRMAR IMPORTACIÓN" data-sgce-confirm-subtitle="IMPORTAR GRUPOS" data-sgce-confirm-message="¿REALMENTE DESEAS IMPORTAR ESTE ARCHIVO DE GRUPOS?" data-sgce-confirm-detail="Se procesará el archivo seleccionado para registrar grupos. Revisa etapa académica, grupo y turno antes de continuar." data-sgce-confirm-button="SÍ, IMPORTAR GRUPOS" data-sgce-confirm-loading="IMPORTANDO GRUPOS..." data-sgce-confirm-icon="fa-file-excel">
                    <?php echo CampoCsrf(); ?>
                                <input type="hidden" name="ImportarGrupos" value="1">
                                <input type="hidden" name="Tab" value="grupos">

                                <p class="MaestrosHelpText GruposHelpText">
                                    FORMATO CSV O EXCEL: <code><?= htmlspecialchars($EtiquetaEtapaAdminMayus, ENT_QUOTES, 'UTF-8') ?>, GRUPO, TURNO</code><br>
                                    EJEMPLO: <code><?= htmlspecialchars($EjemploEtapaImportacion, ENT_QUOTES, 'UTF-8') ?>, C, VESPERTINO</code>
                                </p>

                                <div class="MaestrosFieldGroup GruposFieldGroup">
                                    <label>Archivo CSV o Excel</label>
                                    <input type="file" name="CsvGrupos" class="form-control form-control-sm MaestrosInput MaestrosFileInput GruposInput" accept=".csv,.xlsx" required>
                                </div>

                                <div class="SgceImportActions">
                                    <button type="submit" id="BtnImportarGrupoAzulMetalico" class="BtnGrupoImportarMetalico SgceImportMainBtn">
                                        <span class="SgceColorIcon" aria-hidden="true">☁️</span> Cargar Archivo
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>

                <div class="col-xl-9 col-lg-8 MaestrosTableCol GruposTableCol">
                    <div class="card card-custom p-3 MaestrosTableCard GruposTableCard">

                        <div class="SgceTableHeaderStack mb-3 MaestrosTableTop GruposTableTop">
                            <div class="SgceTableHeaderTitle">
                                <h6 class="mb-0 text-muted SgceInlineTitle"><span class="SgceColorIcon SgceTitleIcon" aria-hidden="true">🗂️</span><span>Grupos Existentes</span></h6>
                            </div>

                            <div class="SgceFilterBar SgceFilterBarGrupos">
                                <div class="input-group input-group-sm search-container SgceFilterSearch">
                                    <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                                    <input type="text" id="SearchGrupos" class="form-control" placeholder="Buscar grupo o turno...">
                                </div>

                                <select class="form-select form-select-sm SgceQuickFilter" data-sgce-filter-table="TableGrupos" data-sgce-filter-key="etapa" aria-label="Filtrar grupos por etapa">
                                    <option value="">Etapa</option>
                                    <?php foreach($FiltroGruposEtapas as $O): ?>
                                        <option value="<?= htmlspecialchars($O['Value'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($O['Label'], ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <select class="form-select form-select-sm SgceQuickFilter" data-sgce-filter-table="TableGrupos" data-sgce-filter-key="grupo" aria-label="Filtrar grupos por letra">
                                    <option value="">Grupo</option>
                                    <?php foreach($FiltroGruposLetras as $O): ?>
                                        <option value="<?= htmlspecialchars($O['Value'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($O['Label'], ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <select class="form-select form-select-sm SgceQuickFilter" data-sgce-filter-table="TableGrupos" data-sgce-filter-key="turno" aria-label="Filtrar grupos por turno">
                                    <option value="">Turno</option>
                                    <?php foreach($FiltroGruposTurnos as $O): ?>
                                        <option value="<?= htmlspecialchars($O['Value'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($O['Label'], ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <button type="button" class="SgceClearFiltersBtn" data-sgce-clear-filters="TableGrupos" title="Limpiar filtros">
                                    <i class="fa-solid fa-eraser"></i><span>Limpiar</span>
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover text-center align-middle" id="TableGrupos">
                                <thead>
                                    <tr>
                                        <th>Etapa</th>
                                        <?php if (!empty($OfertaActiva['UsaProgramas'])): ?><th>Programa</th><?php endif; ?>
                                        <th>Grupo</th>
                                        <th>Turno</th>
                                        <th class="text-center">Calif.</th>
                                        <th class="text-center">Asis. Hoy</th>
                                        <th class="text-center">Asis. Todas</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach($GruposTabla as $G): ?>
                                    <tr data-etapa="<?= htmlspecialchars((string)($G['Grado'] ?? $G['EtapaOrden'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-grupo="<?= htmlspecialchars(SgceNormalizarMayusculas((string)($G['Grupo'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" data-turno="<?= htmlspecialchars(SgceNormalizarMayusculas((string)($G['Turno'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">
                                        <td class="searchable"><?= htmlspecialchars(SgceEtapaNombreVisual($G, $TipoPeriodizacionAdmin), ENT_QUOTES, 'UTF-8') ?></td>
                                        <?php if (!empty($OfertaActiva['UsaProgramas'])): ?><td class="searchable"><?= htmlspecialchars($G['ProgramaNombre'] ?? 'GENERAL') ?></td><?php endif; ?>
                                        <td class="searchable"><span class="GruposGrupoBadge"><?= htmlspecialchars($G['Grupo']) ?></span></td>

                                        <td class="searchable">
                                            <span class="GruposTurnoBadge">
                                                <?= htmlspecialchars($G['Turno']) ?>
                                            </span>
                                        </td>

                                        <td class="text-center">
                                            <div class="ExportIcons">
                                                <a class="ExportIcon ExportExcel" target="_blank" rel="noopener noreferrer" title="Calificaciones del grupo en Excel" href="ExportarCalificaciones.php?GrupoId=<?= $G['Id'] ?>&Tipo=Excel">
                                                    <i class="fa-solid fa-file-excel"></i><span class="ExportText">Excel</span>
                                                </a>
                                                <a class="ExportIcon ExportPdf" target="_blank" rel="noopener noreferrer" title="Calificaciones del grupo en PDF" href="ExportarCalificaciones.php?GrupoId=<?= $G['Id'] ?>&Tipo=Pdf">
                                                    <i class="fa-solid fa-file-pdf"></i><span class="ExportText">PDF</span>
                                                </a>
                                            </div>
                                        </td>

                                        <td class="text-center">
                                            <div class="ExportIcons">
                                                <a class="ExportIcon ExportExcel ExportHoy" target="_blank" rel="noopener noreferrer" title="Asistencias de hoy del grupo en Excel" href="ExportarAsistencia.php?GrupoId=<?= $G['Id'] ?>&Tipo=Excel&Rango=Hoy">
                                                    <i class="fa-solid fa-file-excel"></i><span class="ExportText">Excel</span>
                                                </a>
                                                <a class="ExportIcon ExportPdf ExportHoy" target="_blank" rel="noopener noreferrer" title="Asistencias de hoy del grupo en PDF" href="ExportarAsistencia.php?GrupoId=<?= $G['Id'] ?>&Tipo=Pdf&Rango=Hoy">
                                                    <i class="fa-solid fa-file-pdf"></i><span class="ExportText">PDF</span>
                                                </a>
                                            </div>
                                        </td>

                                        <td class="text-center">
                                            <div class="ExportIcons">
                                                <a class="ExportIcon ExportExcel ExportTodas" target="_blank" rel="noopener noreferrer" title="Todas las asistencias del grupo en Excel" href="ExportarAsistencia.php?GrupoId=<?= $G['Id'] ?>&Tipo=Excel&Rango=Todas<?= $QueryCicloActivoAsistencia ?>">
                                                    <i class="fa-solid fa-file-excel"></i><span class="ExportText">Excel</span>
                                                </a>
                                                <a class="ExportIcon ExportPdf ExportTodas" target="_blank" rel="noopener noreferrer" title="Todas las asistencias del grupo en PDF" href="ExportarAsistencia.php?GrupoId=<?= $G['Id'] ?>&Tipo=Pdf&Rango=Todas<?= $QueryCicloActivoAsistencia ?>">
                                                    <i class="fa-solid fa-file-pdf"></i><span class="ExportText">PDF</span>
                                                </a>
                                            </div>
                                        </td>

                                        <td class="text-center">
                                            <div class="AdminActions">
                                                <button class="ActionBtn ActionEdit BtnGroupEdit" data-bs-toggle="modal" data-bs-target="#EG<?= $G['Id'] ?>">
                                                    <i class="fa-solid fa-pen-to-square"></i><span>Editar</span>
                                                </button>

                                                <form method="POST" class="m-0 p-0" data-confirm-delete="GRUPO" data-confirm-message="¿DESEAS ELIMINAR ESTE GRUPO? SI TIENE DATOS RELACIONADOS, EL SISTEMA PUEDE IMPEDIRLO.">
                    <?php echo CampoCsrf(); ?>
                                                    <input type="hidden" name="Tab" value="grupos">
                                                    <button type="submit" name="DelGrupo" value="<?= $G['Id'] ?>" class="ActionBtn ActionDelete BtnGroupDelete">
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

                        <div id="PagerGrupos" class="SgcePagerServer SgceClientPager"></div>

                    </div>
                </div>

                <?php foreach($GruposTabla as $G): ?>
                <div class="modal fade" id="EG<?= $G['Id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered modal-sm">
                        <div class="modal-content">

                            <form method="POST">
                    <?php echo CampoCsrf(); ?>
                                <div class="modal-body">

                                    <h6 class="mb-3 border-bottom pb-2">Modificar Grupo</h6>

                                    <input type="hidden" name="EditGrupo">
                                    <input type="hidden" name="Tab" value="grupos">
                                    <input type="hidden" name="Id" value="<?= $G['Id'] ?>">

                                    <?php if (!empty($EtapasAcademicas)): ?>
                                    <label class="small text-muted">Etapa académica</label>
                                    <select name="EtapaId" class="form-select form-select-sm mb-2" required>
                                        <?php foreach($EtapasAcademicas as $Et): ?>
                                            <?php $NombreEtapaOpcion = SgceEtapaNombreVisual($Et, $TipoPeriodizacionAdmin); ?>
                                            <option value="<?= (int)$Et['Id'] ?>" <?= (int)($G['EtapaId'] ?? 0) === (int)$Et['Id'] ? 'selected' : '' ?>><?= htmlspecialchars($NombreEtapaOpcion, ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php else: ?>
                                    <label class="small text-muted"><?= htmlspecialchars($EtiquetaEtapaAdmin, ENT_QUOTES, 'UTF-8') ?></label>
                                    <input type="text" name="Grado" value="<?= htmlspecialchars($G['Grado']) ?>" class="form-control form-control-sm mb-2 InputUpper" required maxlength="40">
                                    <?php endif; ?>

                                    <?php if (!empty($OfertaActiva['UsaProgramas'])): ?>
                                    <label class="small text-muted">Programa educativo</label>
                                    <select name="ProgramaId" class="form-select form-select-sm mb-2" required>
                                        <?php foreach($ProgramasActivos as $Ca): ?>
                                            <option value="<?= (int)$Ca['Id'] ?>" <?= (int)($G['ProgramaId'] ?? 0) === (int)$Ca['Id'] ? 'selected' : '' ?>><?= htmlspecialchars($Ca['Nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php endif; ?>

                                    <label class="small text-muted">Grupo</label>
                                    <input type="text"
                                           name="Grupo"
                                           value="<?= htmlspecialchars($G['Grupo']) ?>"
                                           class="form-control form-control-sm mb-2 InputUpperAscii"
                                           required
                                           pattern="^[A-Z]+$">

                                    <label class="small text-muted">Turno</label>
                                    <select name="Turno" class="form-select form-select-sm mb-3" required>
                                        <option value="MATUTINO" <?= strtoupper((string)$G['Turno']) === 'MATUTINO' ? 'selected' : '' ?>>MATUTINO</option>
                                        <option value="VESPERTINO" <?= strtoupper((string)$G['Turno']) === 'VESPERTINO' ? 'selected' : '' ?>>VESPERTINO</option>
                                    </select>

                                    <button class="btn btn-sm btn-success w-100"><i class="fa-solid fa-floppy-disk"></i> Guardar Cambios</button>

                                </div>
                            </form>

                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

            </div>
        </div>

        

        
<?php endif; ?>
