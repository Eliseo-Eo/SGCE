<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceImportarMateriasService(PDO $Pdo, array $UserSession, int $CicloActivoImportacionId): void {
        $ErrorArchivo = ValidarArchivoImportacionSubido($_FILES['CsvMaterias'] ?? null);
        if ($ErrorArchivo !== '') {
            RedirectAdminImportar('materias', $ErrorArchivo, true);
        }

        try {
            $Filas = LeerFilasImportacionSubida($_FILES['CsvMaterias'], ['Materias', 'Asignaturas']);
        } catch (Exception $E) {
            error_log('SGCE importación materias: ' . $E->getMessage());
            RedirectAdminImportar('materias', $E->getMessage(), true);
        }

        if ($CicloActivoImportacionId <= 0) {
            RedirectAdminImportar('materias', 'Primero configura un ciclo escolar activo.', true);
        }

        $OfertaImportacion = SgceOfertaActiva($Pdo);
        $OfertaImportacionId = (int)($OfertaImportacion['Id'] ?? 0);
        if ($OfertaImportacionId <= 0) {
            RedirectAdminImportar('materias', 'Primero configura la estructura académica.', true);
        }

        $EtapasImportacion = SgceEtapasAcademicasListar($Pdo, $OfertaImportacionId, true);
        $MapaEtapasPorOrden = [];
        $MapaEtapasPorNombre = [];
        foreach ($EtapasImportacion as $EtImp) {
            $MapaEtapasPorOrden[(int)$EtImp['Orden']] = $EtImp;
            $MapaEtapasPorNombre[SgceNormalizarMayusculas($EtImp['Nombre'])] = $EtImp;
            $MapaEtapasPorNombre[SgceNormalizarMayusculas(SgceEtapaNombreVisual($EtImp, (string)($OfertaImportacion['TipoPeriodizacion'] ?? 'ANUAL')))] = $EtImp;
        }

        $Insertados = 0;
        $Reactivados = 0;
        $Duplicados = 0;
        $Invalidos = 0;
        $Saltados = 0;
        $FormatoIncompleto = 0;
        $SinGrupoTurno = 0;
        $GrupoNoEncontrado = 0;
        $EtapaNoEncontrada = 0;
        $HorasInvalidas = 0;
        $ErroresImportacion = [];

        try {
            $Pdo->beginTransaction();

            foreach ($Filas as $NumeroFila => $Data) {
                $Data = array_map(static fn($Valor) => trim((string)$Valor), $Data);
                if (EsFilaVacia($Data)) { continue; }
                if (EsEncabezadoMateriaGrupo($Data)) { $Saltados++; continue; }

                $ColumnasConDato = count(array_values(array_filter($Data, static fn($Valor) => trim((string)$Valor) !== '')));
                if ($ColumnasConDato < 5) {
                    $FormatoIncompleto++;
                    $Invalidos++;
                    continue;
                }

                $Materia = SgceNormalizarMayusculas($Data[0] ?? '');
                $EtapaTxt = SgceNormalizarEtapaAcademica($Data[1] ?? '');
                $GrupoTxt = SgceNormalizarGrupo($Data[2] ?? '');
                $Turno = SgceNormalizarTurno($Data[3] ?? '');
                $Horas = SgceHorasMateriaSeguro($Data[4] ?? 0);

                if ($Materia === '' || $EtapaTxt === '') { $Invalidos++; continue; }
                if ($GrupoTxt === '' || $Turno === '') { $SinGrupoTurno++; $Invalidos++; continue; }
                if ($Horas <= 0) { $HorasInvalidas++; $Invalidos++; continue; }

                $ProgramaId = 0;
                if (!empty($OfertaImportacion['UsaProgramas'])) {
                    $ProgramaNombre = SgceNormalizarPrograma($Data[5] ?? '');
                    if ($ProgramaNombre === '') { $Invalidos++; continue; }
                    $ProgramaId = SgceProgramaCrearOReactivar($Pdo, $ProgramaNombre, '', $OfertaImportacionId);
                } else {
                    $ProgramaId = SgceProgramaGeneralId($Pdo, $OfertaImportacionId);
                }

                $EtapaId = SgceResolverEtapaImportacionMateria($EtapaTxt, $MapaEtapasPorOrden, $MapaEtapasPorNombre);
                if ($EtapaId <= 0) { $EtapaNoEncontrada++; $Invalidos++; continue; }

                $GrupoRow = SgceGrupoObtenerPorCicloEstructura($Pdo, $CicloActivoImportacionId, $OfertaImportacionId, $ProgramaId, $EtapaId, $GrupoTxt, $Turno);
                if (!$GrupoRow || (int)$GrupoRow['Activo'] !== 1) { $GrupoNoEncontrado++; $Invalidos++; continue; }
                $MateriaId = SgceMateriaIdPorNombre($Pdo, $Materia);
                $Existia = false;
                $StmtExiste = $Pdo->prepare('SELECT Id, Activo FROM MateriasGrupo WHERE CicloId = ? AND GrupoId = ? AND MateriaId = ? LIMIT 1');
                $StmtExiste->execute([$CicloActivoImportacionId, (int)$GrupoRow['Id'], $MateriaId]);
                $Existente = $StmtExiste->fetch();
                if ($Existente) { $Existia = true; }
                $MateriaGrupoId = SgceMateriaGrupoCrearOReactivar($Pdo, (int)$GrupoRow['Id'], $Materia, $Horas, $CicloActivoImportacionId);
                if ($Existia) {
                    if ((int)($Existente['Activo'] ?? 0) === 1) { $Duplicados++; }
                    else { $Reactivados++; }
                } else {
                    $Insertados++;
                }
            }

            $Pdo->commit();
            RegistrarBitacora($Pdo, $UserSession, 'IMPORTAR_MATERIAS_GRUPO', 'MateriasGrupo', null, 'MATERIAS DE GRUPO IMPORTADAS: ' . $Insertados);
        } catch (Exception $E) {
            if ($Pdo->inTransaction()) { $Pdo->rollBack(); }
            error_log('SGCE importación materias: ' . $E->getMessage());
            RedirectAdminImportar('materias', $E->getMessage(), true);
        }

        if (($Insertados + $Reactivados + $Duplicados) <= 0 && $FormatoIncompleto > 0) {
            RedirectAdminImportar('materias', SgceMensajeFormatoMateriasGrupo(), true);
        }

        if (($Insertados + $Reactivados + $Duplicados) <= 0 && $SinGrupoTurno > 0) {
            RedirectAdminImportar('materias', 'No se importó ninguna materia. Revisa que GRUPO sea una letra válida y que TURNO sea MATUTINO o VESPERTINO.', true);
        }

        if ($Invalidos > 0) {
            SgceImportacionReporteAgregar($ErroresImportacion, 0, 'Resumen de filas omitidas en materias.', [
                'INVALIDOS' => $Invalidos,
                'FORMATO_INCOMPLETO' => $FormatoIncompleto,
                'SIN_GRUPO_TURNO' => $SinGrupoTurno,
                'GRUPO_NO_ENCONTRADO' => $GrupoNoEncontrado,
                'ETAPA_NO_ENCONTRADA' => $EtapaNoEncontrada,
                'HORAS_INVALIDAS' => $HorasInvalidas,
                'ENCABEZADOS_OMITIDOS' => $Saltados,
            ]);
        }
        SgceGuardarReporteImportacionFinal('materias', [
            'Insertados' => $Insertados,
            'Reactivados' => $Reactivados,
            'Duplicados omitidos' => $Duplicados,
            'Inválidos omitidos' => $Invalidos,
            'Encabezados omitidos' => $Saltados,
        ], $ErroresImportacion);

        $Mensaje = "Se importaron $Insertados materias correctamente.";
        if ($Reactivados > 0) { $Mensaje .= " ($Reactivados materias reactivadas)"; }
        if ($Duplicados > 0) { $Mensaje .= " ($Duplicados duplicados omitidos)"; }
        if ($Invalidos > 0) { $Mensaje .= " ($Invalidos registros inválidos omitidos)"; }
        if ($GrupoNoEncontrado > 0) { $Mensaje .= " ($GrupoNoEncontrado filas no coincidieron con un grupo activo)"; }
        if ($EtapaNoEncontrada > 0) { $Mensaje .= " ($EtapaNoEncontrada filas tenían año/etapa no configurado)"; }
        if ($HorasInvalidas > 0) { $Mensaje .= " ($HorasInvalidas filas tenían horas inválidas)"; }
        if ($Saltados > 0) { $Mensaje .= " ($Saltados encabezados omitidos)"; }
        RedirectAdminImportar('materias', $Mensaje);
}
