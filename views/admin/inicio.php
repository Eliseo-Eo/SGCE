<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
?>
        <?php if ($TabActual === 'inicio'): ?>
<div class="tab-pane fade show active SgceActivePane" id="inicio">
            <?php
                $TarjetasInicio = [
                    ['ALUMNOS ACTIVOS', $TotalAlumnosActivos, 'fa-children', 'var(--SgceAzul)'],
                    ['MAESTROS ACTIVOS', $TotalMaestrosActivos, 'fa-chalkboard-user', 'var(--SgceVerde)'],
                    ['GRUPOS ACTIVOS', $TotalGruposActivos, 'fa-users-rectangle', 'var(--SgceAmarillo)'],
                    ['ASISTENCIAS HOY', $AsistenciasHoy, 'fa-calendar-check', 'var(--SgceGuinda)'],
                    ['FALTAS HOY', $FaltasHoy, 'fa-circle-xmark', 'var(--SgceRojo)'],
                    ['PROMEDIO GENERAL', $PromedioGeneral, 'fa-star', '#7C3AED']
                ];
            ?>

            <?php
                $DashboardFechaHoy = date('d/m/Y');
                $DashboardCicloNombre = trim((string)($CicloActivo['Nombre'] ?? 'CICLO NO CONFIGURADO'));
                $DashboardEstadoTexto = 'Sin pases de lista hoy';
                $DashboardEstadoClase = 'StatusNeutral';
                if ((int)$AsistenciasHoy > 0 && (int)$FaltasHoy > 0) {
                    $DashboardEstadoTexto = 'Actividad con incidencias';
                    $DashboardEstadoClase = 'StatusWarning';
                } elseif ((int)$AsistenciasHoy > 0) {
                    $DashboardEstadoTexto = 'Asistencia registrada';
                    $DashboardEstadoClase = 'StatusOk';
                }

                $DashboardKpis = [
                    ['Alumnos activos', $TotalAlumnosActivos, '📚', 'KpiBlue', 'Inscritos vigentes'],
                    ['Maestros activos', $TotalMaestrosActivos, '👨‍🏫', 'KpiGreen', 'Docentes disponibles'],
                    ['Grupos activos', $TotalGruposActivos, '👥', 'KpiCyan', 'Etapa académica, grupo y turno'],
                    ['Asistencias hoy', $AsistenciasHoy, '✅', 'KpiAttendance', 'Registros del día'],
                    ['Faltas hoy', $FaltasHoy, '❌', 'KpiSoftRed', 'Incidencias actuales'],
                    ['Promedio general', $PromedioGeneral, '⭐', 'KpiGold', 'Ciclo activo']
                ];
            ?>

            <div class="DashboardTopPro">
                <section class="DashboardAccessPanel">
                    <div class="DashboardSectionHeader">
                        <div>
                            <span class="DashboardSectionKicker"><i class="fa-solid fa-grip"></i> Accesos rápidos</span>
                            <h2>Panel principal</h2>
                        </div>
                        <span class="DashboardMiniBadge"><?= $EsAdmin ? '16 módulos' : '10 módulos' ?></span>
                    </div>

                    <div class="DashboardModuleGridPro ModulosRecomendados">
                        <a href="AvisosAdmin.php" class="DashboardModuleCard DashboardModuleAnuncios">
                            <span class="SgceColorIcon" aria-hidden="true">📣</span>
                            <span>Anuncios</span>
                            <small>Comunicados</small>
                        </a>
                        <?php if (SgcePuedeAdministrarPeriodos($UserSession)): ?>
                        <a href="PeriodosAdmin.php" class="DashboardModuleCard DashboardModulePeriodos">
                            <span class="SgceColorIcon" aria-hidden="true">📅</span>
                            <span>Periodos</span>
                            <small>Ciclos</small>
                        </a>
                        <?php endif; ?>
                        <a href="Admin.php?Tab=maestros" class="DashboardModuleCard DashboardModuleMaestros">
                            <span class="SgceColorIcon" aria-hidden="true">👨‍🏫</span>
                            <span>Maestros</span>
                            <small>Docentes</small>
                        </a>
                        <a href="Admin.php?Tab=grupos" class="DashboardModuleCard DashboardModuleGrupos">
                            <span class="SgceColorIcon" aria-hidden="true">👥</span>
                            <span>Grupos</span>
                            <small>Etapa y turno</small>
                        </a>
                        <a href="Admin.php?Tab=materias" class="DashboardModuleCard DashboardModuleAsignaciones">
                            <span class="SgceColorIcon" aria-hidden="true">📘</span>
                            <span>Materias</span>
                            <small>Horas por grupo</small>
                        </a>
                        <a href="Admin.php?Tab=alumnos" class="DashboardModuleCard DashboardModuleAlumnos">
                            <span class="SgceColorIcon" aria-hidden="true">📚</span>
                            <span>Alumnos</span>
                            <small>Inscripciones</small>
                        </a>
                        <a href="Admin.php?Tab=asignaciones" class="DashboardModuleCard DashboardModuleAsignaciones">
                            <span class="SgceColorIcon" aria-hidden="true">📖</span>
                            <span>Asignaciones</span>
                            <small>Materias</small>
                        </a>
                        <a href="Admin.php?Tab=expedientes" class="DashboardModuleCard DashboardModuleExpedientes">
                            <span class="SgceColorIcon" aria-hidden="true">📁</span>
                            <span>Expedientes</span>
                            <small>Alumnos</small>
                        </a>
                        <a href="ReportesAdmin.php" class="DashboardModuleCard DashboardModuleReportes">
                            <span class="SgceColorIcon" aria-hidden="true">📈</span>
                            <span>Reportes</span>
                            <small>Centro</small>
                        </a>
                        <a href="PlaneacionesAdmin.php" class="DashboardModuleCard DashboardModulePlaneaciones">
                            <span class="SgceColorIcon" aria-hidden="true">☁️</span>
                            <span>Planeaciones</span>
                            <small>Docentes</small>
                        </a>
                        <a href="ConsultaPadre.php" class="DashboardModuleCard DashboardModuleAsistencias">
                            <span class="SgceColorIcon" aria-hidden="true">📅</span>
                            <span>Asistencias</span>
                            <small>Consulta individual</small>
                        </a>
                        <?php if (SgcePuedeGestionarUsuarios($UserSession)): ?>
                        <a href="UsuariosAdmin.php" class="DashboardModuleCard DashboardModuleUsuarios">
                            <span class="SgceColorIcon" aria-hidden="true">⚙️</span>
                            <span>Usuarios</span>
                            <small>Roles</small>
                        </a>
                        <?php endif; ?>
                        <?php if (SgcePuedeRespaldos($UserSession)): ?>
                        <a href="RestaurarBD.php" class="DashboardModuleCard DashboardModuleRespaldos">
                            <span class="SgceColorIcon" aria-hidden="true">💾</span>
                            <span>Respaldos</span>
                            <small>Datos</small>
                        </a>
                        <?php endif; ?>
                        <?php if (SgcePuedeMigrarCicloEscolar($UserSession)): ?>
                        <a href="MigracionAdmin.php" class="DashboardModuleCard DashboardModulePeriodos">
                            <span class="SgceColorIcon" aria-hidden="true">🔁</span>
                            <span>Migración</span>
                            <small>Cierre de ciclo</small>
                        </a>
                        <?php endif; ?>
                        <?php if (SgcePuedeConfigurarSistema($UserSession)): ?>
                        <a href="ConfiguracionAdmin.php" class="DashboardModuleCard DashboardModuleConfiguracion">
                            <span class="SgceColorIcon" aria-hidden="true">🏫</span>
                            <span>Configuración</span>
                            <small>Escuela</small>
                        </a>
                        <?php endif; ?>
                        <?php if (SgcePuedeBitacora($UserSession)): ?>
                        <a href="Admin.php?Tab=bitacora" class="DashboardModuleCard DashboardModuleBitacora">
                            <span class="SgceColorIcon" aria-hidden="true">🛡️</span>
                            <span>Bitácora</span>
                            <small>Movimientos</small>
                        </a>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="DashboardSummaryPanel">
                    <div class="DashboardSectionHeader">
                        <div>
                            <span class="DashboardSectionKicker"><i class="fa-solid fa-chart-simple"></i> Monitoreo escolar</span>
                            <h2>Resumen general</h2>
                        </div>
                        <span class="DashboardMiniBadge DashboardCycleBadge"><?= htmlspecialchars($DashboardCicloNombre, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>

                    <div class="DashboardTodayStrip">
                        <div class="DashboardTodayIcon"><span class="SgceColorIcon" aria-hidden="true">📅</span></div>
                        <div class="DashboardTodayText">
                            <span>Estado del día</span>
                            <strong><?= htmlspecialchars($DashboardFechaHoy, ENT_QUOTES, 'UTF-8') ?></strong>
                            <small>Resumen operativo del ciclo activo</small>
                        </div>
                        <div class="DashboardStatusPill <?= htmlspecialchars($DashboardEstadoClase, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($DashboardEstadoTexto, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>

                    <div class="DashboardKpiGrid">
                        <?php foreach($DashboardKpis as $Kpi): ?>
                            <article class="DashboardKpiCard <?= htmlspecialchars($Kpi[3], ENT_QUOTES, 'UTF-8') ?>">
                                <div class="DashboardKpiIcon"><span class="SgceColorIcon" aria-hidden="true"><?= htmlspecialchars($Kpi[2], ENT_QUOTES, 'UTF-8') ?></span></div>
                                <div class="DashboardKpiValue"><?= htmlspecialchars((string)$Kpi[1], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="DashboardKpiLabel"><?= htmlspecialchars($Kpi[0], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="DashboardKpiHint"><?= htmlspecialchars($Kpi[4], ENT_QUOTES, 'UTF-8') ?></div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <div class="row g-3 DashboardRiskRow">

                <div class="col-12">
                    <section class="card card-custom DashboardRiskPro">
                        <div class="DashboardRiskHeader">
                            <div>
                                <span class="DashboardSectionKicker DashboardRiskKicker"><i class="fa-solid fa-triangle-exclamation"></i> Seguimiento preventivo</span>
                                <h2>Alumnos con mayor riesgo académico y de asistencia</h2>
                                <p>
                                    El riesgo se calcula con faltas, retardos y promedio menor a 7 dentro del ciclo activo.
                                </p>
                            </div>
                            <span class="DashboardRiskCount"><?= count($AlumnosRiesgo) ?> registros</span>
                        </div>

                        <?php if (count($AlumnosRiesgo) === 0): ?>
                            <div class="DashboardRiskEmpty">
                                <div class="DashboardRiskEmptyIcon"><span class="SgceColorIcon" aria-hidden="true">✅</span></div>
                                <div>
                                    <strong>Sin alumnos en riesgo por ahora</strong>
                                    <span>Cuando existan faltas, retardos o promedios bajos, aparecerán aquí automáticamente.</span>
                                </div>
                            </div>
                        <?php else: ?>
                        <div class="table-responsive DashboardRiskTableWrap">
                            <table class="table table-hover align-middle text-center DashboardRiskTable">
                                <thead>
                                    <tr>
                                        <th>Alumno</th>
                                        <th>Grupo</th>
                                        <th>Turno</th>
                                        <th>Prom.</th>
                                        <th>Faltas</th>
                                        <th>Retardos</th>
                                        <th>Nivel</th>
                                        <th>Motivo</th>
                                        <th>Puntos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach($AlumnosRiesgo as $R): ?>
                                    <?php
                                        $ClaseNivelRiesgo = 'bg-warning text-dark';
                                        if ($R['NivelRiesgo'] === 'ALTO') { $ClaseNivelRiesgo = 'bg-danger'; }
                                        if ($R['NivelRiesgo'] === 'MEDIO') { $ClaseNivelRiesgo = 'bg-warning text-dark'; }
                                        if ($R['NivelRiesgo'] === 'BAJO') { $ClaseNivelRiesgo = 'bg-info text-dark'; }
                                    ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($R['NombreCompleto']) ?></td>
                                        <td><?= htmlspecialchars($R['Grado'].' '.$R['Grupo']) ?></td>
                                        <td><span class="badge <?= $R['Turno']==='MATUTINO'?'bg-primary':'bg-warning text-dark' ?>"><?= htmlspecialchars($R['Turno']) ?></span></td>
                                        <td><span class="badge <?= ($R['Promedio'] !== null && (float)$R['Promedio'] < 7) ? 'bg-danger' : 'bg-success' ?>"><?= $R['Promedio'] !== null ? htmlspecialchars($R['Promedio']) : 'S/C' ?></span></td>
                                        <td><span class="badge bg-danger"><?= (int)$R['Faltas'] ?></span></td>
                                        <td><span class="badge bg-warning text-dark"><?= (int)$R['Retardos'] ?></span></td>
                                        <td><span class="badge <?= $ClaseNivelRiesgo ?>"><?= htmlspecialchars($R['NivelRiesgo']) ?></span></td>
                                        <td><span class="badge bg-dark"><?= htmlspecialchars($R['MotivoRiesgo'] !== '' ? $R['MotivoRiesgo'] : 'SIN MOTIVO') ?></span></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($R['PuntajeRiesgo']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </section>
                </div>
            </div>
        </div>

        

        
<?php endif; ?>
