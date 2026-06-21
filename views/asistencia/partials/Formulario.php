<?php if (!defined('SGCE_APP')) { exit; } ?>
<div class="card MainCard">
    <div class="card-header bg-white border-0 p-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h4 class="fw-bold mb-1">Lista de alumnos</h4>
                <div class="text-muted">Selecciona asistencia y registra conducta solo cuando exista una incidencia.</div>
            </div>
            <div class="badge bg-dark rounded-pill px-4 py-3"><?= (int)count($Alumnos) ?> Alumnos</div>
        </div>
    </div>
    <div class="card-body p-0">
        <form method="POST" id="FormPaseLista">
            <?= CampoCsrf() ?>
            <input type="hidden" name="asignacion_id" value="<?= (int)$AsignacionId ?>">
            <input type="hidden" name="Fecha" value="<?= HGlobal($FechaConsulta) ?>">
            <div class="table-responsive">
                <table class="table align-middle mb-0 SgceTable SgceAttendanceMobileTable SgceConductaTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Alumno</th>
                            <th class="text-center SgceAsistenciaEstadoCol">Estado</th>
                            <th class="SgceConductaCol">Conducta y disciplina</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if(empty($Alumnos)): ?>
                        <tr><td colspan="3"><?= SgceComponenteTablaVacia('No hay alumnos registrados') ?></td></tr>
                    <?php else: foreach($Alumnos as $a): ?>
                        <?php
                            $AlumnoIdFila = (int)$a['Id'];
                            $EstadoActual = $EstadosRegistrados[$AlumnoIdFila] ?? 'A';
                            $ConductaActual = $ConductaPaseLista[$AlumnoIdFila] ?? null;
                            $TieneConducta = is_array($ConductaActual) && (($ConductaActual['Estado'] ?? '') !== 'CANCELADO');
                            $ConductaBloqueada = $TieneConducta && (($ConductaActual['Estado'] ?? 'PENDIENTE') !== 'PENDIENTE');
                            $TipoActual = $ConductaActual['Tipo'] ?? 'REPORTE';
                            $SeveridadActual = $ConductaActual['Severidad'] ?? 'LEVE';
                            $RegistrarActual = ($TieneConducta && !$ConductaBloqueada) ? '1' : '0';
                            $TextoBotonConducta = $TieneConducta ? ($ConductaBloqueada ? 'Ver reporte' : 'Editar reporte') : 'Registrar reporte';
                            $TextoEstadoConducta = $TieneConducta ? ('Conducta: ' . SgceConductaTextoEstado((string)$ConductaActual['Estado'])) : 'Conducta normal por defecto.';
                            $ClaseEstadoConducta = $TieneConducta ? 'text-success fw-bold' : 'text-muted';
                        ?>
                        <tr class="SgceConductaAlumnoRow" data-alumno-id="<?= $AlumnoIdFila ?>">
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="AlumnoAvatar AlumnoAvatarEmoji"><span class="SgceAlumnoEmoji" aria-hidden="true">🧑‍🎓</span></div>
                                    <div>
                                        <div class="AlumnoNombre"><?= HGlobal($a['NombreCompleto']) ?></div>
                                        <small class="SgceConductaFilaEstado <?= HGlobal($ClaseEstadoConducta) ?>"><?= HGlobal($TextoEstadoConducta) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <select name="estado[<?= $AlumnoIdFila ?>]" class="form-select EstadoSelect">
                                    <option value="A" <?= $EstadoActual==='A'?'selected':'' ?>>✅ Asistencia</option>
                                    <option value="F" <?= $EstadoActual==='F'?'selected':'' ?>>❌ Falta</option>
                                    <option value="R" <?= $EstadoActual==='R'?'selected':'' ?>>⏰ Retardo</option>
                                    <option value="J" <?= $EstadoActual==='J'?'selected':'' ?>>📄 Justificante</option>
                                </select>
                            </td>
                            <td class="SgceConductaCell">
                                <input type="hidden" class="ConductaRegistrar" name="conducta[<?= $AlumnoIdFila ?>][registrar]" value="<?= HGlobal($RegistrarActual) ?>">
                                <input type="hidden" class="ConductaTipo" name="conducta[<?= $AlumnoIdFila ?>][tipo]" value="<?= HGlobal($TipoActual) ?>">
                                <input type="hidden" class="ConductaSeveridad" name="conducta[<?= $AlumnoIdFila ?>][severidad]" value="<?= HGlobal($SeveridadActual) ?>">
                                <input type="hidden" class="ConductaCategoria" name="conducta[<?= $AlumnoIdFila ?>][categoria]" value="<?= HGlobal($ConductaActual['Categoria'] ?? '') ?>">
                                <input type="hidden" class="ConductaMotivo" name="conducta[<?= $AlumnoIdFila ?>][motivo]" value="<?= HGlobal($ConductaActual['MotivoCorto'] ?? '') ?>">
                                <input type="hidden" class="ConductaDetalle" name="conducta[<?= $AlumnoIdFila ?>][detalle]" value="<?= HGlobal($ConductaActual['Detalle'] ?? '') ?>">
                                <input type="hidden" class="ConductaAccion" name="conducta[<?= $AlumnoIdFila ?>][accion]" value="<?= HGlobal($ConductaActual['AccionTomada'] ?? '') ?>">
                                <input type="hidden" class="ConductaVisiblePadre" name="conducta[<?= $AlumnoIdFila ?>][visible_padre]" value="<?= !empty($ConductaActual['VisiblePadre']) ? '1' : '0' ?>">
                                <button type="button"
                                        class="btn SgceConductaModalBtn <?= $TieneConducta ? 'is-registered' : '' ?>"
                                        data-alumno-id="<?= $AlumnoIdFila ?>"
                                        data-alumno-nombre="<?= HGlobal($a['NombreCompleto']) ?>"
                                        data-conducta-bloqueada="<?= $ConductaBloqueada ? '1' : '0' ?>"
                                        data-conducta-estado="<?= HGlobal($ConductaActual['Estado'] ?? 'PENDIENTE') ?>"
                                        data-conducta-visible-padre="<?= !empty($ConductaActual['VisiblePadre']) ? '1' : '0' ?>"
                                        data-clase-contexto="<?= HGlobal(($InfoClase['Grado'] ?? '') . '° ' . ($InfoClase['Grupo'] ?? '') . ' · ' . ($InfoClase['MateriaNombre'] ?? '')) ?>">
                                    <span class="SgceConductaBtnIcon" aria-hidden="true"><?= $TieneConducta ? '✅' : '📝' ?></span>
                                    <span class="SgceConductaBtnText"><?= HGlobal($TextoBotonConducta) ?></span>
                                </button>
                                <?php if($ConductaBloqueada): ?>
                                    <div class="small text-muted mt-1">Revisado por administración.</div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="modal fade" id="ModalConductaPaseLista" tabindex="-1" aria-labelledby="ModalConductaPaseListaTitulo" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg modal-fullscreen-sm-down SgceConductaModalDialog">
                    <div class="modal-content SgceConductaModalContent">
                        <div class="modal-header SgceConductaModalHeader">
                            <div class="SgceConductaModalTitleWrap">
                                <div class="SgceConductaModalIcon" aria-hidden="true"><i class="fa-solid fa-user-shield"></i></div>
                                <div class="SgceConductaModalTitleText">
                                    <h5 class="modal-title" id="ModalConductaPaseListaTitulo">Reporte de conducta</h5>
                                    <div class="SgceConductaModalSubtitulo">Control de conducta y disciplina</div>
                                    <div class="SgceConductaAlumnoPill" aria-live="polite">
                                        <div class="SgceConductaAlumnoLabel small" id="ModalConductaAlumnoNombre">Selecciona un alumno.</div>
                                        <div class="SgceConductaContextLabel small" id="ModalConductaClaseContexto"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-body">
                            <div class="SgceConductaModalNotice small" id="ModalConductaAviso">
                                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                                <span>La conducta normal no se captura. Usa este formulario solo cuando exista una incidencia o reconocimiento.</span>
                            </div>
                            <label class="SgceConductaPadreBox mb-3" for="ModalConductaVisiblePadre">
                                <input type="checkbox" id="ModalConductaVisiblePadre" value="1">
                                <span class="SgceConductaPadreIcon" aria-hidden="true"><i class="fa-solid fa-eye-slash"></i></span>
                                <span class="SgceConductaPadreText">
                                    <strong>Visible para padres al validarse</strong>
                                    <small>El padre/tutor lo verá solo cuando administración lo valide o cierre el seguimiento.</small>
                                    <span class="SgceConductaPadreEstado" id="ModalConductaVisiblePadreEstado">No visible para padres</span>
                                </span>
                            </label>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label" for="ModalConductaTipo">Tipo</label>
                                    <select class="form-select" id="ModalConductaTipo">
                                        <?php foreach(SgceConductaTipos() as $TipoConducta): ?>
                                            <option value="<?= HGlobal($TipoConducta) ?>"><?= HGlobal(SgceConductaTextoTipo($TipoConducta)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="ModalConductaSeveridad">Severidad</label>
                                    <select class="form-select" id="ModalConductaSeveridad">
                                        <?php foreach(SgceConductaSeveridades() as $SeveridadConducta): ?>
                                            <option value="<?= HGlobal($SeveridadConducta) ?>"><?= HGlobal(SgceConductaTextoSeveridad($SeveridadConducta)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="ModalConductaCategoria">Categoría</label>
                                    <input type="text" class="form-control SgceConductaUppercase" id="ModalConductaCategoria" maxlength="80" placeholder="DISCIPLINA">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="ModalConductaMotivo">Motivo corto <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control SgceConductaUppercase" id="ModalConductaMotivo" maxlength="180" placeholder="EJ. INTERRUMPE LA CLASE">
                                    <div class="invalid-feedback">Captura el motivo corto del reporte.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="ModalConductaDetalle">Detalle opcional</label>
                                    <textarea class="form-control SgceConductaUppercase" id="ModalConductaDetalle" rows="4" maxlength="1200" placeholder="DESCRIBE BREVEMENTE LO OCURRIDO"></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="ModalConductaAccion">Acción tomada</label>
                                    <textarea class="form-control SgceConductaUppercase" id="ModalConductaAccion" rows="4" maxlength="800" placeholder="DIÁLOGO, CANALIZACIÓN, AVISO A PREFECTURA"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer d-flex justify-content-between gap-2 flex-wrap">
                            <button type="button" class="btn SgceConductaRemoveBtn" id="ModalConductaQuitar"><i class="fa-solid fa-trash-can" aria-hidden="true"></i><span>Quitar reporte</span></button>
                            <div class="SgceConductaFooterActions ms-auto">
                                <button type="button" class="btn SgceConductaCancelBtn" id="ModalConductaCancelar" data-bs-dismiss="modal"><i class="fa-solid fa-xmark" aria-hidden="true"></i><span>Cancelar</span></button>
                                <button type="button" class="btn SgceConductaSaveBtn" id="ModalConductaGuardar"><i class="fa-solid fa-check" aria-hidden="true"></i><span>Guardar en lista</span></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($Alumnos)): ?>
                <div class="SgceStickyActions border-top">
                    <button type="submit" name="guardar" id="BtnGuardarAsistenciaVerdeMetalico" class="btn BtnGuardar BtnAsistenciaVerdeMetalico">
                        <span class="me-2" aria-hidden="true">💾</span><?= $YaSeRegistro ? 'Actualizar pase de lista' : 'Guardar pase de lista' ?>
                    </button>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>
