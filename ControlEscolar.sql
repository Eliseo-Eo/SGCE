-- ============================================================
-- BASE DE DATOS DEL SISTEMA SGCE
-- Versión optimizada para iniciar desde cero y crecer sin tronar fácil.
-- Pensada para cientos de alumnos, muchos maestros y miles/millones de asistencias.
-- ============================================================

DROP DATABASE IF EXISTS ControlEscolar;
CREATE DATABASE ControlEscolar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ControlEscolar;

-- ============================================================
-- USUARIOS DEL SISTEMA
-- Username y Password se respetan exactamente como se escriben.
-- Los demás textos se manejan en mayúsculas desde PHP.
-- ============================================================
CREATE TABLE Usuarios (
    Id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(50) NOT NULL,
    Password VARCHAR(255) NOT NULL,
    NombreCompleto VARCHAR(140) NOT NULL,
    Rol ENUM('admin', 'maestro') NOT NULL,
    Activo TINYINT(1) NOT NULL DEFAULT 1,
    SessionToken CHAR(64) DEFAULT NULL,
    FechaCreacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unico_username (Username),
    INDEX idx_usuarios_session_token (SessionToken),
    INDEX idx_usuarios_rol_activo_nombre (Rol, Activo, NombreCompleto),
    INDEX idx_usuarios_nombre (NombreCompleto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- GRUPOS
-- Se normalizan: GRADO, GRUPO Y TURNO.
-- El índice único evita duplicar 1 A MATUTINO, 1 A VESPERTINO, etc.
-- ============================================================
CREATE TABLE Grupos (
    Id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Grado VARCHAR(20) NOT NULL,
    Grupo VARCHAR(10) NOT NULL,
    Turno ENUM('MATUTINO', 'VESPERTINO') NOT NULL,
    FechaCreacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unico_grupo_turno (Grado, Grupo, Turno),
    INDEX idx_grupos_busqueda_publica (Grado, Grupo, Turno),
    INDEX idx_grupos_orden (Turno, Grado, Grupo, Id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ALUMNOS
-- NombreCompleto se guarda en mayúsculas.
-- GrupoId + NombreCompleto acelera la consulta pública de padres.
-- ============================================================
CREATE TABLE Alumnos (
    Id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    NombreCompleto VARCHAR(160) NOT NULL,
    GrupoId INT UNSIGNED DEFAULT NULL,
    Activo TINYINT(1) NOT NULL DEFAULT 1,
    FechaCreacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_alumnos_grupos FOREIGN KEY (GrupoId) REFERENCES Grupos(Id) ON DELETE SET NULL ON UPDATE CASCADE,
    UNIQUE KEY unico_alumno_grupo (NombreCompleto, GrupoId),
    INDEX idx_alumnos_grupo_nombre_id (GrupoId, NombreCompleto, Id),
    INDEX idx_alumnos_busqueda_publica (GrupoId, NombreCompleto, Activo),
    INDEX idx_alumnos_nombre (NombreCompleto),
    INDEX idx_alumnos_activo (Activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ASIGNACIONES
-- Cada registro representa una materia impartida por un maestro a un grupo.
-- MateriaNombre se guarda en mayúsculas.
-- ============================================================
CREATE TABLE Asignaciones (
    Id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    MaestroId INT UNSIGNED NOT NULL,
    GrupoId INT UNSIGNED NOT NULL,
    MateriaNombre VARCHAR(140) NOT NULL,
    Activo TINYINT(1) NOT NULL DEFAULT 1,
    FechaCreacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_asignaciones_maestros FOREIGN KEY (MaestroId) REFERENCES Usuarios(Id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_asignaciones_grupos FOREIGN KEY (GrupoId) REFERENCES Grupos(Id) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY unica_asignacion (MaestroId, GrupoId, MateriaNombre),
    INDEX idx_asignaciones_maestro (MaestroId, Activo),
    INDEX idx_asignaciones_grupo (GrupoId, Activo),
    INDEX idx_asignaciones_grupo_id (GrupoId, Id),
    INDEX idx_asignaciones_grupo_materia (GrupoId, MateriaNombre, MaestroId),
    INDEX idx_asignaciones_materia (MateriaNombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CALIFICACIONES
-- Una calificación por alumno y asignación.
-- DECIMAL(4,2) permite 0.00 a 10.00.
-- ============================================================
CREATE TABLE Calificaciones (
    Id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    AlumnoId INT UNSIGNED NOT NULL,
    AsignacionId INT UNSIGNED NOT NULL,
    Calificacion DECIMAL(4,2) NOT NULL,
    FechaActualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_calificaciones_alumnos FOREIGN KEY (AlumnoId) REFERENCES Alumnos(Id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_calificaciones_asignaciones FOREIGN KEY (AsignacionId) REFERENCES Asignaciones(Id) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY unico_alumno_asignacion (AlumnoId, AsignacionId),
    INDEX idx_calificaciones_asignacion (AsignacionId, AlumnoId),
    INDEX idx_calificaciones_alumno (AlumnoId, AsignacionId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ASISTENCIAS
-- Tabla preparada para muchos registros.
-- Un alumno puede tener varias asistencias en el mismo día, una por materia.
-- FechaDia se genera automáticamente desde Fecha para buscar por día usando índices.
-- La llave única evita duplicar el mismo alumno en la misma asignación el mismo día.
-- ============================================================
CREATE TABLE Asistencias (
    Id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    AsignacionId INT UNSIGNED NOT NULL,
    AlumnoId INT UNSIGNED NOT NULL,
    Fecha DATETIME NOT NULL,
    FechaDia DATE GENERATED ALWAYS AS (DATE(Fecha)) STORED,
    Estado ENUM('A', 'F', 'R', 'J') NOT NULL,
    FechaRegistro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_asistencias_asignaciones FOREIGN KEY (AsignacionId) REFERENCES Asignaciones(Id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_asistencias_alumnos FOREIGN KEY (AlumnoId) REFERENCES Alumnos(Id) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY unico_pase_lista (AsignacionId, AlumnoId, FechaDia),
    INDEX idx_asistencias_asignacion_fecha_alumno (AsignacionId, FechaDia, AlumnoId),
    INDEX idx_asistencias_alumno_fecha_asignacion (AlumnoId, FechaDia, AsignacionId),
    INDEX idx_asistencias_fecha_asignacion_alumno (FechaDia, AsignacionId, AlumnoId),
    INDEX idx_asistencias_publica (AlumnoId, FechaDia, Estado, AsignacionId),
    INDEX idx_asistencias_estado_fecha (Estado, FechaDia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- USUARIO ADMINISTRADOR INICIAL
-- Puedes cambiar estos datos después desde el sistema.
-- ============================================================
INSERT INTO Usuarios (Username, Password, NombreCompleto, Rol, Activo)
VALUES ('Admin', 'Admin123', 'ADMINISTRADOR GENERAL', 'admin', 1);
