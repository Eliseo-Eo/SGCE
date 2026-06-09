<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
?>
<?php if ($TabActual === 'asignaciones'): ?>
<div class="tab-pane fade show active SgceActivePane" id="asignaciones">
            <div class="card card-custom shadow-sm border-0 AsignacionesTableCard">

                <div class="card-header bg-white py-3 border-bottom AsignacionesHeaderCard">
                    <h6 class="mb-0 fw-bold text-dark">
                        <span class="SgceColorIcon SgceTitleIcon me-2" aria-hidden="true">🔗</span>
                        Nueva Asignación Académica
                    </h6>
                </div>

                <div class="card-body p-4 AsignacionesCardBody">

                    <form method="POST" class="row g-3 align-items-end mb-3 AsignacionForm">
                    <?php echo CampoCsrf(); ?>
                        <input type="hidden" name="AltaAsignacion">
                        <input type="hidden" name="Tab" value="asignaciones">

                        <div class="col-md-4">
                            <label class="small fw-bold text-muted">Seleccionar Docente</label>
                            <select name="MaestroId" class="form-select SgceSearchableSelect" data-sgce-searchable-select="1" data-sgce-search-placeholder="Buscar docente..." required>
                                <option value="">Elegir profesor...</option>
                                <?php foreach($Maestros as $M): ?>
                                    <option value="<?= (int)$M['Id'] ?>"><?= htmlspecialchars($M['NombreCompleto'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="small fw-bold text-muted">Materia disponible</label>
                            <select name="MateriaGrupoId" class="form-select SgceSearchableSelect" data-sgce-searchable-select="1" data-sgce-search-placeholder="Buscar materia, grupo o etapa..." required>
                                <option value="">Elegir materia registrada...</option>
                                <?php foreach($MateriasDisponiblesAsignacion as $MD): ?>
                                    <option value="<?= (int)$MD['Id'] ?>">
                                        <?= htmlspecialchars($MD['MateriaNombre'].' · '.SgceGrupoNombreVisual($MD, $TipoPeriodizacionAdmin).' · '.$MD['HorasSemana'].' h', ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2 AsignacionButtonCol">
                            <button type="submit" id="BtnVincularAsignacionVerdeMetalico" class="w-100 fw-bold BtnAsignacionVincularMetalico" aria-label="Vincular asignación académica">
                                <i class="fa-solid fa-link"></i><span>Vincular</span>
                            </button>
                        </div>
                    </form>


                    <div class="SgceAsignacionesFilterHead mb-3 pt-2">
                        <h6 class="mb-0 fw-bold text-secondary">Cargas Académicas Activas</h6>
                        <form method="GET" class="SgceFilterBar SgceFilterBarAsignaciones SgceServerFilterBar" data-sgce-server-filter="1">
                            <input type="hidden" name="Tab" value="asignaciones">
                            <input type="hidden" name="PagAsig" value="1">
                            <div class="input-group search-container SgceFilterSearch">
                                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass"></i></span>
                                <input type="text" id="SearchAsig" name="BuscarAsignaciones" value="<?= htmlspecialchars((string)($FiltroAsignaciones['buscar'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="form-control border-start-0" placeholder="Buscar carga...">
                            </div>
                            <select name="MateriaFiltro" class="form-select form-select-sm SgceQuickFilter" aria-label="Filtrar asignaciones por materia">
                                <option value="">Materia</option>
                                <?php foreach($FiltroMateriasBase as $O): ?>
                                    <option value="<?= htmlspecialchars($O['Value'], ENT_QUOTES, 'UTF-8') ?>"<?= $SgceSelected($FiltroAsignaciones['materia'] ?? '', $O['Value']) ?>><?= htmlspecialchars($O['Label'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="EtapaFiltro" class="form-select form-select-sm SgceQuickFilter" aria-label="Filtrar asignaciones por etapa">
                                <option value="">Etapa</option>
                                <?php foreach($FiltroMateriasEtapas as $O): ?>
                                    <option value="<?= htmlspecialchars($O['Value'], ENT_QUOTES, 'UTF-8') ?>"<?= $SgceSelected($FiltroAsignaciones['etapa'] ?? '', $O['Value']) ?>><?= htmlspecialchars($O['Label'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="GrupoFiltro" class="form-select form-select-sm SgceQuickFilter" aria-label="Filtrar asignaciones por grupo">
                                <option value="">Grupo</option>
                                <?php foreach($FiltroMateriasLetras as $O): ?>
                                    <option value="<?= htmlspecialchars($O['Value'], ENT_QUOTES, 'UTF-8') ?>"<?= $SgceSelected($FiltroAsignaciones['grupo'] ?? '', $O['Value']) ?>><?= htmlspecialchars($O['Label'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="TurnoFiltro" class="form-select form-select-sm SgceQuickFilter" aria-label="Filtrar asignaciones por turno">
                                <option value="">Turno</option>
                                <?php foreach($FiltroMateriasTurnos as $O): ?>
                                    <option value="<?= htmlspecialchars($O['Value'], ENT_QUOTES, 'UTF-8') ?>"<?= $SgceSelected($FiltroAsignaciones['turno'] ?? '', $O['Value']) ?>><?= htmlspecialchars($O['Label'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                            <a class="SgceClearFiltersBtn" href="Admin.php?Tab=asignaciones" title="Limpiar filtros"><i class="fa-solid fa-eraser"></i><span>Limpiar</span></a>
                        </form>
                    </div>

                    <div class="table-responsive AsignacionesTableWrap">
                        <table class="table table-hover align-middle" id="TableAsig" data-sgce-server-paged="1">

                            <thead class="table-light">
                                <tr>
                                    <th>Docente</th>
                                    <th>Materia</th>
                                    <th>Grupo</th>
                                    <th class="text-center">Horas</th>
                                    <th class="text-center">Calif.</th>
                                    <th class="text-center">Asis. Hoy</th>
                                    <th class="text-center">Asis. Todas</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>

                            <tbody data-sgce-partial-tbody="asignaciones">
        <?php foreach($Asignaciones as $Asg): ?>
                                <tr>
                                    <td class="searchable fw-medium"><?= htmlspecialchars($Asg['Maestro'], ENT_QUOTES, 'UTF-8') ?></td>

                                    <td class="searchable">
                                        <span class="AsignacionMateriaTexto" title="<?= htmlspecialchars($Asg['MateriaNombre'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($Asg['MateriaNombre'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </td>

                                    <td class="searchable AsignacionGrupoTd">
                                        <?php
                                            $TurnoAsignacion = strtoupper((string)$Asg['Turno']);
                                            $GrupoAsignacionEtiqueta = SgceGrupoNombreVisual($Asg, $TipoPeriodizacionAdmin);
                                        ?>
                                        <span class="AsignacionGrupoBadgeFull" title="<?= htmlspecialchars($GrupoAsignacionEtiqueta, ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars($GrupoAsignacionEtiqueta, ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>

                                    <td class="text-center"><span class="badge rounded-pill text-bg-light"><?= (int)($Asg['HorasSemana'] ?? 0) ?> h</span></td>

                                    
                                    <td class="text-center">
                                        <div class="ExportIcons">
                                            <a class="ExportIcon ExportExcel"
                                               target="_blank" rel="noopener noreferrer"
                                               title="Exportar calificaciones en Excel"
                                               href="ExportarCalificaciones.php?AsignacionId=<?= (int)$Asg['Id'] ?>&Tipo=Excel">
                                                <i class="fa-solid fa-file-excel"></i><span class="ExportText">Excel</span>
                                            </a>

                                            <a class="ExportIcon ExportPdf"
                                               target="_blank" rel="noopener noreferrer"
                                               title="Exportar calificaciones en PDF"
                                               href="ExportarCalificaciones.php?AsignacionId=<?= (int)$Asg['Id'] ?>&Tipo=Pdf">
                                                <i class="fa-solid fa-file-pdf"></i><span class="ExportText">PDF</span>
                                            </a>
                                        </div>
                                    </td>

                                    
                                    <td class="text-center">
                                        <div class="ExportIcons">
                                            <a class="ExportIcon ExportExcel ExportHoy"
                                               target="_blank" rel="noopener noreferrer"
                                               title="Exportar asistencias de hoy en Excel"
                                               href="ExportarAsistencia.php?AsignacionId=<?= (int)$Asg['Id'] ?>&Tipo=Excel&Rango=Hoy">
                                                <i class="fa-solid fa-file-excel"></i><span class="ExportText">Excel</span>
                                            </a>

                                            <a class="ExportIcon ExportPdf ExportHoy"
                                               target="_blank" rel="noopener noreferrer"
                                               title="Exportar asistencias de hoy en PDF"
                                               href="ExportarAsistencia.php?AsignacionId=<?= (int)$Asg['Id'] ?>&Tipo=Pdf&Rango=Hoy">
                                                <i class="fa-solid fa-file-pdf"></i><span class="ExportText">PDF</span>
                                            </a>
                                        </div>
                                    </td>

                                    
                                    <td class="text-center">
                                        <div class="ExportIcons">
                                            <a class="ExportIcon ExportExcel ExportTodas"
                                               target="_blank" rel="noopener noreferrer"
                                               title="Exportar todas las asistencias en Excel"
                                               href="ExportarAsistencia.php?AsignacionId=<?= (int)$Asg['Id'] ?>&Tipo=Excel&Rango=Todas<?= $QueryCicloActivoAsistencia ?>">
                                                <i class="fa-solid fa-file-excel"></i><span class="ExportText">Excel</span>
                                            </a>

                                            <a class="ExportIcon ExportPdf ExportTodas"
                                               target="_blank" rel="noopener noreferrer"
                                               title="Exportar todas las asistencias en PDF"
                                               href="ExportarAsistencia.php?AsignacionId=<?= (int)$Asg['Id'] ?>&Tipo=Pdf&Rango=Todas<?= $QueryCicloActivoAsistencia ?>">
                                                <i class="fa-solid fa-file-pdf"></i><span class="ExportText">PDF</span>
                                            </a>
                                        </div>
                                    </td>

                                    <td class="text-center">
                                            <div class="AdminActions">
<button class="ActionBtn ActionEdit BtnAsignacionEdit" data-bs-toggle="modal" data-bs-target="#EAsg<?= (int)$Asg['Id'] ?>">
                                            <i class="fa-solid fa-pen-to-square"></i><span>Editar</span>
                                        </button>

                                        <form method="POST" class="m-0 p-0" data-confirm-delete="ASIGNACIÓN" data-confirm-message="¿DESEAS DESACTIVAR ESTA ASIGNACIÓN? SI YA TIENE CALIFICACIONES O ASISTENCIAS EL SISTEMA LA PROTEGERÁ.">
                    <?php echo CampoCsrf(); ?>
                                            <input type="hidden" name="Tab" value="asignaciones">
                                            <button type="submit" name="DelAsignacion" value="<?= (int)$Asg['Id'] ?>" class="ActionBtn ActionDelete BtnAsignacionDelete">
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

                    
                    <div class="SgcePartialPager" data-sgce-partial-pager="asignaciones">


                    
                        <?= SgceRenderPager('PagAsig', $PagAsig, $TotalAsignacionesTabla, $PageSizeAsignaciones, ['Tab' => 'asignaciones']) ?>


                    
                    </div>

                </div>
            </div>
        </div>

        <div class="SgceAjaxModals" data-sgce-partial-modals="asignaciones">
        <?php foreach($Asignaciones as $Asg): ?>
        <div class="modal fade" id="EAsg<?= (int)$Asg['Id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered ModalEditarPro">
                <div class="modal-content">

                    <form method="POST">
                    <?php echo CampoCsrf(); ?>
                        <div class="modal-body">

                            <h6 class="mb-3 border-bottom pb-2">Editar Asignación</h6>

                            <input type="hidden" name="EditAsignacion">
                            <input type="hidden" name="Tab" value="asignaciones">
                            <input type="hidden" name="Id" value="<?= (int)$Asg['Id'] ?>">

                            <label class="small text-muted">Docente</label>
                            <select name="MaestroId" class="form-select mb-2 SgceSearchableSelect" data-sgce-searchable-select="1" data-sgce-search-placeholder="Buscar docente..." required>
                                <?php foreach($Maestros as $M): ?>
                                    <option value="<?= (int)$M['Id'] ?>" <?= (int)$M['Id'] === (int)$Asg['MaestroId'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($M['NombreCompleto'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <div class="alert alert-light border rounded-4 small fw-semibold mb-3">
                                <div><strong>Materia:</strong> <?= htmlspecialchars($Asg['MateriaNombre'], ENT_QUOTES, 'UTF-8') ?></div>
                                <div><strong>Grupo:</strong> <?= htmlspecialchars(SgceGrupoNombreVisual($Asg, $TipoPeriodizacionAdmin), ENT_QUOTES, 'UTF-8') ?></div>
                                <div><strong>Horas:</strong> <?= (int)($Asg['HorasSemana'] ?? 0) ?> semanales</div>
                            </div>

                            <label class="small text-muted">Motivo del relevo/interinato</label>
                            <input type="text" name="MotivoRelevo" value="RELEVO DOCENTE / INTERINATO" class="form-control mb-3" maxlength="255">
                            <div class="alert alert-warning border-0 rounded-4 small fw-semibold">
                                Si esta asignación ya tiene calificaciones o asistencias, SGCE solo permitirá cambiar el docente. La materia y el grupo quedan protegidos para no romper el historial.
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
