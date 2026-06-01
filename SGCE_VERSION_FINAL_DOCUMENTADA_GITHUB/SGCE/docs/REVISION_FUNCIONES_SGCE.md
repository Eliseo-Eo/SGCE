# Revisión de funciones y estructura - SGCE

## 1. Resumen de auditoría estática

- Archivos totales: 138
- PHP: 73
- CSS: 14
- JS: 12
- Funciones PHP detectadas: 282
- Clases PHP detectadas: 2
- PHP lint: validado sin errores de sintaxis en archivos PHP.
- Duplicados de funciones: no se detectaron nombres de función duplicados.
- Favicon e imágenes: concentrados en `assets/media/img/`, sin favicon duplicado en raíz.
- Wrappers públicos: conservados para mantener URLs simples y proteger módulos internos.
- Documentación: README y manuales regenerados desde cero para GitHub.

## 2. Mapa de módulos

### Entrada pública / wrappers

- `Admin.php`
- `Maestro.php`
- `Asistencia.php`
- `Calificar.php`
- `ConsultaPadre.php`
- `ConsultaCalificaciones.php`
- `ReportesAdmin.php`
- `Instalar.php`

### Configuración

- `config/Conexion.php`
- `config/database.php`
- `config/database.local.example.php`

### Includes

- `includes/SGCE_ErrorHandler.php`
- `includes/SGCE_Helpers.php`
- `includes/SGCE_Pdf.php`
- `includes/SGCE_PublicConsultas.php`

### Servicios

- `services/AlumnoService.php`
- `services/AsistenciaService.php`
- `services/CalificacionService.php`
- `services/GrupoService.php`
- `services/MaestroService.php`
- `services/ReporteService.php`
- `services/SGCE_ServiceLoader.php`
- `services/UsuarioService.php`

### Módulos

- `modules/Admin.php`
- `modules/Asistencia.php`
- `modules/AvisosAdmin.php`
- `modules/Calificar.php`
- `modules/ConfiguracionAdmin.php`
- `modules/DescargarPlaneacion.php`
- `modules/HistorialAlumno.php`
- `modules/Importar.php`
- `modules/Maestro.php`
- `modules/PeriodosAdmin.php`
- `modules/Planeaciones.php`
- `modules/PlaneacionesAdmin.php`
- `modules/RestaurarBD.php`
- `modules/UsuariosAdmin.php`

### Módulo admin interno

- `modules/admin/AdminAcciones.php`
- `modules/admin/AdminDatos.php`
- `modules/admin/AdminVista.php`

### Público

- `public/ConsultaCalificaciones.php`
- `public/ConsultaPadre.php`
- `public/Logout.php`
- `public/index.php`

### Reportes

- `reports/ExportarAlumno.php`
- `reports/ExportarAsistencia.php`
- `reports/ExportarBoletaPublica.php`
- `reports/ExportarCalificaciones.php`
- `reports/ExportarConsultaAsistencia.php`
- `reports/ExportarDatosBD.php`
- `reports/ReportesAdmin.php`
- `reports/RespaldoBD.php`

## 3. Inventario de clases y funciones PHP

### `Instalar.php`

Clases:
- `InstalarMensajeUsuario`

Funciones:
- `HInst()`
- `InstalarCsrfToken()`
- `InstalarCampoCsrf()`
- `InstalarValidarCsrf()`
- `InstalarModoDebug()`
- `InstalarSepararSql()`
- `InstalarValidarPassword()`
- `InstalarMayusculas()`
- `InstalarLongitud()`
- `InstalarNombreBaseValido()`
- `InstalarDsnServidorMysql()`
- `InstalarDsnBaseMysql()`
- `InstalarCrearConexionMysql()`
- `InstalarBaseDatosExiste()`
- `InstalarCrearBaseDatos()`
- `InstalarNormalizarTexto()`
- `InstalarValidarFecha()`
- `InstalarSoloLetrasEspacios()`
- `InstalarValidarTelefonoOpcional()`
- `InstalarValidarCorreoOpcional()`
- `InstalarValidarTextoOpcional()`
- `InstalarFormatoPermisos()`
- `InstalarUsuarioPhp()`
- `InstalarVerificarEscritura()`
- `InstalarEscribirArchivoSeguro()`
- `InstalarEliminarDirectorio()`
- `InstalarGuardarConfiguracion()`
- `InstalarContenidoHtaccessDenegacion()`
- `InstalarAsegurarCarpetaProtegida()`
- `InstalarAsegurarProteccionesIniciales()`
- `InstalarLogDir()`
- `InstalarRegistrarError()`
- `InstalarAddCheck()`
- `InstalarVerificacionesServidor()`
- `InstalarChecksCriticosOk()`

