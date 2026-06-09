<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
?>
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
