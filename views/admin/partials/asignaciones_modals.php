<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
?>
<?php foreach($Asignaciones as $Asg): ?>
<div class="modal fade SgceAsignacionEditModal" id="EAsg<?= (int)$Asg['Id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered ModalEditarPro SgceAsignacionEditDialog">
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
                    <label class="small text-muted">Motivo del relevo/interinato</label>
                    <input type="text" name="MotivoRelevo" class="form-control mb-3" maxlength="255" placeholder="Escribe el motivo del cambio docente, relevo o interinato." autocomplete="off" required>
                    <button class="btn btn-primary w-100"><i class="fa-solid fa-floppy-disk"></i> Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>