### `includes/SGCE_ErrorHandler.php`

Funciones:
- `SgceLogDir()`
- `SgcePrepararLogDir()`
- `SgceRegistrarErrorTecnico()`
- `SgceMostrarErrorCliente()`

### `includes/SGCE_Helpers.php`

Funciones:
- `IniciarSesionSegura()`
- `EsHttps()`
- `EnviarHeadersSeguridad()`
- `SgceContenidoHtaccessDenegacion()`
- `SgceAsegurarCarpetaProtegida()`
- `SgcePrepararDirectoriosSeguros()`
- `SgceEnviarHeadersNoCacheDescarga()`
- `SgceCerrarSesionPhpCompleta()`
- `HGlobal()`
- `SgceSalirConError()`
- `ObtenerCsrfToken()`
- `ValidarCsrfToken()`
- `RequerirCsrfPost()`
- `CampoCsrf()`
- `ImprimirCsrfScript()`
- `ObtenerIpCliente()`
- `SgcePasswordHash()`
- `SgceValidarPasswordFuerte()`
- `SgcePasswordVerify()`
- `SgcePasswordNecesitaRehash()`
- `SgceCadenaMayusculas()`
- `SgceLongitudTexto()`
- `SgceNormalizarMayusculas()`
- `SgceNormalizarNombre()`
- `SgceNormalizarGrupo()`
- `SgceValidarGrado()`
- `SgceNormalizarTurno()`
- `SgceNormalizarTextoUsuarios()`
- `SgceCrearTablaConfiguracionSiNoExiste()`
- `SgceConfiguracionDefault()`
- `SgceObtenerConfiguracion()`
- `SgceGuardarConfiguracion()`
- `SgceNormalizarColorHex()`
- `SgceColorAjustar()`
- `SgceColorRgb()`
- `SgceColorInstitucional()`
- `SgceEstilosTema()`
- `SgceNombreEscuela()`
- `SgceRolesSistema()`
- `SgceNormalizarRolSistema()`
- `SgceValidarRolUsuario()`
- `SgceRolSesion()`
- `SgceTieneRol()`
- `SgcePermisosPorRol()`
- `SgceTienePermiso()`
- `SgceDenegarAcceso()`
- `SgceExigirPermiso()`
- `SgceExigirRol()`
- `SgcePuedeGestionarUsuarios()`
- `SgcePuedeGestionarCatalogos()`
- `SgcePuedeGestionarAvisos()`
- `SgcePuedeAdministrarReportes()`
- `SgcePuedeAdministrarPeriodos()`
- `SgcePuedeRespaldos()`
- `SgcePuedeBitacora()`
- `SgcePuedeImportarCatalogos()`
- `SgcePuedeConfigurarSistema()`
- `SgcePuedeGestionarPlaneaciones()`
- `SgcePuedeCorregirAsistenciaHistorica()`
- `SgcePuedePanelAdmin()`
- `SgceUrlInicioPorRol()`
- `SgceTabsAdminPermitidas()`
- `SgceTabAdminPermitida()`
- `SgceRedirectAdminTab()`
- `SgcePaginaActual()`
- `SgceLimitOffset()`
- `SgceRenderPager()`
- `SgceContarAdminsActivos()`
- `SgcePeriodoActualId()`
- `SgceCicloActivoId()`
- `SgceCicloActivo()`
- `SgcePeriodoInfo()`
- `SgceValidarParcial()`
- `SgcePeriodosDisponibles()`
- `VerificarSesionCookie()`
- `CrearTablaRateLimitSiNoExiste()`
- `RateLimitClave()`
- `RateLimitDisponible()`
- `RateLimitRegistrarFallo()`
- `RateLimitLimpiar()`
- `CrearTablaBitacoraSiNoExiste()`
- `RegistrarBitacora()`
- `SgceCantidadPlaneaciones()`
- `SgceCrearTablaPlaneacionesSiNoExiste()`
- `SgceNormalizarMateriaPlaneacion()`
- `SgceCarpetaPlaneaciones()`
- `SgcePrepararCarpetaDocentePlaneaciones()`
- `SgceNombreArchivoSeguro()`
- `SgceEstadosPlaneacion()`
- `SgceExtensionesPlaneacionPermitidas()`
- `SgceMimePlaneacionPermitido()`
- `SgceArchivoPdfValido()`
- `SgceArchivoOfficeBinarioValido()`
- `SgceArchivoOoxmlValido()`
- `SgceArchivoPlaneacionFirmaValida()`
- `SgceValidarArchivoPlaneacion()`
- `SgceNombrePlaneacionEstandar()`
- `SgceNombrePlaneacionInterno()`
- `SgceMateriasDocente()`
- `SgceColumnasInsertablesBackup()`
- `SgceCrearRespaldoSql()`
- `SgceGenerarBackupAutomatico()`
- `SgceFirmaRespaldoValida()`
- `SgceRutaRaiz()`

