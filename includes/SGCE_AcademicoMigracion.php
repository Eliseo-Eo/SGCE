<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

// Funciones de promoción y migración académica de ciclo.

function SgceMigrarGrupoSiguienteCiclo(PDO $Pdo, int $GrupoOrigenId, int $CicloDestinoId, bool $CopiarAsignaciones = false): array {
    $TransaccionPropia = !$Pdo->inTransaction();
    if ($TransaccionPropia) { $Pdo->beginTransaction(); }
    try {
        $Origen = SgceGrupoObtenerPorId($Pdo, $GrupoOrigenId);
        $DestinoCiclo = SgceCicloPorId($Pdo, $CicloDestinoId);
        if (!$Origen) { throw new RuntimeException('El grupo origen no existe.'); }
        if (!$DestinoCiclo || (int)$DestinoCiclo['Activo'] !== 1) { throw new RuntimeException('Debe existir un ciclo destino activo.'); }
        if ((int)$Origen['CicloId'] === $CicloDestinoId) { throw new RuntimeException('El grupo origen ya pertenece al ciclo activo.'); }
        if ((int)$Origen['CicloActivo'] === 1) { throw new RuntimeException('No se puede migrar un grupo de un ciclo que todavía está activo. Primero crea/activa el nuevo ciclo.'); }

        $StmtLockGrupoOrigen = $Pdo->prepare('SELECT Id FROM Grupos WHERE Id = ? FOR UPDATE');
        $StmtLockGrupoOrigen->execute([$GrupoOrigenId]);
        $StmtLockCicloDestino = $Pdo->prepare('SELECT Id FROM CiclosEscolares WHERE Id = ? AND Activo = 1 FOR UPDATE');
        $StmtLockCicloDestino->execute([$CicloDestinoId]);

        $Resultado = [
        'GrupoOrigen' => $Origen,
        'GrupoDestinoId' => null,
        'NuevoGrado' => null,
        'Promovidos' => 0,
        'Egresados' => 0,
        'Omitidos' => 0,
        'Conflictos' => 0,
        'AsignacionesCopiadas' => 0,
        'AsignacionesOmitidasDocente' => 0,
        'KardexCongelados' => 0,
        'GrupoCreado' => false,
        ];

        $Alumnos = SgceAlumnosPorGrupoCiclo($Pdo, $GrupoOrigenId, (int)$Origen['CicloId'], ['INSCRITO']);
        $EtapaOrigenId = (int)($Origen['EtapaId'] ?? 0);
        $EtapaSiguiente = $EtapaOrigenId > 0 ? SgceEtapaSiguiente($Pdo, $EtapaOrigenId) : null;


        if (!$EtapaSiguiente) {
            $StmtEgresar = $Pdo->prepare("UPDATE AlumnoInscripciones SET Estado = 'EGRESADO' WHERE AlumnoId = ? AND CicloId = ? AND GrupoId = ?");
            $StmtAlumnoNull = $Pdo->prepare('UPDATE Alumnos SET GrupoId = NULL WHERE Id = ? AND GrupoId = ?');
            foreach ($Alumnos as $Alumno) {
                $StmtEgresar->execute([(int)$Alumno['Id'], (int)$Origen['CicloId'], $GrupoOrigenId]);
                if (SgceKardexCongelarAlumnoCiclo($Pdo, (int)$Alumno['Id'], (int)$Origen['CicloId'], 0, true)) { $Resultado['KardexCongelados']++; }
                $StmtAlumnoNull->execute([(int)$Alumno['Id'], $GrupoOrigenId]);
                $Resultado['Egresados']++;
            }
            if ($TransaccionPropia) { $Pdo->commit(); }
            return $Resultado;
        }

        $NuevoGrado = (string)$EtapaSiguiente['Nombre'];
        $OfertaId = (int)($EtapaSiguiente['OfertaId'] ?? ($Origen['OfertaId'] ?? 0));
        $ProgramaId = (int)($Origen['ProgramaId'] ?? 0);
        if ($ProgramaId <= 0) { $ProgramaId = SgceProgramaGeneralId($Pdo, $OfertaId); }
        $EtapaDestinoId = (int)($EtapaSiguiente['Id'] ?? 0);
        if ($EtapaDestinoId <= 0) { throw new RuntimeException('La etapa académica destino no es válida. Revisa la estructura académica.'); }
        $GrupoExistente = SgceGrupoObtenerPorCicloEstructura($Pdo, $CicloDestinoId, $OfertaId, $ProgramaId, $EtapaDestinoId, (string)$Origen['Grupo'], (string)$Origen['Turno']);
        $GrupoDestinoId = SgceGrupoCrearOReactivar($Pdo, $CicloDestinoId, $NuevoGrado, (string)$Origen['Grupo'], (string)$Origen['Turno'], $EtapaDestinoId, $ProgramaId, $OfertaId);
        $Resultado['GrupoDestinoId'] = $GrupoDestinoId;
        $Resultado['NuevoGrado'] = $NuevoGrado;
        $Resultado['GrupoCreado'] = !$GrupoExistente;

        $StmtPromoverOrigen = $Pdo->prepare("UPDATE AlumnoInscripciones SET Estado = 'PROMOVIDO' WHERE AlumnoId = ? AND CicloId = ? AND GrupoId = ?");
        $StmtActualizarAlumno = $Pdo->prepare('UPDATE Alumnos SET GrupoId = ?, Activo = 1 WHERE Id = ?');
        foreach ($Alumnos as $Alumno) {
            $AlumnoId = (int)$Alumno['Id'];
            if (SgceAlumnoTieneInscripcion($Pdo, $AlumnoId, $CicloDestinoId)) {
                $Resultado['Conflictos']++;
                continue;
            }
            if (SgceAlumnoInscribirEnCiclo($Pdo, $AlumnoId, $CicloDestinoId, $GrupoDestinoId, 'INSCRITO')) {
                $StmtPromoverOrigen->execute([$AlumnoId, (int)$Origen['CicloId'], $GrupoOrigenId]);
                if (SgceKardexCongelarAlumnoCiclo($Pdo, $AlumnoId, (int)$Origen['CicloId'], 0, true)) { $Resultado['KardexCongelados']++; }
                $StmtActualizarAlumno->execute([$GrupoDestinoId, $AlumnoId]);
                $Resultado['Promovidos']++;
            } else {
            $Resultado['Omitidos']++;
        }
    }

    if ($CopiarAsignaciones) {
        $StmtAsignaciones = $Pdo->prepare("SELECT A.MaestroId, A.MateriaNombre, A.MateriaId, A.HorasSemana, U.Activo AS MaestroActivo
        FROM Asignaciones A
        INNER JOIN Usuarios U ON U.Id = A.MaestroId AND U.Rol = 'maestro'
        WHERE A.CicloId = ? AND A.GrupoId = ? AND A.Activo = 1");
        $StmtAsignaciones->execute([(int)$Origen['CicloId'], $GrupoOrigenId]);
        $StmtInsertAsignacion = $Pdo->prepare('INSERT IGNORE INTO Asignaciones (CicloId, MaestroId, GrupoId, MateriaGrupoId, MateriaId, MateriaNombre, MateriaBusqueda, HorasSemana, Activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)');
        foreach ($StmtAsignaciones->fetchAll() as $Asig) {
            if ((int)$Asig['MaestroActivo'] !== 1) {
                $Resultado['AsignacionesOmitidasDocente']++;
                continue;
            }
            $MateriaNombre = (string)$Asig['MateriaNombre'];
            $HorasSemana = SgceHorasMateriaSeguro($Asig['HorasSemana'] ?? 0) ?: 1;
            $MateriaGrupoDestinoId = SgceMateriaGrupoCrearOReactivar($Pdo, $GrupoDestinoId, $MateriaNombre, $HorasSemana, $CicloDestinoId);
            $MateriaDestino = SgceMateriaGrupoObtener($Pdo, $MateriaGrupoDestinoId);
            if (!$MateriaDestino) { continue; }
            $MateriaBusquedaDestino = function_exists('SgceTextoBusquedaNormalizado') ? SgceTextoBusquedaNormalizado((string)$MateriaDestino['MateriaNombre']) : (string)$MateriaDestino['MateriaNombre'];
            $StmtInsertAsignacion->execute([$CicloDestinoId, (int)$Asig['MaestroId'], $GrupoDestinoId, $MateriaGrupoDestinoId, (int)$MateriaDestino['MateriaId'], (string)$MateriaDestino['MateriaNombre'], $MateriaBusquedaDestino, (int)$MateriaDestino['HorasSemana']]);
            if ($StmtInsertAsignacion->rowCount() > 0) {
                $NuevaAsignacionId = (int)$Pdo->lastInsertId();
                SgceRegistrarDocenteAsignacionActual($Pdo, $NuevaAsignacionId, (int)$Asig['MaestroId'], 0, 'TITULAR', 'ASIGNACIÓN COPIADA AL NUEVO CICLO');
                $Resultado['AsignacionesCopiadas']++;
            }
        }
    }

    if ($TransaccionPropia) { $Pdo->commit(); }
    return $Resultado;
} catch (Throwable $E) {
if ($TransaccionPropia && $Pdo->inTransaction()) { $Pdo->rollBack(); }
throw $E;
}
}

function SgceMigrarCicloCompleto(PDO $Pdo, int $CicloOrigenId, int $CicloDestinoId, bool $CopiarAsignaciones = false): array {
    $Origen = SgceCicloPorId($Pdo, $CicloOrigenId);
    $Destino = SgceCicloPorId($Pdo, $CicloDestinoId);
    if (!$Origen || (int)$Origen['Activo'] === 1) { throw new RuntimeException('Selecciona un ciclo origen cerrado/inactivo.'); }
    if (!$Destino || (int)$Destino['Activo'] !== 1) { throw new RuntimeException('Debe existir un ciclo destino activo.'); }
    if ($CicloOrigenId === $CicloDestinoId) { throw new RuntimeException('El ciclo origen y destino no pueden ser el mismo.'); }
    $Resumen = ['GruposProcesados' => 0, 'Promovidos' => 0, 'Egresados' => 0, 'Conflictos' => 0, 'Omitidos' => 0, 'AsignacionesCopiadas' => 0, 'AsignacionesOmitidasDocente' => 0, 'KardexCongelados' => 0, 'GruposCreados' => 0];
    foreach (SgceGruposListarPorCiclo($Pdo, $CicloOrigenId, true) as $Grupo) {
        $R = SgceMigrarGrupoSiguienteCiclo($Pdo, (int)$Grupo['Id'], $CicloDestinoId, $CopiarAsignaciones);
        $Resumen['GruposProcesados']++;
        $Resumen['Promovidos'] += (int)$R['Promovidos'];
        $Resumen['Egresados'] += (int)$R['Egresados'];
        $Resumen['Conflictos'] += (int)$R['Conflictos'];
        $Resumen['Omitidos'] += (int)$R['Omitidos'];
        $Resumen['AsignacionesCopiadas'] += (int)$R['AsignacionesCopiadas'];
        $Resumen['AsignacionesOmitidasDocente'] += (int)($R['AsignacionesOmitidasDocente'] ?? 0);
        $Resumen['KardexCongelados'] += (int)($R['KardexCongelados'] ?? 0);
        $Resumen['GruposCreados'] += !empty($R['GrupoCreado']) ? 1 : 0;
    }
    return $Resumen;
}

