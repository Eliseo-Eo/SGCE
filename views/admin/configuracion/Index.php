<?php if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); } ?>
<!DOCTYPE html>
<html lang="es">
<head>
<?= SgceLayoutHeadBase('Configuración | SGCE', $Pdo, ['assets/css/configuracion-botones-metalicos.css']) ?>
</head>
<body>
<div class="SgcePageWrap SgceModuleWrap container-fluid px-4 py-4">
    <section class="SgceHero mb-4">
        <div class="SgceHeroInfo">
            <div class="SgceHeroIcon"><span class="SgceColorIcon" aria-hidden="true">🏫</span></div>
            <div>
                <h1>CONFIGURACIÓN GENERAL</h1>
                <p>Datos institucionales, ciclo escolar activo y periodos usados en reportes, boletas y paneles.</p>
            </div>
        </div>
        <div class="SgceHeroActions">
            <a href="Admin.php?Tab=inicio" class="SgceBtnVolverInicio" title="Volver al inicio" aria-label="Volver al inicio"><i class="fa-solid fa-house"></i><span>Volver al inicio</span></a>
        </div>
    </section>

    <?php if ($Mensaje !== ''): ?>
        <div class="alert alert-<?= HConfig($MensajeTipo) ?> alert-dismissible fade show shadow-sm border-0 rounded-4" role="alert">
            <i class="fa-solid <?= $MensajeTipo === 'success' ? 'fa-circle-check' : 'fa-circle-xmark' ?> me-2"></i><?= HConfig($Mensaje) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>

    <form method="post" class="SgceConfigFormRedisenada SgceConfigGridRedisenada">
        <?= CampoCsrf() ?>
        <div class="SgceConfigTwoColumns">
        <div class="SgceConfigLeftCol">
        <section class="SgceConfigCard SgceConfigCardWide SgceConfigSchoolCard">
            <div class="SgceConfigHead">
                <span><span class="SgceColorIcon" aria-hidden="true">🏫</span></span>
                <div><h2>Datos de la escuela</h2><p>Esta información aparece en boletas, reportes y pantallas públicas.</p></div>
            </div>
            <div class="row g-3">
                <div class="col-md-6"><label class="SgceFieldLabel">Nombre oficial</label><input class="form-control FormControl InputUpper" name="NombreEscuela" value="<?= HConfig($Config['NombreEscuela']) ?>" required></div>
                <div class="col-md-6"><label class="SgceFieldLabel">CCT / Clave</label><input class="form-control FormControl InputUpper" name="ClaveCentroTrabajo" value="<?= HConfig($Config['ClaveCentroTrabajo']) ?>"></div>
                <div class="col-md-6"><label class="SgceFieldLabel">Director(a)</label><input class="form-control FormControl InputUpper" name="DirectorNombre" value="<?= HConfig($Config['DirectorNombre']) ?>"></div>
                <div class="col-md-6"><label class="SgceFieldLabel">Municipio y estado</label><input class="form-control FormControl InputUpper" name="MunicipioEstado" value="<?= HConfig($Config['MunicipioEstado']) ?>"></div>
                <div class="col-md-6"><label class="SgceFieldLabel">Teléfono</label><input class="form-control FormControl InputDigits" name="TelefonoEscuela" value="<?= HConfig($Config['TelefonoEscuela']) ?>" inputmode="numeric" maxlength="15" pattern="\d{0,15}"></div>
                <div class="col-md-6"><label class="SgceFieldLabel">Correo institucional</label><input class="form-control FormControl" type="email" name="CorreoEscuela" value="<?= HConfig($Config['CorreoEscuela']) ?>" maxlength="120"></div>
                <div class="col-md-6"><label class="SgceFieldLabel">Lema o leyenda inferior</label><input class="form-control FormControl" name="LemaInstitucional" value="<?= HConfig($Config['LemaInstitucional']) ?>"></div>
                <div class="col-md-6"><label class="SgceFieldLabel">Color institucional</label><div class="SgceColorControl"><input class="form-control FormControl" type="color" name="ColorInstitucional" id="ColorInstitucional" value="<?= HConfig(SgceNormalizarColorHex($Config['ColorInstitucional'] ?? '#97051E')) ?>"><span id="ColorInstitucionalTexto"><?= HConfig(SgceNormalizarColorHex($Config['ColorInstitucional'] ?? '#97051E')) ?></span></div></div>
            </div>
        </section>

        <section class="SgceConfigCard SgceConfigCardWide SgceConfigAcademicCard">
            <div class="SgceConfigHead">
                <span><span class="SgceColorIcon" aria-hidden="true">🧭</span></span>
                <div>
                    <h2>Estructura académica</h2>
                    <p>Define si el sistema trabajará como primaria, secundaria, bachillerato, universidad, maestría, doctorado o curso. El módulo de migración usará esta estructura académica.</p>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="SgceFieldLabel">Nombre de la oferta educativa</label>
                    <input class="form-control FormControl InputUpper" name="NombreOfertaAcademica" value="<?= HConfig($Config['NombreOfertaAcademica'] ?? ($OfertaActivaConfig['Nombre'] ?? 'SECUNDARIA')) ?>" maxlength="140" required>
                </div>
                <div class="col-md-6">
                    <label class="SgceFieldLabel">Nivel educativo</label>
                    <select name="NivelEducativo" class="form-select FormControl" required>
                        <?php foreach(SgceNivelEducativoOpciones() as $ClaveNivel => $TextoNivel): ?>
                            <option value="<?= HConfig($ClaveNivel) ?>" <?= $NivelEducativoConfig === $ClaveNivel ? 'selected' : '' ?>><?= HConfig($TextoNivel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 SgceAcademicOrgField">
                    <label class="SgceFieldLabel">Organización académica</label>
                    <select name="TipoPeriodizacion" class="form-select FormControl" required>
                        <?php foreach(SgceTipoPeriodizacionOpciones() as $ClaveTipo => $TextoTipo): ?>
                            <option value="<?= HConfig($ClaveTipo) ?>" <?= $TipoPeriodizacionConfig === $ClaveTipo ? 'selected' : '' ?>><?= HConfig($TextoTipo) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 SgceAcademicStagesField">
                    <label class="SgceFieldLabel">Cantidad de <?= HConfig($EtiquetaEtapaUiMinus) ?>s / etapas</label>
                    <input class="form-control FormControl InputDigits" name="TotalEtapas" value="<?= HConfig((string)$TotalEtapasConfig) ?>" min="1" max="20" maxlength="2" inputmode="numeric" required>
                </div>
                <div class="col-12 SgcePrettyCheckWrap SgceAcademicProgramsCheckFull">
                    <label class="SgcePrettyCheck SgcePrettyCheckHorizontal">
                        <input class="form-check-input" type="checkbox" name="UsaProgramas" value="1" <?= $UsaProgramasConfig ? 'checked' : '' ?>>
                        <span><strong>Usa programas educativos</strong><small>Programas, especialidades o posgrados</small></span>
                    </label>
                </div>
                <div class="col-12 SgceProgramasTextareaWrap">
                    <label class="SgceFieldLabel">Programas educativos iniciales opcionales</label>
                    <textarea class="form-control FormControl InputUpper SgceProgramasDependiente" name="ProgramasIniciales" rows="2" placeholder="Ejemplo: INFORMÁTICA, CONTABILIDAD, ENFERMERÍA" <?= $UsaProgramasConfig ? '' : 'disabled' ?>><?php $ProgramasVisiblesConfig = array_values(array_filter(array_column($ProgramasConfig, 'Nombre'), static fn($P) => $P !== 'GENERAL')); if (!empty($ProgramasVisiblesConfig)) { echo HConfig(implode(', ', $ProgramasVisiblesConfig)); } ?></textarea>
                    <small class="text-muted fw-semibold SgceProgramasHelp <?= $UsaProgramasConfig ? '' : 'SgceMuted' ?>">Activa “Usa programas educativos” para capturar programas. En primaria/secundaria puedes dejarlo desactivado.</small>
                </div>
            </div>
        </section>

        </div>

        <div class="SgceConfigRightCol">

        <section class="SgceConfigCard SgceConfigCardWide SgceConfigAcademicCard">
            <div class="SgceConfigHead">
                <span><span class="SgceColorIcon" aria-hidden="true">⚙️</span></span>
                <div><h2>Parámetros multinivel</h2><p>Turnos, escala de calificaciones y matrícula automática para adaptar SGCE a distintas instituciones.</p></div>
            </div>
            <div class="row g-3">
                <div class="col-12"><label class="SgceFieldLabel">Turnos disponibles</label><textarea class="form-control FormControl InputUpper" name="TurnosDisponibles" rows="3" placeholder="MATUTINO
VESPERTINO
NOCTURNO"><?= HConfig($TurnosDisponiblesConfigTexto) ?></textarea><small class="text-muted fw-semibold">Un turno por línea. Ejemplo: MATUTINO, VESPERTINO, NOCTURNO, SABATINO, EN LÍNEA.</small></div>
                <div class="col-md-4"><label class="SgceFieldLabel SgceFieldLabelMulti">Calificación mínima</label><input class="form-control FormControl" type="number" step="0.01" min="0" max="100" name="CalificacionMinima" value="<?= HConfig((string)$CalificacionConfigUi['Minima']) ?>"></div>
                <div class="col-md-4"><label class="SgceFieldLabel SgceFieldLabelMulti">Calificación aprobatoria</label><input class="form-control FormControl" type="number" step="0.01" min="0" max="100" name="CalificacionAprobatoria" value="<?= HConfig((string)$CalificacionConfigUi['Aprobatoria']) ?>"></div>
                <div class="col-md-4"><label class="SgceFieldLabel SgceFieldLabelMulti">Calificación máxima</label><input class="form-control FormControl" type="number" step="0.01" min="0" max="100" name="CalificacionMaxima" value="<?= HConfig((string)$CalificacionConfigUi['Maxima']) ?>"></div>
                <div class="col-md-6"><label class="SgcePrettyCheck"><input class="form-check-input" type="checkbox" name="CalificacionDecimales" value="1" <?= !empty($CalificacionConfigUi['Decimales']) ? 'checked' : '' ?>><span><strong>Permitir decimales</strong><small>Ejemplo: 8.5 o 92.75</small></span></label></div>
                <div class="col-md-6"><label class="SgcePrettyCheck"><input class="form-check-input" type="checkbox" name="MatriculaAutomatica" id="SgceConfigMatriculaAutomatica" value="1" <?= $MatriculaAutomaticaConfig ? 'checked' : '' ?>><span><strong>Matrícula automática</strong><small>Generar folio si no se captura manualmente</small></span></label></div>
                <div class="col-md-6"><label class="SgceFieldLabel">Prefijo de matrícula</label><input class="form-control FormControl InputUpperAscii SgceMatriculaDependiente" name="MatriculaPrefijo" value="<?= HConfig($MatriculaPrefijoConfig) ?>" maxlength="12" pattern="[A-Z0-9]{2,12}" placeholder="SGCE" data-sgce-matricula-campo="1"></div>
                <div class="col-md-6"><label class="SgceFieldLabel">Formato generado</label><input class="form-control FormControl SgceMatriculaDependiente" id="SgceConfigMatriculaEjemplo" value="<?= HConfig($MatriculaPrefijoConfig . '-' . date('Y') . '-000001') ?>" readonly data-sgce-matricula-campo="1"><small class="text-muted fw-semibold SgceMatriculaHelp">Solo se usa si Matrícula automática está activada.</small></div>
            </div>
        </section>

        <section class="SgceConfigCard SgceConfigCycleCard">
            <div class="SgceConfigHead">
                <span><span class="SgceColorIcon" aria-hidden="true">📅</span></span>
                <div><h2>Ciclo activo</h2><p>Rango usado para asistencias, reportes y estadísticas.</p></div>
            </div>
            <div class="row g-3">
                <div class="col-12"><label class="SgceFieldLabel">Nombre del ciclo</label><input class="form-control FormControl InputUpper" name="CicloNombre" value="<?= HConfig($CicloActivo['Nombre'] ?? '') ?>" maxlength="40" required></div>
                <div class="col-md-6"><label class="SgceFieldLabel">Fecha inicio</label><input class="form-control FormControl" type="date" name="FechaInicio" value="<?= HConfig($CicloActivo['FechaInicio'] ?? '') ?>" required></div>
                <div class="col-md-6"><label class="SgceFieldLabel">Fecha fin</label><input class="form-control FormControl" type="date" name="FechaFin" value="<?= HConfig($CicloActivo['FechaFin'] ?? '') ?>" required></div>
            </div>
        </section>

        <section class="SgceConfigCard SgceConfigPeriodsCard">
            <div class="SgceConfigHead">
                <span><span class="SgceColorIcon" aria-hidden="true">📋</span></span>
                <div><h2>Periodos y planeaciones</h2><p>Los periodos ya no son fijos. SGCE los crea según la oferta educativa y el ciclo activo.</p></div>
            </div>
            <div class="row g-3">
                <div class="col-md-6"><label class="SgceFieldLabel">Cantidad de periodos</label><input class="form-control FormControl InputDigits" name="PeriodosCantidad" value="<?= HConfig((string)$PeriodosCantidadConfig) ?>" required min="1" max="12" maxlength="2" inputmode="numeric"></div>
                <div class="col-md-6"><label class="SgceFieldLabel">Nombre base</label><input class="form-control FormControl InputUpper" name="PeriodosNombreBase" value="<?= HConfig($PeriodosNombreBaseConfig) ?>" maxlength="60" required placeholder="PARCIAL / TRIMESTRE / UNIDAD"></div>
                <div class="col-12"><label class="SgceFieldLabel">Modo de periodos</label><select class="form-select FormControl" name="PeriodosModo" id="SgceConfigPeriodosModo"><option value="AUTOMATICO" <?= $PeriodosModoConfig === 'AUTOMATICO' ? 'selected' : '' ?>>Automático</option><option value="PERSONALIZADO" <?= $PeriodosModoConfig === 'PERSONALIZADO' ? 'selected' : '' ?>>Personalizado</option></select></div>
                <div class="col-12"><label class="SgceFieldLabel">Periodos personalizados</label><textarea class="form-control FormControl InputUpper SgcePeriodosPersonalizadosDependiente" name="PeriodosPersonalizados" rows="3" placeholder="PARCIAL 1, PARCIAL 2, ORDINARIO" <?= $PeriodosModoConfig === 'PERSONALIZADO' ? '' : 'disabled' ?>><?= HConfig($PeriodosModoConfig === 'PERSONALIZADO' ? $PeriodosPersonalizadosConfig : '') ?></textarea><small class="text-muted fw-semibold SgcePeriodosPersonalizadosHelp <?= $PeriodosModoConfig === 'PERSONALIZADO' ? '' : 'SgceMuted' ?>">Solo se captura cuando el modo de periodos está en personalizado.</small></div>
                <div class="col-12"><label class="SgcePrettyCheck"><input class="form-check-input" type="checkbox" name="UsaPlaneaciones" value="1" <?= $UsaPlaneacionesConfig ? 'checked' : '' ?>><span><strong>La institución usa planeaciones</strong><small>Control de entregas por materia</small></span></label></div>
                <div class="col-md-6"><label class="SgceFieldLabel">Tipo de planeación</label><select class="form-select FormControl SgcePlaneacionesDependiente" name="TipoPlaneacion" <?= $UsaPlaneacionesConfig ? '' : 'disabled' ?>><option value="CICLO" <?= $TipoPlaneacionConfig === 'CICLO' ? 'selected' : '' ?>>Por ciclo</option><option value="PERIODO" <?= $TipoPlaneacionConfig === 'PERIODO' ? 'selected' : '' ?>>Por periodo de evaluación</option><option value="UNIDAD" <?= $TipoPlaneacionConfig === 'UNIDAD' ? 'selected' : '' ?>>Por unidad/tema</option><option value="SEMANA" <?= $TipoPlaneacionConfig === 'SEMANA' ? 'selected' : '' ?>>Semanal</option></select></div>
                <div class="col-md-6"><label class="SgceFieldLabel">Planeaciones a entregar</label><input class="form-control FormControl InputDigits SgcePlaneacionesDependiente" name="PlaneacionesCantidad" value="<?= HConfig((string)$PlaneacionesCantidad) ?>" <?= $UsaPlaneacionesConfig ? 'required' : 'disabled' ?> min="1" max="12" maxlength="2" inputmode="numeric"><small class="text-muted fw-semibold SgcePlaneacionesHelp <?= $UsaPlaneacionesConfig ? '' : 'SgceMuted' ?>">Se solicitará la cantidad configurada de planeaciones por materia durante el ciclo escolar.</small></div>
            </div>
        </section>
        </div>
        </div>

        <section class="SgceConfigActions SgceConfigActionsInline SgceConfigActionsFull">
            <div>
                <strong><i class="fa-solid fa-circle-info"></i> Cambios globales</strong>
                <span>Al guardar se actualizan reportes, boletas, consulta pública y paneles.</span>
            </div>
            <button type="submit" id="BtnGuardarConfiguracionVerdeMetalico" class="SgceConfigSave BtnConfiguracionGuardarMetalico"><span class="SgceColorIcon" aria-hidden="true">💾</span> Guardar configuración</button>
        </section>
    </form>


</div>
<?= SgceLayoutSharedJs(['assets/js/ConfiguracionAdmin.js']) ?>
</body>
</html>
