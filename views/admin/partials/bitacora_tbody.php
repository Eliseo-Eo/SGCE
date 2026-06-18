<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
?>
<?php if (empty($BitacoraReciente)): ?>
<tr>
    <td colspan="8" class="py-5 text-muted fw-bold">
        <i class="fa-solid fa-inbox fa-2x d-block mb-2"></i>
        TODAVÍA NO HAY MOVIMIENTOS REGISTRADOS.
    </td>
</tr>
<?php else: ?>
    <?php foreach($BitacoraReciente as $Mov): ?>
        <tr>
            <td class="fw-bold searchable SgceBitCellFecha" title="<?= htmlspecialchars(date('d/m/Y H:i', strtotime($Mov['FechaRegistro'])), ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars(date('d/m/Y H:i', strtotime($Mov['FechaRegistro'])), ENT_QUOTES, 'UTF-8') ?>
            </td>
            <td class="searchable SgceBitCellUsuario" title="<?= htmlspecialchars($Mov['NombreCompleto'] ?: 'SISTEMA', ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($Mov['NombreCompleto'] ?: 'SISTEMA', ENT_QUOTES, 'UTF-8') ?>
            </td>
            <td class="searchable">
                <span class="badge bg-dark SgceBitBadgeRol" title="<?= htmlspecialchars(strtoupper((string)($Mov['Rol'] ?? '-')), ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars(strtoupper((string)($Mov['Rol'] ?? '-')), ENT_QUOTES, 'UTF-8') ?>
                </span>
            </td>
            <td class="searchable">
                <span class="badge bg-primary SgceBitBadgeAccion" title="<?= htmlspecialchars((string)$Mov['Accion'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars((string)$Mov['Accion'], ENT_QUOTES, 'UTF-8') ?>
                </span>
            </td>
            <td class="searchable SgceBitCellTabla" title="<?= htmlspecialchars((string)($Mov['TablaAfectada'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($Mov['TablaAfectada'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
            <td class="searchable SgceBitCellRegistro"><?= htmlspecialchars((string)($Mov['RegistroId'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
            <td class="text-start searchable SgceBitCellDetalle" title="<?= htmlspecialchars((string)($Mov['Detalle'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars((string)($Mov['Detalle'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
            </td>
            <td class="searchable SgceBitCellIp"><?= htmlspecialchars((string)($Mov['Ip'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>
