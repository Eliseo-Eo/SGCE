-- ============================================================
-- BASE DE DATOS SGCE - VERSIÓN PROFESIONAL OPTIMIZADA
-- Pensada para escuela grande: muchos alumnos, docentes, grupos,
-- asistencias diarias por materia, reportes y consulta pública segura.
-- ============================================================

DROP DATABASE IF EXISTS ControlEscolar;
CREATE DATABASE ControlEscolar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ControlEscolar;

-- ============================================================
-- USUARIOS DEL SISTEMA
-- Username y Password se respetan exactamente como se escriben.
-- ============================================================
CREATE TABLE Usuarios (
    Id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(50) NOT NULL,
    Password VARCHAR(255) NOT NULL,
    NombreCompleto VARCHAR(140) NOT NULL,
    Rol ENUM('admin', 'maestro', 'director', 'secretario', 'coordinador', 'prefecto') NOT NULL DEFAULT 'maestro',
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

-- ============================================================
-- GRUPOS
-- ============================================================
CREATE TABLE Grupos (
    Id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Grado VARCHAR(20) NOT NULL,
    Grupo VARCHAR(10) NOT NULL,
    Turno ENUM('MATUTINO', 'VESPERTINO') NOT NULL,
    Activo TINYINT(1) NOT NULL DEFAULT 1,
    FechaCreacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FechaActualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unico_grupo_turno (Grado, Grupo, Turno),
    INDEX idx_grupos_busqueda_publica (Grado, Grupo, Turno, Activo),
    INDEX idx_grupos_orden (Activo, Turno, Grado, Grupo, Id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ALUMNOS
-- La consulta pública valida coincidencia exacta de nombre, grado, grupo y turno.
-- No se elimina físicamente: se usa Activo = 0 para conservar historial.
-- ============================================================
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
    INDEX idx_alumnos_activo (Activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ASIGNACIONES
-- No se eliminan físicamente: Activo = 0 conserva historial.
-- ============================================================
CREATE TABLE Asignaciones (
    Id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    MaestroId INT UNSIGNED NOT NULL,
    GrupoId INT UNSIGNED NOT NULL,
    MateriaNombre VARCHAR(140) NOT NULL,
    Activo TINYINT(1) NOT NULL DEFAULT 1,
    FechaCreacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FechaActualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_asignaciones_maestros FOREIGN KEY (MaestroId) REFERENCES Usuarios(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_asignaciones_grupos FOREIGN KEY (GrupoId) REFERENCES Grupos(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
    UNIQUE KEY unica_asignacion (MaestroId, GrupoId, MateriaNombre),
    INDEX idx_asignaciones_maestro (MaestroId, Activo),
    INDEX idx_asignaciones_grupo (GrupoId, Activo),
    INDEX idx_asignaciones_grupo_id (GrupoId, Id, Activo),
    INDEX idx_asignaciones_grupo_materia (GrupoId, MateriaNombre, MaestroId),
    INDEX idx_asignaciones_materia (MateriaNombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- CICLOS ESCOLARES Y PERIODOS DE EVALUACIÓN
-- ============================================================
CREATE TABLE CiclosEscolares (
    Id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(30) NOT NULL,
    FechaInicio DATE NOT NULL,
    FechaFin DATE NOT NULL,
    Activo TINYINT(1) NOT NULL DEFAULT 1,
    FechaCreacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unico_ciclo_nombre (Nombre),
    INDEX idx_ciclos_activo_fecha (Activo, FechaInicio, FechaFin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE PeriodosEvaluacion (
    Id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    CicloId INT UNSIGNED NOT NULL,
    Nombre VARCHAR(60) NOT NULL,
    Orden INT UNSIGNED NOT NULL DEFAULT 1,
    Activo TINYINT(1) NOT NULL DEFAULT 1,
    FechaCreacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_periodos_ciclos FOREIGN KEY (CicloId) REFERENCES CiclosEscolares(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
    UNIQUE KEY unico_periodo_ciclo_nombre (CicloId, Nombre),
    INDEX idx_periodos_ciclo_orden (CicloId, Activo, Orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CALIFICACIONES
-- ============================================================
CREATE TABLE Calificaciones (
    Id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    AlumnoId INT UNSIGNED NOT NULL,
    AsignacionId INT UNSIGNED NOT NULL,
    PeriodoId INT UNSIGNED NOT NULL,
    Calificacion DECIMAL(4,2) NOT NULL,
    FechaActualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_calificaciones_alumnos FOREIGN KEY (AlumnoId) REFERENCES Alumnos(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_calificaciones_asignaciones FOREIGN KEY (AsignacionId) REFERENCES Asignaciones(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_calificaciones_periodos FOREIGN KEY (PeriodoId) REFERENCES PeriodosEvaluacion(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
    UNIQUE KEY unico_alumno_asignacion_periodo (AlumnoId, AsignacionId, PeriodoId),
    INDEX idx_calificaciones_asignacion (AsignacionId, AlumnoId),
    INDEX idx_calificaciones_periodo (PeriodoId, AsignacionId, AlumnoId),
    INDEX idx_calificaciones_alumno (AlumnoId, AsignacionId),
    INDEX idx_calificaciones_valor (Calificacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ASISTENCIAS
-- Un alumno puede tener muchas asistencias al día: una por materia.
-- La combinación AsignacionId + AlumnoId + FechaDia evita duplicar pase.
-- ============================================================
CREATE TABLE Asistencias (
    Id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    AsignacionId INT UNSIGNED NOT NULL,
    AlumnoId INT UNSIGNED NOT NULL,
    Fecha DATETIME NOT NULL,
    FechaDia DATE GENERATED ALWAYS AS (DATE(Fecha)) STORED,
    Estado ENUM('A', 'F', 'R', 'J') NOT NULL,
    FechaRegistro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_asistencias_asignaciones FOREIGN KEY (AsignacionId) REFERENCES Asignaciones(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_asistencias_alumnos FOREIGN KEY (AlumnoId) REFERENCES Alumnos(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
    UNIQUE KEY unico_pase_lista (AsignacionId, AlumnoId, FechaDia),
    INDEX idx_asistencias_asignacion_fecha_alumno (AsignacionId, FechaDia, AlumnoId),
    INDEX idx_asistencias_alumno_fecha_asignacion (AlumnoId, FechaDia, AsignacionId),
    INDEX idx_asistencias_fecha_asignacion_alumno (FechaDia, AsignacionId, AlumnoId),
    INDEX idx_asistencias_publica (AlumnoId, FechaDia, Estado, AsignacionId),
    INDEX idx_asistencias_estado_fecha (Estado, FechaDia),
    INDEX idx_asistencias_fecha_estado (FechaDia, Estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- AVISOS DEL SISTEMA
-- ============================================================
CREATE TABLE Avisos (
    Id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Titulo VARCHAR(160) NOT NULL,
    Mensaje TEXT NOT NULL,
    Publico ENUM('TODOS', 'MAESTROS', 'PADRES') NOT NULL DEFAULT 'TODOS',
    Activo TINYINT(1) NOT NULL DEFAULT 1,
    FechaCreacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_avisos_publico_activo_fecha (Publico, Activo, FechaCreacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- BITÁCORA DE MOVIMIENTOS
-- Guarda acciones importantes para auditoría.
-- ============================================================
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
    INDEX idx_bitacora_tabla_registro (TablaAfectada, RegistroId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- INTENTOS DE SEGURIDAD
-- Controla intentos fallidos de login y consulta pública.
-- ============================================================
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

-- ============================================================
-- USUARIO ADMINISTRADOR INICIAL
-- ============================================================
INSERT INTO Usuarios (Username, Password, NombreCompleto, Rol, Activo)
VALUES ('Admin', 'Admin123', 'ADMINISTRADOR GENERAL', 'admin', 1);

INSERT INTO Avisos (Titulo, Mensaje, Publico, Activo)
VALUES ('BIENVENIDO A SGCE', 'SISTEMA INTEGRAL DE GESTIÓN ESCOLAR LISTO PARA INICIAR.', 'TODOS', 1);


INSERT INTO CiclosEscolares (Nombre, FechaInicio, FechaFin, Activo) VALUES ('2025-2026','2025-08-01','2026-07-31',1);
INSERT INTO PeriodosEvaluacion (CicloId, Nombre, Orden, Activo) VALUES
(1,'PRIMER PERIODO',1,1),
(1,'SEGUNDO PERIODO',2,1),
(1,'TERCER PERIODO',3,1),
(1,'FINAL',4,1);
