<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceImportarAlumnosService(PDO $Pdo, array $UserSession, int $CicloActivoImportacionId): void {
        $GrupoId = intval($_POST['GrupoId'] ?? 0);

        $ErrorArchivo = ValidarArchivoImportacionSubido($_FILES['CsvAlumnos'] ?? null);
        if ($ErrorArchivo !== '') {
            RedirectAdminImportar('alumnos', $ErrorArchivo, true);
        }

        try {
            $Filas = LeerFilasImportacionSubida($_FILES['CsvAlumnos'], ['Alumnos', 'Padron de Alumnos', 'Padrón de Alumnos']);
        } catch (Exception $E) {
            error_log('SGCE importación alumnos: ' . $E->getMessage());
            RedirectAdminImportar('alumnos', $E->getMessage(), true);
        }

        $Insertados = 0;
        $Reactivados = 0;
        $Duplicados = 0;
        $Invalidos = 0;
        $Saltados = 0;
        $GrupoNoEncontrado = 0;
        $SinGrupoDestino = 0;
        $ErroresImportacion = [];

        if ($CicloActivoImportacionId <= 0) {
            RedirectAdminImportar('alumnos', 'Primero configura un ciclo escolar activo.', true);
        }

        $OfertaImportacion = SgceOfertaActiva($Pdo);
        $OfertaImportacionId = (int)($OfertaImportacion['Id'] ?? 0);
        if ($OfertaImportacionId <= 0) {
            RedirectAdminImportar('alumnos', 'Primero configura la estructura académica.', true);
        }

        $EtapasImportacion = SgceEtapasAcademicasListar($Pdo, $OfertaImportacionId, true);
        $MapaEtapasPorOrden = [];
        $MapaEtapasPorNombre = [];
        foreach ($EtapasImportacion as $EtImp) {
            $MapaEtapasPorOrden[(int)$EtImp['Orden']] = $EtImp;
            $MapaEtapasPorNombre[SgceNormalizarMayusculas($EtImp['Nombre'])] = $EtImp;
            $MapaEtapasPorNombre[SgceNormalizarMayusculas(SgceEtapaNombreVisual($EtImp, (string)($OfertaImportacion['TipoPeriodizacion'] ?? 'ANUAL')))] = $EtImp;
        }

        $GrupoSeleccionadoValido = false;
        if ($GrupoId > 0) {
            $CheckGrupo = $Pdo->prepare("SELECT COUNT(*) FROM Grupos WHERE Id = ? AND CicloId = ? AND Activo = 1");
            $CheckGrupo->execute([$GrupoId, $CicloActivoImportacionId]);
            $GrupoSeleccionadoValido = (int)$CheckGrupo->fetchColumn() > 0;
            if (!$GrupoSeleccionadoValido) {
                RedirectAdminImportar('alumnos', 'El grupo seleccionado no existe en el ciclo activo.', true);
            }
        }

        $Check = $Pdo->prepare("SELECT A.Id, A.Activo FROM Alumnos A INNER JOIN AlumnoInscripciones AI ON AI.AlumnoId = A.Id WHERE A.NombreCompleto = ? AND AI.CicloId = ? AND AI.GrupoId = ? AND AI.Estado = 'INSCRITO' LIMIT 1");
        $CheckBase = $Pdo->prepare("SELECT Id, Activo FROM Alumnos WHERE NombreCompleto = ? AND GrupoId = ? LIMIT 1");
        $StmtReactivar = $Pdo->prepare("UPDATE Alumnos SET Activo = 1, GrupoId = ?, NombreBusqueda = NombreCompleto WHERE Id = ?");
        $Stmt = $Pdo->prepare("INSERT INTO Alumnos (NombreCompleto, NombreBusqueda, GrupoId, Activo) VALUES (?, ?, ?, 1)");

        try {
            $Pdo->beginTransaction();

            foreach ($Filas as $NumeroFila => $Data) {
                $Data = array_map(static fn($Valor) => trim((string)$Valor), $Data);
                if (EsFilaVacia($Data)) { continue; }
                if (EsEncabezadoAlumno($Data)) { $Saltados++; continue; }

                $Nombre = SgceNormalizarNombre($Data[0] ?? '');
                if ($Nombre === '') {
                    $Invalidos++;
                    continue;
                }

                $GrupoDestinoId = $GrupoId;
                if (SgceFilaAlumnoTraeGrupo($Data)) {
                    $EtapaTxt = SgceNormalizarEtapaAcademica($Data[1] ?? '');
                    $GrupoTxt = SgceNormalizarGrupo($Data[2] ?? '');
                    $Turno = SgceNormalizarTurno($Data[3] ?? '');
                    $EtapaId = SgceResolverEtapaImportacionMateria($EtapaTxt, $MapaEtapasPorOrden, $MapaEtapasPorNombre);

                    if ($EtapaId <= 0 || $GrupoTxt === '' || $Turno === '') {
                        $GrupoNoEncontrado++;
                        $Invalidos++;
                        continue;
                    }

                    $ProgramaId = 0;
                    if (!empty($OfertaImportacion['UsaProgramas'])) {
                        $ProgramaNombre = SgceNormalizarPrograma($Data[4] ?? '');
                        if ($ProgramaNombre === '') { $Invalidos++; continue; }
                        $ProgramaId = SgceProgramaCrearOReactivar($Pdo, $ProgramaNombre, '', $OfertaImportacionId);
                    } else {
                        $ProgramaId = SgceProgramaGeneralId($Pdo, $OfertaImportacionId);
                    }

                    $GrupoRow = SgceGrupoObtenerPorCicloEstructura($Pdo, $CicloActivoImportacionId, $OfertaImportacionId, $ProgramaId, $EtapaId, $GrupoTxt, $Turno);
                    if (!$GrupoRow || (int)$GrupoRow['Activo'] !== 1) {
                        $GrupoNoEncontrado++;
                        $Invalidos++;
                        continue;
                    }
                    $GrupoDestinoId = (int)$GrupoRow['Id'];
                }

                if ($GrupoDestinoId <= 0) {
                    $SinGrupoDestino++;
                    $Invalidos++;
                    continue;
                }

                $Check->execute([$Nombre, $CicloActivoImportacionId, $GrupoDestinoId]);
                $AlumnoExistente = $Check->fetch();
                if ($AlumnoExistente) {
                    $Duplicados++;
                    continue;
                }

                $CheckBase->execute([$Nombre, $GrupoDestinoId]);
                $AlumnoBase = $CheckBase->fetch();
                if ($AlumnoBase) {
                    $AlumnoId = (int)$AlumnoBase['Id'];
                    $StmtReactivar->execute([$GrupoDestinoId, $AlumnoId]);
                    SgceAlumnoInscribirEnCiclo($Pdo, $AlumnoId, $CicloActivoImportacionId, $GrupoDestinoId, 'INSCRITO');
                    $Reactivados++;
                    continue;
                }

                $Stmt->execute([$Nombre, SgceTextoBusquedaNormalizado($Nombre), $GrupoDestinoId]);
                SgceAlumnoInscribirEnCiclo($Pdo, (int)$Pdo->lastInsertId(), $CicloActivoImportacionId, $GrupoDestinoId, 'INSCRITO');
                $Insertados++;
            }

            $Pdo->commit();
            RegistrarBitacora($Pdo, $UserSession, 'IMPORTAR_ALUMNOS', 'Alumnos', null, 'ALUMNOS IMPORTADOS: ' . $Insertados);

        } catch (Exception $E) {
            if ($Pdo->inTransaction()) { $Pdo->rollBack(); }
            error_log('SGCE importación alumnos: ' . $E->getMessage());
            RedirectAdminImportar('alumnos', 'Error al importar los alumnos.', true);
        }

        if (($Insertados + $Reactivados + $Duplicados) <= 0 && $SinGrupoDestino > 0) {
            RedirectAdminImportar('alumnos', 'No se importó ningún alumno. Selecciona un grupo destino o usa una hoja Alumnos con columnas: NOMBRE COMPLETO, AÑO, GRUPO, TURNO.', true);
        }

        if ($Invalidos > 0 || $GrupoNoEncontrado > 0 || $SinGrupoDestino > 0) {
            SgceImportacionReporteAgregar($ErroresImportacion, 0, 'Resumen de filas omitidas en alumnos.', [
                'INVALIDOS' => $Invalidos,
                'GRUPO_NO_ENCONTRADO' => $GrupoNoEncontrado,
                'SIN_GRUPO_DESTINO' => $SinGrupoDestino,
                'ENCABEZADOS_OMITIDOS' => $Saltados,
            ]);
        }
        SgceGuardarReporteImportacionFinal('alumnos', [
            'Insertados' => $Insertados,
            'Reactivados' => $Reactivados,
            'Duplicados omitidos' => $Duplicados,
            'Inválidos omitidos' => $Invalidos,
            'Encabezados omitidos' => $Saltados,
        ], $ErroresImportacion);

        $Mensaje = "Se importaron $Insertados alumnos correctamente.";
        if ($Reactivados > 0) { $Mensaje .= " ($Reactivados alumnos reactivados)"; }
        if ($Duplicados > 0) { $Mensaje .= " ($Duplicados duplicados omitidos)"; }
        if ($Invalidos > 0) { $Mensaje .= " ($Invalidos registros inválidos omitidos)"; }
        if ($GrupoNoEncontrado > 0) { $Mensaje .= " ($GrupoNoEncontrado filas no coincidieron con un grupo activo)"; }
        if ($Saltados > 0) { $Mensaje .= " ($Saltados encabezados/títulos omitidos)"; }

        RedirectAdminImportar('alumnos', $Mensaje);
}
