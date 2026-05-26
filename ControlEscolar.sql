DROP DATABASE IF EXISTS ControlEscolar;
CREATE DATABASE ControlEscolar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ControlEscolar;

CREATE TABLE Usuarios (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(50) NOT NULL UNIQUE,
    Password VARCHAR(255) NOT NULL,
    NombreCompleto VARCHAR(100) NOT NULL,
    Rol ENUM('admin', 'maestro') NOT NULL,
    Activo TINYINT(1) DEFAULT 1,
    SessionToken VARCHAR(64) DEFAULT NULL
);

CREATE TABLE Grupos (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    Grado VARCHAR(20) NOT NULL,
    Grupo VARCHAR(5) NOT NULL,
    Turno ENUM('Matutino', 'Vespertino') NOT NULL
);

CREATE TABLE Alumnos (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    NombreCompleto VARCHAR(100) NOT NULL,
    GrupoId INT,
    FOREIGN KEY (GrupoId) REFERENCES Grupos(Id) ON DELETE SET NULL
);

CREATE TABLE Asignaciones (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    MaestroId INT,
    GrupoId INT,
    MateriaNombre VARCHAR(100) NOT NULL,
    FOREIGN KEY (MaestroId) REFERENCES Usuarios(Id) ON DELETE CASCADE,
    FOREIGN KEY (GrupoId) REFERENCES Grupos(Id) ON DELETE CASCADE
);

CREATE TABLE Calificaciones (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    AlumnoId INT,
    AsignacionId INT,
    Calificacion DECIMAL(4,2) NOT NULL,
    FOREIGN KEY (AlumnoId) REFERENCES Alumnos(Id) ON DELETE CASCADE,
    FOREIGN KEY (AsignacionId) REFERENCES Asignaciones(Id) ON DELETE CASCADE,
    UNIQUE KEY AlumnoAsignacion (AlumnoId, AsignacionId)
);

CREATE TABLE Asistencias (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    AsignacionId INT NOT NULL,
    AlumnoId INT NOT NULL,
    Fecha DATETIME NOT NULL,
    Estado ENUM('A', 'F', 'R', 'J') NOT NULL,
    FOREIGN KEY (AsignacionId) REFERENCES Asignaciones(Id) ON DELETE CASCADE,
    FOREIGN KEY (AlumnoId) REFERENCES Alumnos(Id) ON DELETE CASCADE,
    -- ESTO ES LO QUE HACE LA MAGIA:
    -- Impedimos que un alumno tenga dos registros para la misma materia el mismo día.
    UNIQUE KEY registro_diario (AsignacionId, AlumnoId, (DATE(Fecha)))
);

INSERT INTO Usuarios (Username, Password, NombreCompleto, Rol, Activo) 
VALUES ('Admin', 'Admin123', 'Administrador General', 'Admin', 1);