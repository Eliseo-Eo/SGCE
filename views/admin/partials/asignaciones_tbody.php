<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
?>
<?php foreach($Asignaciones as $Asg): ?>
<tr>
    <td class="searchable fw-medium"><?= htmlspecialchars($Asg['Maestro'], ENT_QUOTES, 'UTF-8') ?></td>
    <td class="searchable">
        <span class="AsignacionMateriaTexto" title="<?= htmlspecialchars($Asg['MateriaNombre'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($Asg['MateriaNombre'], ENT_QUOTES, 'UTF-8') ?></span>
    </td>
    <td class="searchable AsignacionGrupoTd">
        <?php $GrupoAsignacionEtiqueta = SgceGrupoNombreVisual($Asg, $TipoPeriodizacionAdmin); ?>
        <span class="AsignacionGrupoBadgeFull" title="<?= htmlspecialchars($GrupoAsignacionEtiqueta, ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($GrupoAsignacionEtiqueta, ENT_QUOTES, 'UTF-8') ?>
        </span>
    </td>
    <td class="text-center"><span class="badge rounded-pill text-bg-light"><?= (int)($Asg['HorasSemana'] ?? 0) ?> h</span></td>
    <td class="text-center">
        <div class="ExportIcons">
            <a class="ExportIcon ExportExcel" target="_blank" rel="noopener noreferrer" title="Exportar calificaciones en Excel" href="ExportarCalificaciones.php?AsignacionId=<?= (int)$Asg['Id'] ?>&Tipo=Excel">
                <i class="fa-solid fa-file-excel"></i><span class="ExportText">Excel</span>
            </a>
            <a class="ExportIcon ExportPdf" target="_blank" rel="noopener noreferrer" title="Exportar calificaciones en PDF" href="ExportarCalificaciones.php?AsignacionId=<?= (int)$Asg['Id'] ?>&Tipo=Pdf">
                <i class="fa-solid fa-file-pdf"></i><span class="ExportText">PDF</span>
            </a>
        </div>
    </td>
    <td class="text-center">
        <div class="ExportIcons">
            <a class="ExportIcon ExportExcel ExportHoy" target="_blank" rel="noopener noreferrer" title="Exportar asistencias de hoy en Excel" href="ExportarAsistencia.php?AsignacionId=<?= (int)$Asg['Id'] ?>&Tipo=Excel&Rango=Hoy">
                <i class="fa-solid fa-file-excel"></i><span class="ExportText">Excel</span>
            </a>
            <a class="ExportIcon ExportPdf ExportHoy" target="_blank" rel="noopener noreferrer" title="Exportar asistencias de hoy en PDF" href="ExportarAsistencia.php?AsignacionId=<?= (int)$Asg['Id'] ?>&Tipo=Pdf&Rango=Hoy">
                <i class="fa-solid fa-file-pdf"></i><span class="ExportText">PDF</span>
            </a>
        </div>
    </td>
    <td class="text-center">
        <div class="ExportIcons">
            <a class="ExportIcon ExportExcel ExportTodas" target="_blank" rel="noopener noreferrer" title="Exportar todas las asistencias en Excel" href="ExportarAsistencia.php?AsignacionId=<?= (int)$Asg['Id'] ?>&Tipo=Excel&Rango=Todas<?= $QueryCicloActivoAsistencia ?>">
                <i class="fa-solid fa-file-excel"></i><span class="ExportText">Excel</span>
            </a>
            <a class="ExportIcon ExportPdf ExportTodas" target="_blank" rel="noopener noreferrer" title="Exportar todas las asistencias en PDF" href="ExportarAsistencia.php?AsignacionId=<?= (int)$Asg['Id'] ?>&Tipo=Pdf&Rango=Todas<?= $QueryCicloActivoAsistencia ?>">
                <i class="fa-solid fa-file-pdf"></i><span class="ExportText">PDF</span>
            </a>
        </div>
    </td>
    <td class="text-center">
        <div class="AdminActions">
            <button class="ActionBtn ActionEdit BtnAsignacionEdit" data-bs-toggle="modal" data-bs-target="#EAsg<?= (int)$Asg['Id'] ?>">
                <i class="fa-solid fa-pen-to-square"></i><span>Editar</span>
            </button>
            <form method="POST" class="m-0 p-0" data-sgce-confirm="delete" data-sgce-confirm-title="DESACTIVAR ASIGNACIÓN" data-sgce-confirm-subtitle="CONTROL ACADÉMICO" data-sgce-confirm-message="¿DESEAS DESACTIVAR ESTA ASIGNACIÓN?" data-sgce-confirm-detail="Si ya tiene calificaciones o asistencias, el sistema protegerá la información relacionada." data-sgce-confirm-button="SÍ, DESACTIVAR" data-sgce-confirm-loading="DESACTIVANDO..." data-sgce-confirm-icon="fa-link-slash">
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
