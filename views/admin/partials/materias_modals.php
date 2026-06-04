<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
?>
<?php foreach($MateriasGrupo as $Mat): ?>
<div class="modal fade" id="EMat<?= $Mat['Id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <form method="POST">
                <?php echo CampoCsrf(); ?>
                <div class="modal-body">
                    <h6 class="mb-3 border-bottom pb-2">Modificar Materia</h6>
                    <input type="hidden" name="EditMateriaGrupo">
                    <input type="hidden" name="Tab" value="materias">
                    <input type="hidden" name="Id" value="<?= $Mat['Id'] ?>">
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
