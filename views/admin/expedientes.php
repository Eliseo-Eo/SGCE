<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
?>
<?php if ($TabActual === 'expedientes'): ?>
<div class="tab-pane fade show active SgceActivePane" id="expedientes">
            <div class="card card-custom ExpedientesCard">
                <div class="card-body ExpedientesCardBody">

                    <div class="ExpedientesTop">
                        <div class="ExpedientesTitleBlock">
                            <span class="ExpedientesTitleIcon"><span class="SgceColorIcon" aria-hidden="true">🔎</span></span>
                            <div>
                                <h4>Expedientes de Alumnos</h4>
                                <p>Selecciona un grupo para consultar solo el padrón correspondiente y abrir el historial individual.</p>
                            </div>
                        </div>
                    </div>

                    <form method="GET" action="Admin.php" class="ExpedientesFilterForm">
                        <input type="hidden" name="Tab" value="expedientes">
                        <div class="ExpedientesFilterGrid <?= $ExpedienteGrupoId > 0 ? 'HasCleanButton' : '' ?>">
                            <div class="ExpedientesGroupField">
                                <label><?= htmlspecialchars($EtiquetaEtapaAdmin, ENT_QUOTES, 'UTF-8') ?> / Grupo / Turno</label>
                                <select name="ExpGrupoId" class="form-select" required>
                                    <option value="">SELECCIONA GRUPO...</option>
                                    <?php foreach($Grupos as $GExp): ?>
                                        <option value="<?= (int)$GExp['Id'] ?>" <?= ((int)$ExpedienteGrupoId === (int)$GExp['Id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(SgceGrupoNombreVisual($GExp, $TipoPeriodizacionAdmin), ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="ExpedientesActionField">
                                <label>Acción</label>
                                <button type="submit" id="BtnCargarExpedientesVerdeMetalico" class="BtnExpedienteLoadVerdeMetalico">
                                    <span aria-hidden="true">📑</span><span>Cargar Expedientes</span>
                                </button>
                            </div>

                            <?php if($ExpedienteGrupoId > 0): ?>
                            <div class="ExpedientesActionField">
                                <label>Restablecer</label>
                                <a href="Admin.php?Tab=expedientes" class="ActionBtn BtnExpedienteClean">
                                    <i class="fa-solid fa-eraser"></i><span>Limpiar</span>
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </form>

                    <?php if($ExpedienteGrupoId <= 0): ?>
                        <div class="ExpedientesEmptyState">
                            <span><i class="fa-solid fa-circle-info"></i></span>
                            <div>
                                <strong>Selecciona un grupo para cargar expedientes.</strong>
                                <p>Así el sistema evita consultar todos los alumnos y mantiene la pantalla rápida y ordenada.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="ExpedientesTools">
                            <div class="ExpedientesSelectedGroup">
                                <i class="fa-solid fa-users"></i>
                                <span>Grupo seleccionado:</span>
                                <strong>
                                    <?= $GrupoExpedienteSeleccionado
                                        ? htmlspecialchars(SgceGrupoNombreVisual($GrupoExpedienteSeleccionado, $TipoPeriodizacionAdmin), ENT_QUOTES, 'UTF-8')
                                        : 'NO DISPONIBLE' ?>
                                </strong>
                            </div>

                            <div class="input-group search-container ExpedientesSearch">
                                <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                                <input type="text" id="SearchExpedientes" class="form-control" placeholder="Buscar expediente...">
                            </div>
                        </div>

                        <div class="table-responsive ExpedientesTableWrap">
                            <table class="table table-hover align-middle" id="TableExpedientes">
                                <thead>
                                    <tr>
                                        <th>Alumno</th>
                                        <th class="text-center">Grupo</th>
                                        <th class="text-center">Turno</th>
                                        <th class="text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($AlumnosExpedientes as $Al): ?>
                                    <tr>
                                        <td class="searchable ExpedientesAlumnoNombre"><?= htmlspecialchars($Al['NombreCompleto'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="searchable text-center">
                                            <span class="ExpedientesGroupBadge"><?= htmlspecialchars(SgceGrupoNombreVisual($Al, $TipoPeriodizacionAdmin), ENT_QUOTES, 'UTF-8') ?></span>
                                        </td>
                                        <td class="searchable text-center">
                                            <span class="ExpedientesTurnBadge"><?= htmlspecialchars($Al['Turno'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </td>
                                        <td class="text-center">
                                            <a class="ActionBtn BtnExpedienteOpen" href="HistorialAlumno.php?AlumnoId=<?= $Al['Id'] ?>">
                                                <span class="SgceColorIcon" aria-hidden="true">📂</span><span>Abrir Expediente</span>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>

                                    <?php if(count($AlumnosExpedientes) === 0): ?>
                                    <tr>
                                        <td colspan="4" class="ExpedientesNoData">NO HAY ALUMNOS ACTIVOS EN ESTE GRUPO.</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div id="PagerExpedientes" class="SgcePagerServer ExpedientesPager"></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        

        
<?php endif; ?>