### `includes/SGCE_Pdf.php`

Clases:
- `SgcePdfSimple`

Funciones:
- `__construct()`
- `AddPage()`
- `Width()`
- `Height()`
- `Margin()`
- `Y()`
- `SetY()`
- `AddY()`
- `Op()`
- `PdfY()`
- `Enc()`
- `SetTextColorHex()`
- `SetDrawColorHex()`
- `HexToRgb()`
- `Color()`
- `Text()`
- `Line()`
- `Rect()`
- `ULen()`
- `USubstr()`
- `WrapText()`
- `MultiText()`
- `HeaderBlock()`
- `Table()`
- `AddFooter()`
- `Fmt()`
- `Output()`
- `SgcePdfArchivoSeguro()`
- `SgcePdfRespuestaTabla()`

### `includes/SGCE_PublicConsultas.php`

Funciones:
- `SgcePublicoUrlRaizProyecto()`
- `SgcePublicoNormalizarGrupo()`
- `SgcePublicoTextoEstado()`
- `SgcePublicoClaseEstado()`
- `SgcePublicoIconoEstado()`
- `SgcePublicoEmojiEstado()`
- `SgcePublicoCatalogos()`
- `SgcePublicoRateKey()`
- `SgcePublicoEnviarHeaders()`
- `SgcePublicoMensajeNoEncontrado()`
- `SgcePublicoHoneypotActivado()`
- `SgcePublicoRateDisponible()`
- `SgcePublicoRegistrarFallo()`
- `SgcePublicoLimpiarFalloExacto()`
- `SgcePublicoCampoHoneypot()`
- `SgcePublicoBuscarAlumno()`
- `SgcePublicoNormalizarFecha()`
- `SgcePublicoValidarRangoFechas()`
- `SgcePublicoResumenAsistencia()`
- `SgcePublicoCalificacionesCiclo()`
- `SgcePublicoLimpiarTokensConsulta()`
- `SgcePublicoCrearTokenConsulta()`
- `SgcePublicoLeerTokenConsulta()`
- `SgcePublicoTokenDesdeGet()`
- `SgcePublicoFormatoCalificacion()`

### `modules/AvisosAdmin.php`

Funciones:
- `HAviso()`
- `MayusAviso()`
- `PublicoAvisoValido()`
- `RedirectAvisos()`

### `modules/ConfiguracionAdmin.php`

Funciones:
- `HConfig()`
- `ConfigMayusculas()`
- `ConfigLongitud()`
- `ConfigNormalizar()`
- `ConfigFechaValida()`

### `modules/HistorialAlumno.php`

Funciones:
- `H()`
- `TextoEstado()`
- `ClaseEstado()`

### `modules/Importar.php`

Funciones:
- `RedirectAdminImportar()`
- `BomStrip()`
- `ExtensionImportacion()`
- `InfoServidorSubidasImportacion()`
- `MensajeErrorSubidaImportacion()`
- `ValidarArchivoImportacionSubido()`
- `DetectarDelimitadorCsv()`
- `LeerFilasCsv()`
- `ColumnaExcelAIndice()`
- `TextoNodosExcel()`
- `SharedStringsExcel()`
- `PrimeraHojaExcel()`
- `LeerFilasXlsx()`
- `LeerFilasImportacionSubida()`
- `EsFilaVacia()`
- `EsEncabezadoAlumno()`
- `EsEncabezadoDocente()`
- `EsEncabezadoGrupo()`
- `UsuariosExistentesPorUsername()`
- `HashPasswordImportacion()`

### `modules/PeriodosAdmin.php`

Funciones:
- `HPeriodo()`

### `modules/Planeaciones.php`

