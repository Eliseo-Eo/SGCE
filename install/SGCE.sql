
CREATE TABLE ConfiguracionSistema (
    Clave VARCHAR(80) NOT NULL PRIMARY KEY,
    Valor TEXT NULL,
    FechaActualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_config_fecha (FechaActualizacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Usuarios (
    Id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(80) NOT NULL,
    Password VARCHAR(255) NOT NULL,
    NombreCompleto VARCHAR(140) NOT NULL,
    Rol ENUM('admin', 'administrativo', 'maestro') NOT NULL DEFAULT 'maestro',
    Activo TINYINT(1) NOT NULL DEFAULT 1,
    SessionToken CHAR(64) DEFAULT NULL,
    SessionTokenExpira DATETIME DEFAULT NULL,
    FechaCreacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FechaActualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unico_username (Username),
    INDEX idx_usuarios_session_token (SessionToken),
    INDEX idx_usuarios_session_expira (SessionTokenExpira),
    INDEX idx_usuarios_rol_activo_nombre (Rol, Activo, NombreCompleto),
    INDEX idx_usuarios_nombre (NombreCompleto),
    INDEX idx_usuarios_activo (Activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE CiclosEscolares (
    Id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(40) NOT NULL,
    FechaInicio DATE NOT NULL,
    FechaFin DATE NOT NULL,
    Activo TINYINT(1) NOT NULL DEFAULT 1,
    FechaCreacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unico_ciclo_nombre (Nombre),
    INDEX idx_ciclos_activo_fecha (Activo, FechaInicio, FechaFin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Grupos (
    Id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    CicloId INT UNSIGNED NOT NULL,
    Grado VARCHAR(20) NOT NULL,
    Grupo VARCHAR(10) NOT NULL,
    Turno ENUM('MATUTINO', 'VESPERTINO') NOT NULL,
    Activo TINYINT(1) NOT NULL DEFAULT 1,
    FechaCreacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FechaActualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_grupos_ciclo FOREIGN KEY (CicloId) REFERENCES CiclosEscolares(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
    UNIQUE KEY unico_grupo_ciclo_turno (CicloId, Grado, Grupo, Turno),
    INDEX idx_grupos_busqueda_publica (CicloId, Grado, Grupo, Turno, Activo),
    INDEX idx_grupos_orden (CicloId, Activo, Turno, Grado, Grupo, Id),
    INDEX idx_grupos_ciclo (CicloId, Activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Alumnos (
    Id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    NombreCompleto VARCHAR(160) NOT NULL,
    GrupoId INT UNSIGNED DEFAULT NULL,
    Activo TINYINT(1) NOT NULL DEFAULT 1,
    FechaCreacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FechaActualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_alumnos_grupos FOREIGN KEY (GrupoId) REFERENCES Grupos(Id) ON DELETE SET NULL ON UPDATE CASCADE,
    UNIQUE KEY unico_alumno_grupo (NombreCompleto, GrupoId),
    INDEX idx_alumnos_grupo_nombre_id (GrupoId, NombreCompleto, Id),
    INDEX idx_alumnos_busqueda_publica (GrupoId, NombreCompleto, Activo),
    INDEX idx_alumnos_nombre (NombreCompleto),
    INDEX idx_alumnos_activo_grupo_nombre (Activo, GrupoId, NombreCompleto, Id),
    INDEX idx_alumnos_activo (Activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE AlumnoInscripciones (
    Id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    AlumnoId INT UNSIGNED NOT NULL,
    CicloId INT UNSIGNED NOT NULL,
    GrupoId INT UNSIGNED NOT NULL,
    Estado ENUM('INSCRITO','PROMOVIDO','EGRESADO','BAJA') NOT NULL DEFAULT 'INSCRITO',
    FechaInscripcion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FechaActualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_inscripciones_alumno FOREIGN KEY (AlumnoId) REFERENCES Alumnos(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_inscripciones_ciclo FOREIGN KEY (CicloId) REFERENCES CiclosEscolares(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_inscripciones_grupo FOREIGN KEY (GrupoId) REFERENCES Grupos(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
    UNIQUE KEY unico_alumno_ciclo (AlumnoId, CicloId),
    INDEX idx_inscripciones_ciclo_grupo_estado (CicloId, GrupoId, Estado, AlumnoId),
    INDEX idx_inscripciones_alumno_ciclo (AlumnoId, CicloId),
    INDEX idx_inscripciones_grupo_estado (GrupoId, Estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE MateriasCatalogo (
    Id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(140) NOT NULL,
    Activo TINYINT(1) NOT NULL DEFAULT 1,
    FechaCreacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FechaActualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unico_materia_nombre (Nombre),
    INDEX idx_materias_activo_nombre (Activo, Nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Asignaciones (
    Id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    CicloId INT UNSIGNED NOT NULL,
    MaestroId INT UNSIGNED NOT NULL,
    GrupoId INT UNSIGNED NOT NULL,
    MateriaId INT UNSIGNED DEFAULT NULL,
    MateriaNombre VARCHAR(140) NOT NULL,
    Activo TINYINT(1) NOT NULL DEFAULT 1,
    FechaCreacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FechaActualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_asignaciones_ciclo FOREIGN KEY (CicloId) REFERENCES CiclosEscolares(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_asignaciones_maestros FOREIGN KEY (MaestroId) REFERENCES Usuarios(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_asignaciones_grupos FOREIGN KEY (GrupoId) REFERENCES Grupos(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_asignaciones_materia_catalogo FOREIGN KEY (MateriaId) REFERENCES MateriasCatalogo(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
    UNIQUE KEY unica_asignacion (CicloId, MaestroId, GrupoId, MateriaNombre),
    UNIQUE KEY unica_materia_grupo_ciclo (CicloId, GrupoId, MateriaNombre),
    INDEX idx_asignaciones_maestro (CicloId, MaestroId, Activo),
    INDEX idx_asignaciones_grupo (CicloId, GrupoId, Activo),
    INDEX idx_asignaciones_grupo_id (GrupoId, Id, Activo),
    INDEX idx_asignaciones_grupo_materia (CicloId, GrupoId, MateriaNombre, MaestroId),
    INDEX idx_asignaciones_activo_maestro_grupo_materia (CicloId, Activo, MaestroId, GrupoId, MateriaNombre, Id),
    INDEX idx_asignaciones_materia (MateriaNombre),
    INDEX idx_asignaciones_materia_id (MateriaId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE PeriodosEvaluacion (
    Id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    CicloId INT UNSIGNED NOT NULL,
    Nombre VARCHAR(80) NOT NULL,
    Orden INT UNSIGNED NOT NULL DEFAULT 1,
    Activo TINYINT(1) NOT NULL DEFAULT 1,
    FechaCreacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_periodos_ciclos FOREIGN KEY (CicloId) REFERENCES CiclosEscolares(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
    UNIQUE KEY unico_periodo_ciclo_nombre (CicloId, Nombre),
    UNIQUE KEY unico_periodo_ciclo_orden (CicloId, Orden),
    INDEX idx_periodos_ciclo_orden (CicloId, Activo, Orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Calificaciones (
    Id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    AlumnoId INT UNSIGNED NOT NULL,
    AsignacionId INT UNSIGNED NOT NULL,
    PeriodoId INT UNSIGNED NOT NULL,
    Calificacion DECIMAL(4,2) NOT NULL,
    CONSTRAINT chk_calificaciones_rango CHECK (Calificacion >= 5 AND Calificacion <= 10),
    FechaActualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_calificaciones_alumnos FOREIGN KEY (AlumnoId) REFERENCES Alumnos(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_calificaciones_asignaciones FOREIGN KEY (AsignacionId) REFERENCES Asignaciones(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_calificaciones_periodos FOREIGN KEY (PeriodoId) REFERENCES PeriodosEvaluacion(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
    UNIQUE KEY unico_alumno_asignacion_periodo (AlumnoId, AsignacionId, PeriodoId),
    INDEX idx_calificaciones_asignacion (AsignacionId, AlumnoId),
    INDEX idx_calificaciones_periodo (PeriodoId, AsignacionId, AlumnoId),
    INDEX idx_calificaciones_alumno (AlumnoId, AsignacionId),
    INDEX idx_calificaciones_valor (Calificacion),
    INDEX idx_calificaciones_periodo_asignacion_alumno_valor (PeriodoId, AsignacionId, AlumnoId, Calificacion),
    INDEX idx_calificaciones_periodo_alumno_valor (PeriodoId, AlumnoId, Calificacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Asistencias (
    Id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    CicloId INT UNSIGNED NOT NULL,
    AsignacionId INT UNSIGNED NOT NULL,
    AlumnoId INT UNSIGNED NOT NULL,
    Fecha DATETIME NOT NULL,
    FechaDia DATE GENERATED ALWAYS AS (DATE(Fecha)) STORED,
    Estado ENUM('A', 'F', 'R', 'J') NOT NULL,
    FechaRegistro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_asistencias_ciclo FOREIGN KEY (CicloId) REFERENCES CiclosEscolares(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_asistencias_asignaciones FOREIGN KEY (AsignacionId) REFERENCES Asignaciones(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_asistencias_alumnos FOREIGN KEY (AlumnoId) REFERENCES Alumnos(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
    UNIQUE KEY unico_pase_lista (CicloId, AsignacionId, AlumnoId, FechaDia),
    INDEX idx_asistencias_asignacion_fecha_alumno (CicloId, AsignacionId, FechaDia, AlumnoId),
    INDEX idx_asistencias_alumno_fecha_asignacion (CicloId, AlumnoId, FechaDia, AsignacionId),
    INDEX idx_asistencias_fecha_asignacion_alumno (CicloId, FechaDia, AsignacionId, AlumnoId),
    INDEX idx_asistencias_publica (CicloId, AlumnoId, FechaDia, Estado, AsignacionId),
    INDEX idx_asistencias_estado_fecha (CicloId, Estado, FechaDia),
    INDEX idx_asistencias_fecha_estado (CicloId, FechaDia, Estado),
    INDEX idx_asistencias_rango_reporte (CicloId, FechaDia, AsignacionId, Estado, AlumnoId),
    INDEX idx_asistencias_fecha_alumno_estado (CicloId, FechaDia, AlumnoId, Estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE AsignacionDocenteHistorial (
    Id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    AsignacionId INT UNSIGNED NOT NULL,
    MaestroId INT UNSIGNED NOT NULL,
    FechaInicio DATETIME NULL,
    FechaFin DATETIME NULL,
    TipoMovimiento ENUM('TITULAR','INTERINATO','RELEVO') NOT NULL DEFAULT 'TITULAR',
    Motivo VARCHAR(255) DEFAULT NULL,
    RegistradoPor INT UNSIGNED DEFAULT NULL,
    FechaRegistro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_hist_asignacion FOREIGN KEY (AsignacionId) REFERENCES Asignaciones(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_hist_maestro FOREIGN KEY (MaestroId) REFERENCES Usuarios(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_hist_registrado_por FOREIGN KEY (RegistradoPor) REFERENCES Usuarios(Id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_hist_asignacion_fechas (AsignacionId, FechaInicio, FechaFin),
    INDEX idx_hist_maestro (MaestroId, FechaInicio, FechaFin),
    INDEX idx_hist_activo (AsignacionId, FechaFin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE KardexAlumno (
    Id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    AlumnoId INT UNSIGNED NOT NULL,
    CicloId INT UNSIGNED NOT NULL,
    GrupoId INT UNSIGNED NOT NULL,
    CicloNombreSnapshot VARCHAR(40) NOT NULL,
    GradoSnapshot VARCHAR(20) NOT NULL,
    GrupoSnapshot VARCHAR(10) NOT NULL,
    TurnoSnapshot VARCHAR(20) NOT NULL,
    EstadoFinal ENUM('INSCRITO','PROMOVIDO','EGRESADO','BAJA') NOT NULL DEFAULT 'INSCRITO',
    PromedioFinal DECIMAL(5,2) DEFAULT NULL,
    GeneradoPor INT UNSIGNED DEFAULT NULL,
    FechaGeneracion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_kardex_alumno FOREIGN KEY (AlumnoId) REFERENCES Alumnos(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_kardex_ciclo FOREIGN KEY (CicloId) REFERENCES CiclosEscolares(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_kardex_grupo FOREIGN KEY (GrupoId) REFERENCES Grupos(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_kardex_generado_por FOREIGN KEY (GeneradoPor) REFERENCES Usuarios(Id) ON DELETE SET NULL ON UPDATE CASCADE,
    UNIQUE KEY unico_kardex_alumno_ciclo (AlumnoId, CicloId),
    INDEX idx_kardex_alumno_ciclo (AlumnoId, CicloId),
    INDEX idx_kardex_ciclo_grupo (CicloId, GrupoId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE KardexDetalle (
    Id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    KardexId BIGINT UNSIGNED NOT NULL,
    MateriaNombreSnapshot VARCHAR(140) NOT NULL,
    MaestroNombreSnapshot VARCHAR(140) DEFAULT NULL,
    Parcial1 DECIMAL(4,2) DEFAULT NULL,
    Parcial2 DECIMAL(4,2) DEFAULT NULL,
    Parcial3 DECIMAL(4,2) DEFAULT NULL,
    Promedio DECIMAL(5,2) DEFAULT NULL,
    Orden INT UNSIGNED NOT NULL DEFAULT 1,
    CONSTRAINT fk_kardex_detalle FOREIGN KEY (KardexId) REFERENCES KardexAlumno(Id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_kardex_detalle_kardex_orden (KardexId, Orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Avisos (
    Id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Titulo VARCHAR(160) NOT NULL,
    Mensaje TEXT NOT NULL,
    Publico ENUM('TODOS', 'MAESTROS', 'PADRES') NOT NULL DEFAULT 'TODOS',
    Activo TINYINT(1) NOT NULL DEFAULT 1,
    FechaCreacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_avisos_activo_fecha (Activo, FechaCreacion),
    INDEX idx_avisos_publico_activo_fecha (Publico, Activo, FechaCreacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE Planeaciones (
    Id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    CicloId INT UNSIGNED NOT NULL,
    MaestroId INT UNSIGNED NOT NULL,
    MateriaNombre VARCHAR(140) NOT NULL,
    Numero INT UNSIGNED NOT NULL,
    VersionArchivo INT UNSIGNED NOT NULL DEFAULT 1,
    Titulo VARCHAR(180) DEFAULT NULL,
    ArchivoOriginal VARCHAR(255) NOT NULL,
    ArchivoGuardado VARCHAR(255) NOT NULL,
    MimeType VARCHAR(120) DEFAULT NULL,
    TamanoBytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
    Estado ENUM('SUBIDA','APROBADA','DEVUELTA') NOT NULL DEFAULT 'SUBIDA',
    NotaRevision TEXT DEFAULT NULL,
    RevisadoPor INT UNSIGNED DEFAULT NULL,
    FechaRevision DATETIME DEFAULT NULL,
    FechaSubida TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FechaActualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_planeaciones_ciclo FOREIGN KEY (CicloId) REFERENCES CiclosEscolares(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_planeaciones_maestro FOREIGN KEY (MaestroId) REFERENCES Usuarios(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_planeaciones_revisor FOREIGN KEY (RevisadoPor) REFERENCES Usuarios(Id) ON DELETE SET NULL ON UPDATE CASCADE,
    UNIQUE KEY unico_planeacion_docente_materia_numero (CicloId, MaestroId, MateriaNombre, Numero),
    INDEX idx_planeaciones_maestro_ciclo (MaestroId, CicloId, MateriaNombre, Numero),
    INDEX idx_planeaciones_estado (Estado, FechaActualizacion),
    INDEX idx_planeaciones_numero (Numero)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE BitacoraMovimientos (
    Id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    UsuarioId INT UNSIGNED DEFAULT NULL,
    Rol VARCHAR(30) DEFAULT NULL,
    Accion VARCHAR(80) NOT NULL,
    TablaAfectada VARCHAR(80) DEFAULT NULL,
    RegistroId BIGINT UNSIGNED DEFAULT NULL,
    Detalle TEXT DEFAULT NULL,
    Ip VARCHAR(45) DEFAULT NULL,
    FechaRegistro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bitacora_usuario FOREIGN KEY (UsuarioId) REFERENCES Usuarios(Id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_bitacora_fecha (FechaRegistro),
    INDEX idx_bitacora_usuario_fecha (UsuarioId, FechaRegistro),
    INDEX idx_bitacora_accion_fecha (Accion, FechaRegistro),
    INDEX idx_bitacora_fecha_id (FechaRegistro, Id),
    INDEX idx_bitacora_tabla_registro (TablaAfectada, RegistroId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IntentosSeguridad (
    Id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ClaveHash CHAR(64) NOT NULL,
    Contexto VARCHAR(40) NOT NULL,
    Intentos INT UNSIGNED NOT NULL DEFAULT 0,
    BloqueadoHasta DATETIME DEFAULT NULL,
    UltimoIntento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unico_contexto_clave (Contexto, ClaveHash),
    INDEX idx_intentos_bloqueado (Contexto, BloqueadoHasta),
    INDEX idx_intentos_ultimo (UltimoIntento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
