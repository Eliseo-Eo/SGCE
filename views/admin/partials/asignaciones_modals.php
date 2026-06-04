<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
?>
<?php foreach($Asignaciones as $Asg): ?>
<div class="modal fade" id="EAsg<?= $Asg['Id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered ModalEditarPro">
        <div class="modal-content">
            <form method="POST">
                <?php echo CampoCsrf(); ?>
                <div class="modal-body">
                    <h6 class="mb-3 border-bottom pb-2">Editar Asignación</h6>
                    <input type="hidden" name="EditAsignacion">
                    <input type="hidden" name="Tab" value="asignaciones">
                    <input type="hidden" name="Id" value="<?= $Asg['Id'] ?>">
                    <label class="small text-muted">Docente</label>
                    <select name="MaestroId" class="form-select mb-2 SgceSearchableSelect" data-sgce-searchable-select="1" data-sgce-search-placeholder="Buscar docente..." required>
                        <?php foreach($Maestros as $M): ?>
                            <option value="<?= $M['Id'] ?>" <?= $M['Id'] == $Asg['MaestroId'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($M['NombreCompleto']) ?>
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
