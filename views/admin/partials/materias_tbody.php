<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
?>
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
