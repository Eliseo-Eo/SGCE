<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
?>
<?php if ($TabActual === 'materias'): ?>
<div class="tab-pane fade show active SgceActivePane" id="materias">
            <div class="row MaestrosLayoutRow GruposLayoutRow MateriasLayoutRow">

                <div class="col-xl-3 col-lg-4 MaestrosSideCol GruposSideCol MateriasSideCol">

                    <div class="card card-custom MaestrosSideCard GruposSideCard MateriasSideCard MateriasRegisterCard mb-3">
                        <div class="card-header-custom MaestrosCardTitle GruposCardTitle MateriasCardTitle">
                            <span class="SgceColorIcon SgceTitleIcon" aria-hidden="true">📖</span> Crear Materia
                        </div>

                        <div class="card-body">
                            <form method="POST" class="MaestrosFormStack GruposFormStack MateriasFormStack">
                                <?php echo CampoCsrf(); ?>
                                <input type="hidden" name="AltaMateriaGrupo">
                                <input type="hidden" name="Tab" value="materias">

                                <div class="MaestrosFieldGroup GruposFieldGroup MateriasFieldGroup">
                                    <label>Grupo</label>
                                    <select name="GrupoId" class="form-select form-select-sm MaestrosInput GruposInput MateriasInput" required>
                                        <option value="">SELECCIONA GRUPO...</option>
                                        <?php foreach($Grupos as $G): ?>
                                            <option value="<?= (int)$G['Id'] ?>"><?= htmlspecialchars(SgceGrupoNombreVisual($G, $TipoPeriodizacionAdmin), ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="MaestrosFieldGroup GruposFieldGroup MateriasFieldGroup">
                                    <label>Materia</label>
                                    <input type="text" name="Materia" class="form-control form-control-sm MaestrosInput GruposInput MateriasInput InputUpper" placeholder="EJ: ESPAÑOL, INFORMÁTICA, MATEMÁTICAS" maxlength="140" required autocomplete="off">
                                </div>

                                <div class="MaestrosFieldGroup GruposFieldGroup MateriasFieldGroup">
                                    <label>Horas semanales</label>
                                    <input type="number" name="HorasSemana" class="form-control form-control-sm MaestrosInput GruposInput MateriasInput" min="1" max="40" placeholder="EJ: 8" required>
                                </div>

                                <button type="submit" id="BtnGuardarMateriaVerdeMetalico" class="BtnMateriaGuardarMetalico w-100">
                                    <span class="SgceColorIcon" aria-hidden="true">✅</span> Guardar Materia
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card card-custom MaestrosSideCard GruposSideCard MateriasSideCard MateriasImportCard">
                        <div class="card-header-custom MaestrosCardTitle MaestrosImportTitle GruposCardTitle GruposImportTitle MateriasCardTitle MateriasImportTitle">
                            <span class="SgceColorIcon SgceTitleIcon" aria-hidden="true">📥</span> Importar CSV / Excel
                        </div>

                        <div class="card-body">
                            <form action="Importar.php" method="POST" enctype="multipart/form-data" class="MaestrosFormStack GruposFormStack MateriasFormStack" data-sgce-confirm="import" data-sgce-confirm-title="CONFIRMAR IMPORTACIÓN" data-sgce-confirm-subtitle="IMPORTAR MATERIAS" data-sgce-confirm-message="¿REALMENTE DESEAS IMPORTAR ESTE ARCHIVO DE MATERIAS?" data-sgce-confirm-detail="Se procesará el archivo seleccionado para registrar materias por grupo. Revisa materia, etapa, grupo, turno y horas antes de continuar." data-sgce-confirm-button="SÍ, IMPORTAR MATERIAS" data-sgce-confirm-loading="IMPORTANDO MATERIAS..." data-sgce-confirm-icon="fa-file-excel">
                                <?php echo CampoCsrf(); ?>
                                <input type="hidden" name="ImportarMateriasGrupo" value="1">
                                <input type="hidden" name="Tab" value="materias">

                                <p class="MaestrosHelpText GruposHelpText MateriasHelpText">
                                    FORMATO CSV O EXCEL: <code>MATERIA, AÑO, GRUPO, TURNO, HORAS</code><br>
                                    EJEMPLO: <code>ESPAÑOL, 1,C, MATUTINO, 5</code>
                                </p>

                                <div class="MaestrosFieldGroup GruposFieldGroup MateriasFieldGroup">
                                    <label>Archivo CSV o Excel</label>
                                    <input type="file" name="CsvMaterias" class="form-control form-control-sm MaestrosInput MaestrosFileInput GruposInput MateriasInput" accept=".csv,.xlsx" required>
                                </div>

                                <div class="SgceImportActions">
<button type="submit" id="BtnImportarMateriaAzulMetalico" class="BtnMateriaImportarMetalico SgceImportMainBtn">
                                        <span class="SgceColorIcon" aria-hidden="true">☁️</span> Cargar Archivo
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>

                <div class="col-xl-9 col-lg-8 MaestrosTableCol GruposTableCol MateriasTableCol">
                    <div class="card card-custom p-3 MaestrosTableCard GruposTableCard MateriasTableCard">

                        <div class="SgceTableHeaderStack mb-3 MaestrosTableTop GruposTableTop MateriasTableTop">
                            <div class="SgceTableHeaderTitle">
                                <h6 class="mb-0 text-muted SgceInlineTitle"><span class="SgceColorIcon SgceTitleIcon" aria-hidden="true">📋</span><span>Materias Existentes</span></h6>
                            </div>

                            <form method="GET" class="SgceFilterBar SgceFilterBarMaterias MateriasTopTools SgceServerFilterBar" data-sgce-server-filter="1">
                                <input type="hidden" name="Tab" value="materias">
                                <input type="hidden" name="PagMaterias" value="1">
                                <div class="input-group input-group-sm search-container SgceFilterSearch">
                                    <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                                    <input type="text" id="SearchMaterias" name="BuscarMaterias" value="<?= htmlspecialchars((string)($FiltroMaterias['buscar'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="form-control" placeholder="Buscar materia...">
                                </div>
                                <select name="MateriaFiltro" class="form-select form-select-sm SgceQuickFilter SgceQuickFilterMateria" aria-label="Filtrar por materia">
                                    <option value="">Materia</option>
                                    <?php foreach($FiltroMateriasBase as $O): ?>
                                        <option value="<?= htmlspecialchars($O['Value'], ENT_QUOTES, 'UTF-8') ?>"<?= $SgceSelected($FiltroMaterias['materia'] ?? '', $O['Value']) ?>><?= htmlspecialchars($O['Label'], ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="EtapaFiltro" class="form-select form-select-sm SgceQuickFilter" aria-label="Filtrar materias por etapa">
                                    <option value="">Etapa</option>
                                    <?php foreach($FiltroMateriasEtapas as $O): ?>
                                        <option value="<?= htmlspecialchars($O['Value'], ENT_QUOTES, 'UTF-8') ?>"<?= $SgceSelected($FiltroMaterias['etapa'] ?? '', $O['Value']) ?>><?= htmlspecialchars($O['Label'], ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="GrupoFiltro" class="form-select form-select-sm SgceQuickFilter" aria-label="Filtrar materias por grupo">
                                    <option value="">Grupo</option>
                                    <?php foreach($FiltroMateriasLetras as $O): ?>
                                        <option value="<?= htmlspecialchars($O['Value'], ENT_QUOTES, 'UTF-8') ?>"<?= $SgceSelected($FiltroMaterias['grupo'] ?? '', $O['Value']) ?>><?= htmlspecialchars($O['Label'], ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="TurnoFiltro" class="form-select form-select-sm SgceQuickFilter" aria-label="Filtrar materias por turno">
                                    <option value="">Turno</option>
                                    <?php foreach($FiltroMateriasTurnos as $O): ?>
                                        <option value="<?= htmlspecialchars($O['Value'], ENT_QUOTES, 'UTF-8') ?>"<?= $SgceSelected($FiltroMaterias['turno'] ?? '', $O['Value']) ?>><?= htmlspecialchars($O['Label'], ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="EstadoFiltro" class="form-select form-select-sm SgceQuickFilter" aria-label="Filtrar materias por estado">
                                    <option value="">Estado</option>
                                    <option value="DISPONIBLE"<?= $SgceSelected($FiltroMaterias['estado'] ?? '', 'DISPONIBLE') ?>>Disponible</option>
                                    <option value="ASIGNADA"<?= $SgceSelected($FiltroMaterias['estado'] ?? '', 'ASIGNADA') ?>>Asignada</option>
                                </select>
                                <a class="SgceClearFiltersBtn" href="Admin.php?Tab=materias" title="Limpiar filtros"><i class="fa-solid fa-eraser"></i><span>Limpiar</span></a>
                            </form>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover text-center align-middle" id="TableMaterias" data-sgce-server-paged="1">
                                <thead>
                                    <tr>
                                        <th class="text-start">Materia</th>
                                        <th>Etapa</th>
                                        <th>Grupo</th>
                                        <th>Turno</th>
                                        <?php if (!empty($OfertaActiva['UsaProgramas'])): ?><th>Programa</th><?php endif; ?>
                                        <th class="text-center">Horas</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>

                                <tbody data-sgce-partial-tbody="materias">
                                    <?php foreach($MateriasGrupo as $Mat): ?>
                                    <tr data-materia="<?= htmlspecialchars(trim((string)preg_replace('/\s+\d+$/u', '', SgceNormalizarMayusculas((string)($Mat['MateriaNombre'] ?? '')))), ENT_QUOTES, 'UTF-8') ?>" data-etapa="<?= htmlspecialchars((string)($Mat['Grado'] ?? $Mat['EtapaOrden'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-grupo="<?= htmlspecialchars(SgceNormalizarMayusculas((string)($Mat['Grupo'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" data-turno="<?= htmlspecialchars(SgceNormalizarMayusculas((string)($Mat['Turno'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" data-estado="<?= ((int)($Mat['TieneAsignacion'] ?? 0) > 0) ? 'ASIGNADA' : 'DISPONIBLE' ?>">
                                        <td class="searchable text-start MateriaNombreCell"><?= htmlspecialchars($Mat['MateriaNombre'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="searchable"><?= htmlspecialchars(SgceEtapaNombreVisual($Mat, $TipoPeriodizacionAdmin), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="searchable"><span class="GruposGrupoBadge"><?= htmlspecialchars($Mat['Grupo'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                        <td class="searchable"><span class="GruposTurnoBadge"><?= htmlspecialchars($Mat['Turno'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                        <?php if (!empty($OfertaActiva['UsaProgramas'])): ?><td class="searchable"><?= htmlspecialchars($Mat['ProgramaNombre'] ?? 'GENERAL', ENT_QUOTES, 'UTF-8') ?></td><?php endif; ?>
                                        <td class="text-center"><span class="badge rounded-pill text-bg-light"><?= (int)$Mat['HorasSemana'] ?> h</span></td>
                                        <td class="searchable text-center">
                                            <?php if ((int)($Mat['TieneAsignacion'] ?? 0) > 0): ?>
                                                <span class="MateriaEstadoBadge MateriaEstadoAsignada">Asignada</span>
                                            <?php else: ?>
                                                <span class="MateriaEstadoBadge MateriaEstadoDisponible">Disponible</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="AdminActions">
                                                <button class="ActionBtn ActionEdit BtnMateriaEdit" data-bs-toggle="modal" data-bs-target="#EMat<?= (int)$Mat['Id'] ?>">
                                                    <i class="fa-solid fa-pen-to-square"></i><span>Editar</span>
                                                </button>
                                                <form method="POST" class="m-0 p-0" data-confirm-delete="MATERIA" data-confirm-message="¿DESEAS DESACTIVAR ESTA MATERIA DEL GRUPO?">
                                                    <?php echo CampoCsrf(); ?>
                                                    <input type="hidden" name="Tab" value="materias">
                                                    <button type="submit" name="DelMateriaGrupo" value="<?= (int)$Mat['Id'] ?>" class="ActionBtn ActionDelete BtnMateriaDelete">
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

                        <div class="SgcePartialPager" data-sgce-partial-pager="materias">


                            <?= SgceRenderPager('PagMaterias', $PagMaterias, $TotalMateriasTabla, $PageSizeMaterias, ['Tab' => 'materias']) ?>


                        </div>

                    </div>
                </div>

                <div class="SgceAjaxModals" data-sgce-partial-modals="materias">
        <?php foreach($MateriasGrupo as $Mat): ?>
                <div class="modal fade" id="EMat<?= (int)$Mat['Id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered modal-sm">
                        <div class="modal-content">
                            <form method="POST">
                                <?php echo CampoCsrf(); ?>
                                <div class="modal-body">
                                    <h6 class="mb-3 border-bottom pb-2">Modificar Materia</h6>
                                    <input type="hidden" name="EditMateriaGrupo">
                                    <input type="hidden" name="Tab" value="materias">
                                    <input type="hidden" name="Id" value="<?= (int)$Mat['Id'] ?>">

                                    <label class="small text-muted">Grupo</label>
                                    <select name="GrupoId" class="form-select form-select-sm mb-2" required>
                                        <?php foreach($Grupos as $G): ?>
                                            <option value="<?= (int)$G['Id'] ?>" <?= (int)$G['Id'] === (int)$Mat['GrupoId'] ? 'selected' : '' ?>><?= htmlspecialchars(SgceGrupoNombreVisual($G, $TipoPeriodizacionAdmin), ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>

                                    <label class="small text-muted">Materia</label>
                                    <input type="text" name="Materia" value="<?= htmlspecialchars($Mat['MateriaNombre'], ENT_QUOTES, 'UTF-8') ?>" class="form-control form-control-sm mb-2 InputUpper" maxlength="140" required>

                                    <label class="small text-muted">Horas semanales</label>
                                    <input type="number" name="HorasSemana" value="<?= (int)$Mat['HorasSemana'] ?>" class="form-control form-control-sm mb-3" min="1" max="40" required>

                                    <button class="btn btn-sm btn-success w-100"><i class="fa-solid fa-floppy-disk"></i> Guardar Cambios</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
        </div>

            </div>
        </div>

<?php endif; ?>