Funciones:
- `HPlan()`
- `PlaneacionEstadoClase()`

### `modules/PlaneacionesAdmin.php`

Funciones:
- `HPlanAdmin()`
- `PlanAdminEstadoClase()`

### `modules/RestaurarBD.php`

Funciones:
- `HRest()`
- `InfoServidorSubidasRestaurar()`
- `MensajeErrorSubidaRest()`
- `RedirectRestaurar()`
- `QTablaRest()`
- `TablasSistemaRest()`
- `VaciarTablasRest()`
- `GarantizarSesionDespuesRestaurar()`
- `PartirSqlRest()`
- `SentenciaPermitidaRest()`
- `ImportarSqlRest()`

### `public/ConsultaCalificaciones.php`

Funciones:
- `HCC()`

### `public/ConsultaPadre.php`

Funciones:
- `HCP()`
- `FechaHumanaCP()`

### `reports/ExportarAsistencia.php`

Funciones:
- `HAsis()`
- `ArchivoSeguroAsis()`
- `EstadoAsis()`
- `EstilosAsis()`

### `reports/ExportarCalificaciones.php`

Funciones:
- `HExpCal()`
- `ArchivoSeguroCal()`
- `FormatoCal()`
- `SgceCalificacionesEmitirExcel()`
- `EstilosReporteCal()`

### `reports/ExportarDatosBD.php`

Funciones:
- `QTablaDatos()`
- `ColumnasInsertablesDatos()`
- `ValorSqlDatos()`
- `LlavePrimariaDatos()`

### `reports/RespaldoBD.php`

Funciones:
- `QTablaRespaldo()`
- `ColumnasInsertablesRespaldo()`

### `services/AlumnoService.php`

Funciones:
- `SgceAlumnoContarActivos()`
- `SgceAlumnoListarPaginado()`
- `SgceAlumnoListarActivosPorGrupo()`

### `services/AsistenciaService.php`

Funciones:
- `SgceAsistenciaResumenHoy()`

### `services/CalificacionService.php`

Funciones:
- `SgceCalificacionPromedioGeneralCiclo()`

### `services/GrupoService.php`

Funciones:
- `SgceGrupoContarActivos()`
- `SgceGrupoListarActivos()`
- `SgceGrupoListarPaginado()`
- `SgceGrupoObtenerActivoPorId()`
- `SgceGrupoExisteActivo()`

### `services/MaestroService.php`

Funciones:
- `SgceMaestroContarActivos()`
- `SgceMaestroListarActivos()`
- `SgceMaestroListarPaginado()`
- `SgceMaestroExisteActivo()`

### `services/ReporteService.php`

Funciones:
- `SgceAsignacionContarActivas()`
- `SgceAsignacionListarPaginadas()`
- `SgceReporteAlumnosRiesgo()`
- `SgceReporteBitacoraReciente()`

### `services/UsuarioService.php`

Funciones:
- `SgceUsuarioContarAdminsActivosServicio()`

## 4. Checklist funcional revisado

- [x] Instalador con verificación de extensiones PHP, permisos y conexión MySQL.
- [x] Creación de base de datos desde SQL limpio.
- [x] Alta de administrador inicial.
- [x] Configuración institucional y ciclo activo.
- [x] Control de sesión y redirección por rol.
- [x] Panel administrativo con pestañas permitidas por rol.
- [x] Validación de nombres, grupos, grados, turnos, usuarios y contraseñas.
- [x] Desactivación lógica de docentes, alumnos, grupos y asignaciones.
- [x] Carga de catálogos paginada para evitar pantallas pesadas.
- [x] Captura de asistencia por asignación, alumno y fecha.
- [x] Captura de calificaciones por periodo.
- [x] Consulta pública protegida con rate limit y token temporal para exportación.
- [x] Generación de PDF interno sin dependencia externa.
- [x] Validación de archivos de planeación por extensión, MIME y firma.
- [x] Respaldos y restauración con protección de carpetas.
- [x] Bitácora de movimientos críticos.
- [x] Archivos sensibles bloqueados por `.htaccess` y `.gitignore`.

## 5. Observaciones honestas

- Esta revisión valida estructura, sintaxis, rutas locales, inventario y consistencia documental. La prueba funcional completa requiere servidor real con MySQL y navegador.
- Los módulos internos no deben abrirse directamente desde URL; se accede por los wrappers de raíz.
- Los archivos Markdown están pensados para GitHub. En producción quedan protegidos por `.htaccess`.