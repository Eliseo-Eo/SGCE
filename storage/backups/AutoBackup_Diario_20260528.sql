-- SGCE respaldo automático
-- SGCE_EXPORT_SIGNATURE=SGCE_PRODUCCION
-- Fecha: 2026-05-28 21:59:22
SET FOREIGN_KEY_CHECKS=0;
SET NAMES utf8mb4;

DROP TABLE IF EXISTS `Alumnos`;
CREATE TABLE `Alumnos` (
  `Id` int unsigned NOT NULL AUTO_INCREMENT,
  `NombreCompleto` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `GrupoId` int unsigned DEFAULT NULL,
  `Activo` tinyint(1) NOT NULL DEFAULT '1',
  `FechaCreacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `FechaActualizacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `unico_alumno_grupo` (`NombreCompleto`,`GrupoId`),
  KEY `idx_alumnos_grupo_nombre_id` (`GrupoId`,`NombreCompleto`,`Id`),
  KEY `idx_alumnos_busqueda_publica` (`GrupoId`,`NombreCompleto`,`Activo`),
  KEY `idx_alumnos_nombre` (`NombreCompleto`),
  KEY `idx_alumnos_activo` (`Activo`),
  CONSTRAINT `fk_alumnos_grupos` FOREIGN KEY (`GrupoId`) REFERENCES `Grupos` (`Id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `Asignaciones`;
CREATE TABLE `Asignaciones` (
  `Id` int unsigned NOT NULL AUTO_INCREMENT,
  `MaestroId` int unsigned NOT NULL,
  `GrupoId` int unsigned NOT NULL,
  `MateriaNombre` varchar(140) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Activo` tinyint(1) NOT NULL DEFAULT '1',
  `FechaCreacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `FechaActualizacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `unica_asignacion` (`MaestroId`,`GrupoId`,`MateriaNombre`),
  KEY `idx_asignaciones_maestro` (`MaestroId`,`Activo`),
  KEY `idx_asignaciones_grupo` (`GrupoId`,`Activo`),
  KEY `idx_asignaciones_grupo_id` (`GrupoId`,`Id`,`Activo`),
  KEY `idx_asignaciones_grupo_materia` (`GrupoId`,`MateriaNombre`,`MaestroId`),
  KEY `idx_asignaciones_materia` (`MateriaNombre`),
  CONSTRAINT `fk_asignaciones_grupos` FOREIGN KEY (`GrupoId`) REFERENCES `Grupos` (`Id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_asignaciones_maestros` FOREIGN KEY (`MaestroId`) REFERENCES `Usuarios` (`Id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `Asistencias`;
CREATE TABLE `Asistencias` (
  `Id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `AsignacionId` int unsigned NOT NULL,
  `AlumnoId` int unsigned NOT NULL,
  `Fecha` datetime NOT NULL,
  `FechaDia` date GENERATED ALWAYS AS (cast(`Fecha` as date)) STORED,
  `Estado` enum('A','F','R','J') COLLATE utf8mb4_unicode_ci NOT NULL,
  `FechaRegistro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `unico_pase_lista` (`AsignacionId`,`AlumnoId`,`FechaDia`),
  KEY `idx_asistencias_asignacion_fecha_alumno` (`AsignacionId`,`FechaDia`,`AlumnoId`),
  KEY `idx_asistencias_alumno_fecha_asignacion` (`AlumnoId`,`FechaDia`,`AsignacionId`),
  KEY `idx_asistencias_fecha_asignacion_alumno` (`FechaDia`,`AsignacionId`,`AlumnoId`),
  KEY `idx_asistencias_publica` (`AlumnoId`,`FechaDia`,`Estado`,`AsignacionId`),
  KEY `idx_asistencias_estado_fecha` (`Estado`,`FechaDia`),
  KEY `idx_asistencias_fecha_estado` (`FechaDia`,`Estado`),
  KEY `idx_asistencias_fecha_alumno_estado` (`FechaDia`,`AlumnoId`,`Estado`),
  CONSTRAINT `fk_asistencias_alumnos` FOREIGN KEY (`AlumnoId`) REFERENCES `Alumnos` (`Id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_asistencias_asignaciones` FOREIGN KEY (`AsignacionId`) REFERENCES `Asignaciones` (`Id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `Avisos`;
CREATE TABLE `Avisos` (
  `Id` int unsigned NOT NULL AUTO_INCREMENT,
  `Titulo` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Mensaje` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `Publico` enum('TODOS','MAESTROS','PADRES') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TODOS',
  `Activo` tinyint(1) NOT NULL DEFAULT '1',
  `FechaCreacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  KEY `idx_avisos_publico_activo_fecha` (`Publico`,`Activo`,`FechaCreacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `BitacoraMovimientos`;
CREATE TABLE `BitacoraMovimientos` (
  `Id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `UsuarioId` int unsigned DEFAULT NULL,
  `Rol` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Accion` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `TablaAfectada` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `RegistroId` bigint unsigned DEFAULT NULL,
  `Detalle` text COLLATE utf8mb4_unicode_ci,
  `Ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `FechaRegistro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  KEY `idx_bitacora_fecha` (`FechaRegistro`),
  KEY `idx_bitacora_usuario_fecha` (`UsuarioId`,`FechaRegistro`),
  KEY `idx_bitacora_accion_fecha` (`Accion`,`FechaRegistro`),
  KEY `idx_bitacora_tabla_registro` (`TablaAfectada`,`RegistroId`),
  CONSTRAINT `fk_bitacora_usuario` FOREIGN KEY (`UsuarioId`) REFERENCES `Usuarios` (`Id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `BitacoraMovimientos` (`Id`,`UsuarioId`,`Rol`,`Accion`,`TablaAfectada`,`RegistroId`,`Detalle`,`Ip`) VALUES ('1','1','admin','INSTALACION_INICIAL','ConfiguracionSistema',NULL,'INSTALACIÓN INICIAL DEL SISTEMA','127.0.0.1');
INSERT INTO `BitacoraMovimientos` (`Id`,`UsuarioId`,`Rol`,`Accion`,`TablaAfectada`,`RegistroId`,`Detalle`,`Ip`) VALUES ('2','1','admin','INICIO_SESION','Usuarios','1','USUARIO INICIÓ SESIÓN','127.0.0.1');

DROP TABLE IF EXISTS `Calificaciones`;
CREATE TABLE `Calificaciones` (
  `Id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `AlumnoId` int unsigned NOT NULL,
  `AsignacionId` int unsigned NOT NULL,
  `PeriodoId` int unsigned NOT NULL,
  `Calificacion` decimal(4,2) NOT NULL,
  `FechaActualizacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `unico_alumno_asignacion_periodo` (`AlumnoId`,`AsignacionId`,`PeriodoId`),
  KEY `idx_calificaciones_asignacion` (`AsignacionId`,`AlumnoId`),
  KEY `idx_calificaciones_periodo` (`PeriodoId`,`AsignacionId`,`AlumnoId`),
  KEY `idx_calificaciones_alumno` (`AlumnoId`,`AsignacionId`),
  KEY `idx_calificaciones_valor` (`Calificacion`),
  KEY `idx_calificaciones_periodo_alumno_valor` (`PeriodoId`,`AlumnoId`,`Calificacion`),
  CONSTRAINT `fk_calificaciones_alumnos` FOREIGN KEY (`AlumnoId`) REFERENCES `Alumnos` (`Id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_calificaciones_asignaciones` FOREIGN KEY (`AsignacionId`) REFERENCES `Asignaciones` (`Id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_calificaciones_periodos` FOREIGN KEY (`PeriodoId`) REFERENCES `PeriodosEvaluacion` (`Id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `CiclosEscolares`;
CREATE TABLE `CiclosEscolares` (
  `Id` int unsigned NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `FechaInicio` date NOT NULL,
  `FechaFin` date NOT NULL,
  `Activo` tinyint(1) NOT NULL DEFAULT '1',
  `FechaCreacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `unico_ciclo_nombre` (`Nombre`),
  KEY `idx_ciclos_activo_fecha` (`Activo`,`FechaInicio`,`FechaFin`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `CiclosEscolares` (`Id`,`Nombre`,`FechaInicio`,`FechaFin`,`Activo`) VALUES ('1','2026-2027','2026-08-01','2027-07-31','1');

DROP TABLE IF EXISTS `ConfiguracionSistema`;
CREATE TABLE `ConfiguracionSistema` (
  `Clave` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Valor` text COLLATE utf8mb4_unicode_ci,
  `FechaActualizacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`Clave`),
  KEY `idx_config_fecha` (`FechaActualizacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `ConfiguracionSistema` (`Clave`,`Valor`) VALUES ('ClaveCentroTrabajo','');
INSERT INTO `ConfiguracionSistema` (`Clave`,`Valor`) VALUES ('ColorInstitucional','#97051E');
INSERT INTO `ConfiguracionSistema` (`Clave`,`Valor`) VALUES ('CorreoEscuela','');
INSERT INTO `ConfiguracionSistema` (`Clave`,`Valor`) VALUES ('DirectorNombre','');
INSERT INTO `ConfiguracionSistema` (`Clave`,`Valor`) VALUES ('InstalacionFecha','2026-05-29 03:59:11');
INSERT INTO `ConfiguracionSistema` (`Clave`,`Valor`) VALUES ('LemaInstitucional','');
INSERT INTO `ConfiguracionSistema` (`Clave`,`Valor`) VALUES ('MunicipioEstado','');
INSERT INTO `ConfiguracionSistema` (`Clave`,`Valor`) VALUES ('NombreEscuela','ESCUELA SECUNDARIA TECNICA 101');
INSERT INTO `ConfiguracionSistema` (`Clave`,`Valor`) VALUES ('PlaneacionesCantidad','3');
INSERT INTO `ConfiguracionSistema` (`Clave`,`Valor`) VALUES ('SistemaNombre','SGCE');
INSERT INTO `ConfiguracionSistema` (`Clave`,`Valor`) VALUES ('TelefonoEscuela','');

DROP TABLE IF EXISTS `Grupos`;
CREATE TABLE `Grupos` (
  `Id` int unsigned NOT NULL AUTO_INCREMENT,
  `Grado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Grupo` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Turno` enum('MATUTINO','VESPERTINO') COLLATE utf8mb4_unicode_ci NOT NULL,
  `Activo` tinyint(1) NOT NULL DEFAULT '1',
  `FechaCreacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `FechaActualizacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `unico_grupo_turno` (`Grado`,`Grupo`,`Turno`),
  KEY `idx_grupos_busqueda_publica` (`Grado`,`Grupo`,`Turno`,`Activo`),
  KEY `idx_grupos_orden` (`Activo`,`Turno`,`Grado`,`Grupo`,`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `IntentosSeguridad`;
CREATE TABLE `IntentosSeguridad` (
  `Id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ClaveHash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Contexto` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Intentos` int unsigned NOT NULL DEFAULT '0',
  `BloqueadoHasta` datetime DEFAULT NULL,
  `UltimoIntento` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `unico_contexto_clave` (`Contexto`,`ClaveHash`),
  KEY `idx_intentos_bloqueado` (`Contexto`,`BloqueadoHasta`),
  KEY `idx_intentos_ultimo` (`UltimoIntento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `PeriodosEvaluacion`;
CREATE TABLE `PeriodosEvaluacion` (
  `Id` int unsigned NOT NULL AUTO_INCREMENT,
  `CicloId` int unsigned NOT NULL,
  `Nombre` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Orden` int unsigned NOT NULL DEFAULT '1',
  `Activo` tinyint(1) NOT NULL DEFAULT '1',
  `FechaCreacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `unico_periodo_ciclo_nombre` (`CicloId`,`Nombre`),
  UNIQUE KEY `unico_periodo_ciclo_orden` (`CicloId`,`Orden`),
  KEY `idx_periodos_ciclo_orden` (`CicloId`,`Activo`,`Orden`),
  CONSTRAINT `fk_periodos_ciclos` FOREIGN KEY (`CicloId`) REFERENCES `CiclosEscolares` (`Id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `PeriodosEvaluacion` (`Id`,`CicloId`,`Nombre`,`Orden`,`Activo`) VALUES ('1','1','PRIMER PARCIAL','1','1');
INSERT INTO `PeriodosEvaluacion` (`Id`,`CicloId`,`Nombre`,`Orden`,`Activo`) VALUES ('2','1','SEGUNDO PARCIAL','2','1');
INSERT INTO `PeriodosEvaluacion` (`Id`,`CicloId`,`Nombre`,`Orden`,`Activo`) VALUES ('3','1','TERCER PARCIAL','3','1');

DROP TABLE IF EXISTS `Planeaciones`;
CREATE TABLE `Planeaciones` (
  `Id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `CicloId` int unsigned NOT NULL,
  `MaestroId` int unsigned NOT NULL,
  `MateriaNombre` varchar(140) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Numero` int unsigned NOT NULL,
  `VersionArchivo` int unsigned NOT NULL DEFAULT '1',
  `Titulo` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ArchivoOriginal` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ArchivoGuardado` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `MimeType` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `TamanoBytes` bigint unsigned NOT NULL DEFAULT '0',
  `Estado` enum('SUBIDA','APROBADA','DEVUELTA') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SUBIDA',
  `NotaRevision` text COLLATE utf8mb4_unicode_ci,
  `RevisadoPor` int unsigned DEFAULT NULL,
  `FechaRevision` datetime DEFAULT NULL,
  `FechaSubida` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `FechaActualizacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `unico_planeacion_docente_materia_numero` (`CicloId`,`MaestroId`,`MateriaNombre`,`Numero`),
  KEY `fk_planeaciones_revisor` (`RevisadoPor`),
  KEY `idx_planeaciones_maestro_ciclo` (`MaestroId`,`CicloId`,`MateriaNombre`,`Numero`),
  KEY `idx_planeaciones_estado` (`Estado`,`FechaActualizacion`),
  KEY `idx_planeaciones_numero` (`Numero`),
  CONSTRAINT `fk_planeaciones_ciclo` FOREIGN KEY (`CicloId`) REFERENCES `CiclosEscolares` (`Id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_planeaciones_maestro` FOREIGN KEY (`MaestroId`) REFERENCES `Usuarios` (`Id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_planeaciones_revisor` FOREIGN KEY (`RevisadoPor`) REFERENCES `Usuarios` (`Id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `Usuarios`;
CREATE TABLE `Usuarios` (
  `Id` int unsigned NOT NULL AUTO_INCREMENT,
  `Username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `NombreCompleto` varchar(140) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Rol` enum('admin','maestro','director','secretario','coordinador','prefecto') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'maestro',
  `Activo` tinyint(1) NOT NULL DEFAULT '1',
  `SessionToken` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `SessionTokenExpira` datetime DEFAULT NULL,
  `FechaCreacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `FechaActualizacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `unico_username` (`Username`),
  KEY `idx_usuarios_session_token` (`SessionToken`),
  KEY `idx_usuarios_session_expira` (`SessionTokenExpira`),
  KEY `idx_usuarios_rol_activo_nombre` (`Rol`,`Activo`,`NombreCompleto`),
  KEY `idx_usuarios_nombre` (`NombreCompleto`),
  KEY `idx_usuarios_activo` (`Activo`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `Usuarios` (`Id`,`Username`,`Password`,`NombreCompleto`,`Rol`,`Activo`,`SessionToken`,`SessionTokenExpira`) VALUES ('1','ADMIN','$2y$12$OPK3SeVYCjA2CxMvnA5/T.wv8Qfr.qaIZn5nuVPB4ygrpfZu3FQSy','ADMINISTRADOR','admin','1',NULL,NULL);

SET FOREIGN_KEY_CHECKS=1;
