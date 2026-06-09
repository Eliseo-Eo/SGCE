<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
?>
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
                        <input type="text" name="Nombre" value="<?= htmlspecialchars($Al['NombreCompleto'], ENT_QUOTES, 'UTF-8') ?>" class="form-control SoloLetrasMayus" maxlength="160" required pattern="^[A-ZÁÉÍÓÚÜÑ\s]+$" title="Solo letras y espacios" autocomplete="off">
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
