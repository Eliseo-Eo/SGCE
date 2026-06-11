<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceConfiguracionAdminPreparar(PDO $Pdo, array $UserSession): array {
    function HConfig($Texto) { return htmlspecialchars((string)$Texto, ENT_QUOTES, 'UTF-8'); }
    function ConfigMayusculas($Texto) {
        $Texto = (string)$Texto;
        if (function_exists('mb_strtoupper')) { return mb_strtoupper($Texto, 'UTF-8'); }
        $Texto = strtr($Texto, [
            'á'=>'Á','é'=>'É','í'=>'Í','ó'=>'Ó','ú'=>'Ú','ü'=>'Ü','ñ'=>'Ñ',
            'à'=>'À','è'=>'È','ì'=>'Ì','ò'=>'Ò','ù'=>'Ù','ä'=>'Ä','ë'=>'Ë','ï'=>'Ï','ö'=>'Ö'
        ]);
        return strtoupper($Texto);
    }

    function ConfigLongitud($Texto) {
        $Texto = (string)$Texto;
        return function_exists('mb_strlen') ? mb_strlen($Texto, 'UTF-8') : strlen($Texto);
    }

    function ConfigNormalizar($Texto, $Mayusculas = true) {
        $Texto = trim(preg_replace('/\s+/u', ' ', (string)$Texto));
        return $Mayusculas ? ConfigMayusculas($Texto) : $Texto;
    }
    function ConfigFechaValida($Fecha) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$Fecha)) { return false; }
        $D = DateTime::createFromFormat('Y-m-d', (string)$Fecha);
        return $D && $D->format('Y-m-d') === $Fecha;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $NombreEscuela = ConfigNormalizar($_POST['NombreEscuela'] ?? '', true);
            $ClaveCentroTrabajo = ConfigNormalizar($_POST['ClaveCentroTrabajo'] ?? '', true);
            $DirectorNombre = ConfigNormalizar($_POST['DirectorNombre'] ?? '', true);
            $MunicipioEstado = ConfigNormalizar($_POST['MunicipioEstado'] ?? '', true);
            $TelefonoEscuela = ConfigNormalizar($_POST['TelefonoEscuela'] ?? '', false);
            $CorreoEscuela = ConfigNormalizar($_POST['CorreoEscuela'] ?? '', false);
            $LemaInstitucional = ConfigNormalizar($_POST['LemaInstitucional'] ?? '', false);
            $ColorInstitucional = SgceNormalizarColorHex($_POST['ColorInstitucional'] ?? '#97051E');
            $TurnosDisponiblesTexto = SgceTurnosTextoSeguro((string)($_POST['TurnosDisponibles'] ?? 'MATUTINO\nVESPERTINO'));
            $CalificacionMinima = max(0, min(100, (float)($_POST['CalificacionMinima'] ?? 5)));
            $CalificacionMaxima = max(0, min(100, (float)($_POST['CalificacionMaxima'] ?? 10)));
            $CalificacionAprobatoria = max($CalificacionMinima, min($CalificacionMaxima, (float)($_POST['CalificacionAprobatoria'] ?? 6)));
            $CalificacionDecimales = !empty($_POST['CalificacionDecimales']) ? '1' : '0';
            $MatriculaAutomatica = !empty($_POST['MatriculaAutomatica']) ? '1' : '0';
            $MatriculaPrefijo = SgceNormalizarMayusculas((string)($_POST['MatriculaPrefijo'] ?? 'SGCE'));
            if ($MatriculaAutomatica === '1' && !preg_match('/^[A-Z0-9]{2,12}$/', $MatriculaPrefijo)) { $MatriculaPrefijo = 'SGCE'; }
            if ($MatriculaAutomatica !== '1') { $MatriculaPrefijo = 'SGCE'; }
            if ($CalificacionMinima >= $CalificacionMaxima) { throw new Exception('La escala de calificaciones no es válida. La mínima debe ser menor que la máxima.'); }

            $CicloNombre = ConfigNormalizar($_POST['CicloNombre'] ?? '', true);
            $FechaInicio = trim((string)($_POST['FechaInicio'] ?? ''));
            $FechaFin = trim((string)($_POST['FechaFin'] ?? ''));
            $PeriodosCantidad = max(1, min(12, (int)($_POST['PeriodosCantidad'] ?? 3)));
            $PeriodosNombreBase = SgceNombreBasePeriodoValido((string)($_POST['PeriodosNombreBase'] ?? 'PARCIAL'));
            $PeriodosModo = SgceModoPeriodosValido((string)($_POST['PeriodosModo'] ?? 'AUTOMATICO'));
            $PeriodosPersonalizados = ConfigNormalizar($_POST['PeriodosPersonalizados'] ?? '', true);
            $PeriodosFinales = SgceGenerarNombresPeriodos($PeriodosCantidad, $PeriodosNombreBase, $PeriodosModo, $PeriodosPersonalizados);
            $UsaPlaneaciones = !empty($_POST['UsaPlaneaciones']);
            $TipoPlaneacion = $UsaPlaneaciones ? SgceTipoPlaneacionValido((string)($_POST['TipoPlaneacion'] ?? 'CICLO')) : 'CICLO';
            $PlaneacionesCantidad = $UsaPlaneaciones ? max(1, min(12, (int)($_POST['PlaneacionesCantidad'] ?? 0))) : 0;
            $NivelEducativo = SgceNivelEducativoValido((string)($_POST['NivelEducativo'] ?? 'SECUNDARIA'));
            $TipoPeriodizacion = SgceTipoPeriodizacionValido((string)($_POST['TipoPeriodizacion'] ?? 'ANUAL'));
            $TotalEtapas = max(1, min(20, (int)($_POST['TotalEtapas'] ?? 3)));
            $UsaProgramasManual = !empty($_POST['UsaProgramas']);
            $RequiereProgramasPorNivel = SgceRequiereProgramasEducativosPorDefecto($NivelEducativo, $TipoPeriodizacion);
            $UsaProgramas = $UsaProgramasManual || $RequiereProgramasPorNivel;
            $ProgramasIniciales = ConfigNormalizar($_POST['ProgramasIniciales'] ?? '', true);
            $NombreOfertaAcademica = ConfigNormalizar($_POST['NombreOfertaAcademica'] ?? ($NivelEducativo . ' ' . $TipoPeriodizacion), true);
            $ProgramasCapturados = array_values(array_filter(array_map(static function($P) { return ConfigNormalizar($P, true); }, preg_split('/[,;\n]+/u', $ProgramasIniciales))));
            $ProgramasRealesCapturados = array_values(array_filter($ProgramasCapturados, static fn($P) => $P !== 'GENERAL'));

            // Si ya existen programas reales registrados, no se obliga a volver a escribirlos en cada guardado.
            // Así, activar o desactivar planeaciones no falla por una validación que pertenece a otra sección.
            $OfertaActualValidar = SgceOfertaActiva($Pdo);
            $ProgramasRealesExistentes = [];
            if (!empty($OfertaActualValidar['Id'])) {
                foreach (SgceProgramasEducativosListar($Pdo, true, (int)$OfertaActualValidar['Id']) as $ProgramaExistente) {
                    $NombreProgramaExistente = ConfigNormalizar($ProgramaExistente['Nombre'] ?? '', true);
                    if ($NombreProgramaExistente !== '' && $NombreProgramaExistente !== 'GENERAL') { $ProgramasRealesExistentes[] = $NombreProgramaExistente; }
                }
            }
            $ProgramasRealesDisponibles = array_values(array_unique(array_merge($ProgramasRealesExistentes, $ProgramasRealesCapturados)));
            if (($UsaProgramasManual || $RequiereProgramasPorNivel) && count($ProgramasRealesDisponibles) === 0) {
                throw new Exception('Captura al menos un programa educativo real o desmarca la opción Usa programas educativos si la institución no los maneja.');
            }

            if ($NombreEscuela === '' || ConfigLongitud($NombreEscuela) < 3) { throw new Exception('Escribe el nombre oficial de la escuela.'); }
            if ($ClaveCentroTrabajo !== '' && !preg_match('/^[A-Z0-9-]{3,30}$/', $ClaveCentroTrabajo)) { throw new Exception('La CCT / clave solo debe usar letras, números o guion.'); }
            if ($DirectorNombre !== '' && !preg_match('/^[A-ZÁÉÍÓÚÜÑ .\'-]{3,120}$/u', $DirectorNombre)) { throw new Exception('El nombre del director solo debe contener letras y espacios.'); }
            if ($TelefonoEscuela !== '' && !preg_match('/^\d{7,15}$/', $TelefonoEscuela)) { throw new Exception('El teléfono debe contener solo números, mínimo 7 y máximo 15 dígitos.'); }
            if ($CorreoEscuela !== '' && !filter_var($CorreoEscuela, FILTER_VALIDATE_EMAIL)) { throw new Exception('El correo institucional no tiene formato válido.'); }
            if (!preg_match('/^#[0-9A-F]{6}$/', $ColorInstitucional)) { throw new Exception('El color institucional no tiene formato válido.'); }
            if ($CicloNombre === '' || ConfigLongitud($CicloNombre) > 40 || !ConfigFechaValida($FechaInicio) || !ConfigFechaValida($FechaFin) || strtotime($FechaInicio) >= strtotime($FechaFin)) {
                throw new Exception('Revisa el ciclo escolar. Las fechas no son válidas y el nombre del ciclo no debe superar 40 caracteres.');
            }
            if (count($PeriodosFinales) !== $PeriodosCantidad || count(array_unique($PeriodosFinales)) !== count($PeriodosFinales)) { throw new Exception('Revisa los periodos de evaluación. Deben existir y no repetirse.'); }
            foreach ($PeriodosFinales as $NombrePeriodoValidar) {
                if (ConfigLongitud($NombrePeriodoValidar) > 80) { throw new Exception('El nombre de cada periodo no debe superar 80 caracteres.'); }
            }
            if ($UsaPlaneaciones && ($PlaneacionesCantidad < 1 || $PlaneacionesCantidad > 12)) { throw new Exception('La cantidad de planeaciones debe estar entre 1 y 12.'); }
            if ($UsaPlaneaciones && $TipoPlaneacion === 'PERIODO' && $PlaneacionesCantidad > $PeriodosCantidad) { throw new Exception('Cuando el tipo es Por periodo, la cantidad de planeaciones no puede ser mayor que la cantidad de periodos de evaluación.'); }
            if ($TotalEtapas < 1 || $TotalEtapas > 20) { throw new Exception('La cantidad de etapas académicas debe estar entre 1 y 20.'); }
            if ($NombreOfertaAcademica === '' || ConfigLongitud($NombreOfertaAcademica) > 140) { throw new Exception('Escribe un nombre válido para la oferta educativa.'); }
            // Aunque la oferta no use programas visibles, SGCE siempre crea y usa un programa interno GENERAL.
            if ($OfertaActualValidar) {
                $StmtGruposOferta = $Pdo->prepare('SELECT COUNT(*) FROM Grupos WHERE OfertaId = ?');
                $StmtGruposOferta->execute([(int)$OfertaActualValidar['Id']]);
                $TieneGruposOferta = (int)$StmtGruposOferta->fetchColumn() > 0;
                $CambioNombreOferta = (string)$OfertaActualValidar['Nombre'] !== $NombreOfertaAcademica;
                $CambioEstructura = (string)$OfertaActualValidar['NivelEducativo'] !== $NivelEducativo
                    || (string)$OfertaActualValidar['TipoPeriodizacion'] !== $TipoPeriodizacion
                    || (int)$OfertaActualValidar['TotalEtapas'] !== $TotalEtapas
                    || (int)$OfertaActualValidar['UsaProgramas'] !== ($UsaProgramas ? 1 : 0);
                if ($TieneGruposOferta && $CambioNombreOferta) {
                    throw new Exception('La oferta educativa ya tiene grupos vinculados. Por seguridad no se puede cambiar su nombre después de crear grupos; así se evita ocultar alumnos, boletas o historial.');
                }
                if ($TieneGruposOferta && $CambioEstructura) {
                    throw new Exception('La estructura académica ya tiene grupos vinculados. Por seguridad no se puede cambiar nivel, periodización, etapas o uso de programas después de crear grupos. Puedes agregar programas nuevos en el campo de programas iniciales.');
                }
                $CicloActivoValidar = SgceCicloActivo($Pdo);
                $CicloValidarId = (int)($CicloActivoValidar['Id'] ?? 0);
                $ConfigAcademicaActual = SgceConfiguracionAcademicaPorOferta($Pdo, (int)$OfertaActualValidar['Id']);
                $PeriodosActuales = [
                    (int)($ConfigAcademicaActual['CantidadPeriodosEvaluacion'] ?? 0),
                    (string)($ConfigAcademicaActual['NombreBasePeriodo'] ?? ''),
                    (string)($ConfigAcademicaActual['ModoPeriodos'] ?? ''),
                    trim((string)($ConfigAcademicaActual['PeriodosPersonalizados'] ?? '')),
                ];
                $PeriodosNuevos = [$PeriodosCantidad, $PeriodosNombreBase, $PeriodosModo, trim(implode(PHP_EOL, $PeriodosFinales))];
                if ($CicloValidarId > 0 && $PeriodosActuales !== $PeriodosNuevos && SgceCicloOfertaTieneCalificaciones($Pdo, $CicloValidarId, (int)$OfertaActualValidar['Id'])) {
                    throw new Exception('No se pueden cambiar los periodos de evaluación porque ya existen calificaciones capturadas en el ciclo activo y oferta educativa. Esta estructura debe definirse antes de capturar calificaciones.');
                }
                $PlaneacionActual = [
                    (int)($ConfigAcademicaActual['UsaPlaneaciones'] ?? 1),
                    (string)($ConfigAcademicaActual['TipoPlaneacion'] ?? 'CICLO'),
                    (int)($ConfigAcademicaActual['PlaneacionesCantidad'] ?? 1),
                ];
                $PlaneacionNueva = [$UsaPlaneaciones ? 1 : 0, $TipoPlaneacion, $PlaneacionesCantidad];
                if ($CicloValidarId > 0 && $PlaneacionActual !== $PlaneacionNueva && SgceCicloOfertaTienePlaneaciones($Pdo, $CicloValidarId, (int)$OfertaActualValidar['Id'])) {
                    throw new Exception('No se puede cambiar la estructura de planeaciones porque ya existen planeaciones cargadas en el ciclo activo y oferta educativa. Esta estructura debe definirse antes de recibir archivos.');
                }
            }

            $Pdo->beginTransaction();
            SgceGuardarConfiguracion($Pdo, [
                'NombreEscuela' => $NombreEscuela,
                'ClaveCentroTrabajo' => $ClaveCentroTrabajo,
                'DirectorNombre' => $DirectorNombre,
                'MunicipioEstado' => $MunicipioEstado,
                'TelefonoEscuela' => $TelefonoEscuela,
                'CorreoEscuela' => $CorreoEscuela,
                'LemaInstitucional' => $LemaInstitucional,
                'ColorInstitucional' => $ColorInstitucional,
                'SistemaNombre' => 'SGCE',
                'PeriodosCantidad' => (string)$PeriodosCantidad,
                'PeriodosNombreBase' => $PeriodosNombreBase,
                'PeriodosModo' => $PeriodosModo,
                'PeriodosPersonalizados' => implode(PHP_EOL, $PeriodosFinales),
                'UsaPlaneaciones' => $UsaPlaneaciones ? '1' : '0',
                'TipoPlaneacion' => $TipoPlaneacion,
                'PlaneacionesCantidad' => (string)$PlaneacionesCantidad,
                'NivelEducativo' => $NivelEducativo,
                'TipoPeriodizacion' => $TipoPeriodizacion,
                'TotalEtapas' => (string)$TotalEtapas,
                'UsaProgramas' => $UsaProgramas ? '1' : '0',
                'NombreOfertaAcademica' => $NombreOfertaAcademica,
                'TurnosDisponibles' => $TurnosDisponiblesTexto,
                'CalificacionMinima' => (string)$CalificacionMinima,
                'CalificacionMaxima' => (string)$CalificacionMaxima,
                'CalificacionAprobatoria' => (string)$CalificacionAprobatoria,
                'CalificacionDecimales' => $CalificacionDecimales,
                'MatriculaAutomatica' => $MatriculaAutomatica,
                'MatriculaPrefijo' => $MatriculaPrefijo,
            ]);
            $OfertaIdConfigurada = SgceConfigurarEstructuraAcademicaInicial($Pdo, $NivelEducativo, $TipoPeriodizacion, $TotalEtapas, $UsaProgramas, implode(PHP_EOL, $ProgramasRealesCapturados), $NombreOfertaAcademica, SgceEtiquetaEtapaPorTipo($TipoPeriodizacion), $PeriodosCantidad, $PeriodosNombreBase, $PeriodosModo, implode(PHP_EOL, $PeriodosFinales), $UsaPlaneaciones, $TipoPlaneacion, $PlaneacionesCantidad);

            $CicloActivo = SgceCicloActivo($Pdo);
            $CicloId = (int)($CicloActivo['Id'] ?? 0);
            $NombreActivoActual = ConfigNormalizar($CicloActivo['Nombre'] ?? '', true);
            $EsCambioDeCiclo = ($CicloId <= 0 || $NombreActivoActual !== $CicloNombre);

            if ($EsCambioDeCiclo) {
                $StmtExisteCiclo = $Pdo->prepare('SELECT Id FROM CiclosEscolares WHERE Nombre = ? LIMIT 1');
                $StmtExisteCiclo->execute([$CicloNombre]);
                $CicloExistenteId = (int)$StmtExisteCiclo->fetchColumn();
                if ($CicloExistenteId > 0) {
                    $StmtCiclo = $Pdo->prepare('UPDATE CiclosEscolares SET FechaInicio = ?, FechaFin = ? WHERE Id = ?');
                    $StmtCiclo->execute([$FechaInicio, $FechaFin, $CicloExistenteId]);
                    $CicloId = $CicloExistenteId;
                    SgceActivarCicloUnico($Pdo, $CicloId);
                } else {
                    $StmtCiclo = $Pdo->prepare('INSERT INTO CiclosEscolares (Nombre, FechaInicio, FechaFin, Activo) VALUES (?, ?, ?, 0)');
                    $StmtCiclo->execute([$CicloNombre, $FechaInicio, $FechaFin]);
                    $CicloId = (int)$Pdo->lastInsertId();
                    SgceActivarCicloUnico($Pdo, $CicloId);
                }
            } else {
                $StmtCiclo = $Pdo->prepare('UPDATE CiclosEscolares SET FechaInicio = ?, FechaFin = ? WHERE Id = ?');
                $StmtCiclo->execute([$FechaInicio, $FechaFin, $CicloId]);
                SgceActivarCicloUnico($Pdo, $CicloId);
            }

            SgceSincronizarPeriodosCicloOferta($Pdo, $CicloId, (int)($OfertaIdConfigurada ?? 0), $PeriodosFinales);

            RegistrarBitacora($Pdo, $UserSession, 'ACTUALIZAR_CONFIGURACION', 'ConfiguracionSistema', null, 'CONFIGURACIÓN INSTITUCIONAL ACTUALIZADA');
            $Pdo->commit();
            $_SESSION['MensajeConfiguracion'] = 'Configuración guardada correctamente.';
            $_SESSION['MensajeConfiguracionTipo'] = 'success';
        } catch (Exception $E) {
            if ($Pdo->inTransaction()) { $Pdo->rollBack(); }
            $CodigoError = SgceRegistrarErrorTecnico('CONFIGURACION_ADMIN', $E);
            $_SESSION['MensajeConfiguracion'] = 'No se pudo guardar la configuración. Código de seguimiento: ' . $CodigoError;
            $_SESSION['MensajeConfiguracionTipo'] = 'danger';
        }
        header('Location: ConfiguracionAdmin.php');
        exit;
    }

    $Config = SgceObtenerConfiguracion($Pdo);
    $OfertaActivaConfig = SgceOfertaActiva($Pdo);
    $EtiquetaEtapaUi = SgceEtiquetaEtapaActual($Pdo, (int)($OfertaActivaConfig['Id'] ?? 0));
    $EtiquetaEtapaUiMinus = function_exists('mb_strtolower') ? mb_strtolower($EtiquetaEtapaUi, 'UTF-8') : strtolower($EtiquetaEtapaUi);
    $NivelEducativoConfig = SgceNivelEducativoValido($Config['NivelEducativo'] ?? ($OfertaActivaConfig['NivelEducativo'] ?? 'SECUNDARIA'));
    $TipoPeriodizacionConfig = SgceTipoPeriodizacionValido($Config['TipoPeriodizacion'] ?? ($OfertaActivaConfig['TipoPeriodizacion'] ?? 'ANUAL'));
    $TotalEtapasConfig = (int)($Config['TotalEtapas'] ?? ($OfertaActivaConfig['TotalEtapas'] ?? 3));
    $UsaProgramasConfig = !empty($Config['UsaProgramas']) || !empty($OfertaActivaConfig['UsaProgramas']);
    $ConfigAcademica = SgceConfiguracionAcademicaPorOferta($Pdo, (int)($OfertaActivaConfig['Id'] ?? 0));
    $ProgramasConfig = SgceProgramasEducativosListar($Pdo, true, (int)($OfertaActivaConfig['Id'] ?? 0));
    $EtapasConfig = !empty($OfertaActivaConfig['Id']) ? SgceEtapasAcademicasListar($Pdo, (int)$OfertaActivaConfig['Id'], true) : [];
    $CicloActivo = SgceCicloActivo($Pdo);
    $Periodos = [];
    if (!empty($CicloActivo['Id']) && !empty($OfertaActivaConfig['Id'])) {
        $StmtPeriodos = $Pdo->prepare('SELECT Orden, Nombre FROM PeriodosEvaluacion WHERE CicloId = ? AND OfertaId = ? AND Activo = 1 ORDER BY Orden ASC');
        $StmtPeriodos->execute([(int)$CicloActivo['Id'], (int)$OfertaActivaConfig['Id']]);
        foreach ($StmtPeriodos->fetchAll() as $P) { $Periodos[(int)$P['Orden']] = $P['Nombre']; }
    }
    $PeriodosCantidadConfig = (int)($ConfigAcademica['CantidadPeriodosEvaluacion'] ?? ($Config['PeriodosCantidad'] ?? max(3, count($Periodos))));
    $PeriodosNombreBaseConfig = (string)($ConfigAcademica['NombreBasePeriodo'] ?? ($Config['PeriodosNombreBase'] ?? 'PARCIAL'));
    $PeriodosModoConfig = (string)($ConfigAcademica['ModoPeriodos'] ?? ($Config['PeriodosModo'] ?? 'AUTOMATICO'));
    $PeriodosPersonalizadosConfig = trim(implode(PHP_EOL, array_values($Periodos))) !== '' ? implode(PHP_EOL, array_values($Periodos)) : (string)($ConfigAcademica['PeriodosPersonalizados'] ?? '');
    $UsaPlaneacionesConfig = (int)($ConfigAcademica['UsaPlaneaciones'] ?? ($Config['UsaPlaneaciones'] ?? 1)) === 1;
    $TipoPlaneacionConfig = (string)($ConfigAcademica['TipoPlaneacion'] ?? ($Config['TipoPlaneacion'] ?? 'CICLO'));
    $PlaneacionesCantidad = SgceCantidadPlaneaciones($Pdo);
    $TurnosDisponiblesConfigTexto = (string)($Config['TurnosDisponibles'] ?? "MATUTINO\nVESPERTINO");
    $CalificacionConfigUi = SgceCalificacionConfig($Pdo);
    $MatriculaAutomaticaConfig = (string)($Config['MatriculaAutomatica'] ?? '1') === '1';
    $MatriculaPrefijoConfig = SgceMatriculaPrefijo($Pdo);
    $Mensaje = $_SESSION['MensajeConfiguracion'] ?? '';
    $MensajeTipo = $_SESSION['MensajeConfiguracionTipo'] ?? 'success';
    unset($_SESSION['MensajeConfiguracion'], $_SESSION['MensajeConfiguracionTipo']);

    $DatosConfiguracion = get_defined_vars();
    unset($DatosConfiguracion['Pdo'], $DatosConfiguracion['UserSession']);
    return $DatosConfiguracion;
}
