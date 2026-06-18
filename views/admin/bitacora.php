<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
?>
<?php if ($PuedeVerBitacora && $TabActual === 'bitacora'): ?>
                <div class="tab-pane fade show active SgceActivePane" id="bitacora">
                    <div class="card card-custom p-4 SgceBitacoraCard">
                        <div class="SgceBitacoraHead">
                            <div class="SgceBitacoraTitle">
                                <span class="SgceBitacoraIcon"><span class="SgceColorIcon" aria-hidden="true">🕘</span></span>
                                <div>
                                    <h4>BITÁCORA DE MOVIMIENTOS</h4>
                                    <p>Aquí se muestran los últimos movimientos importantes del sistema: Altas, modificaciones, bajas, importaciones, asistencia y calificaciones.</p>
                                </div>
                            </div>

                            <form method="GET" class="SgceBitacoraTools SgceBitacoraFilterForm SgceServerFilterBar" data-sgce-server-filter="1">
                                <input type="hidden" name="Tab" value="bitacora">
                                <input type="hidden" name="PagBitacora" value="1">

                                <div class="SgceBitacoraFilterSearch">
                                    <div class="SgceSearchBox SgceSearchBoxSmall">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                        <input type="text" id="SearchBitacora" name="BuscarBitacora" value="<?= htmlspecialchars((string)($FiltroBitacora['buscar'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar movimiento...">
                                    </div>
                                </div>

                                <div class="SgceBitacoraFilterRow SgceBitacoraFilterRowSelects">
                                    <select name="AccionFiltro" class="form-select form-select-sm SgceQuickFilter" aria-label="Filtrar bitácora por acción">
                                        <option value="">Acción</option>
                                        <?php foreach($BitacoraAccionesFiltro as $AccionFiltroItem): ?>
                                            <option value="<?= htmlspecialchars($AccionFiltroItem, ENT_QUOTES, 'UTF-8') ?>"<?= $SgceSelected($FiltroBitacora['accion'] ?? '', $AccionFiltroItem) ?>><?= htmlspecialchars($AccionFiltroItem, ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="UsuarioIdFiltro" class="form-select form-select-sm SgceQuickFilter" aria-label="Filtrar bitácora por usuario">
                                        <option value="">Usuario</option>
                                        <?php foreach($BitacoraUsuariosFiltro as $UsuarioFiltroItem): ?>
                                            <option value="<?= (int)$UsuarioFiltroItem['Id'] ?>"<?= $SgceSelected($FiltroBitacora['usuario_id'] ?? '', (int)$UsuarioFiltroItem['Id']) ?>><?= htmlspecialchars($UsuarioFiltroItem['NombreCompleto'], ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="SgceBitacoraFilterRow SgceBitacoraFilterRowDates">
                                    <input type="date" name="DesdeFiltro" value="<?= htmlspecialchars((string)($FiltroBitacora['desde'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="form-control form-control-sm SgceQuickFilter" aria-label="Desde">
                                    <input type="date" name="HastaFiltro" value="<?= htmlspecialchars((string)($FiltroBitacora['hasta'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="form-control form-control-sm SgceQuickFilter" aria-label="Hasta">
                                </div>

                                <div class="SgceBitacoraFilterActions">
                                    <a class="SgceClearFiltersBtn" href="Admin.php?Tab=bitacora" title="Limpiar filtros"><i class="fa-solid fa-eraser"></i><span>Limpiar</span></a>
                                    <div class="SgceCountPill"><i class="fa-solid fa-clock-rotate-left"></i><span><?= (int)$TotalBitacoraTabla ?> registros</span></div>
                                </div>
                            </form>
                        </div>

                        <div class="SgceInfoBanner mb-4">
                            <i class="fa-solid fa-circle-info"></i>
                            <span>Esta pantalla registra movimientos importantes. Por rendimiento se muestra por defecto el rango de los últimos 30 días; puedes ampliar el rango hasta 370 días por consulta.</span>
                        </div>

                        <div class="table-responsive SgceTableWrap">
                            <table class="table table-hover align-middle text-center SgceBitacoraTable" id="TableBitacora" data-sgce-server-paged="1">
                                <colgroup>
                                    <col class="SgceBitColFecha">
                                    <col class="SgceBitColUsuario">
                                    <col class="SgceBitColRol">
                                    <col class="SgceBitColAccion">
                                    <col class="SgceBitColTabla">
                                    <col class="SgceBitColRegistro">
                                    <col class="SgceBitColDetalle">
                                    <col class="SgceBitColIp">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Usuario</th>
                                        <th>Rol</th>
                                        <th>Acción</th>
                                        <th>Tabla</th>
                                        <th>Registro</th>
                                        <th>Detalle</th>
                                        <th>IP</th>
                                    </tr>
                                </thead>
                                <tbody data-sgce-partial-tbody="bitacora">
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
                                </tbody>
                            </table>
                        </div>

                        <div class="SgcePartialPager" data-sgce-partial-pager="bitacora">


                            <?= SgceRenderPager('PagBitacora', $PagBitacora, $TotalBitacoraTabla, $PageSizeBitacora, ['Tab' => 'bitacora']) ?>


                        </div>
                    </div>
                </div>
        <?php endif; ?>
