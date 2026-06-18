<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceImportarGruposService(PDO $Pdo, array $UserSession, int $CicloActivoImportacionId): void {
        $ErrorArchivo = ValidarArchivoImportacionSubido($_FILES['CsvGrupos'] ?? null);
        if ($ErrorArchivo !== '') {
            RedirectAdminImportar('grupos', $ErrorArchivo, true);
        }

        try {
            $Filas = LeerFilasImportacionSubida($_FILES['CsvGrupos'], ['Grupos', 'Grado y Grupo', 'Grados y Grupos']);
        } catch (Exception $E) {
            error_log('SGCE importación grupos: ' . $E->getMessage());
            RedirectAdminImportar('grupos', $E->getMessage(), true);
        }

        $ErrorFormatoPrevio = SgceValidarImportacionGruposPrevia($Filas);
        if ($ErrorFormatoPrevio !== '') {
            RedirectAdminImportar('grupos', $ErrorFormatoPrevio, true);
        }

        $Insertados = 0;
        $Reactivados = 0;
        $Duplicados = 0;
        $Invalidos = 0;
        $Saltados = 0;
        $ErroresImportacion = [];

        if ($CicloActivoImportacionId <= 0) {
            RedirectAdminImportar('grupos', 'Primero configura un ciclo escolar activo.', true);
        }

        $OfertaImportacion = SgceOfertaActiva($Pdo);
        $OfertaImportacionId = (int)($OfertaImportacion['Id'] ?? 0);
        $EtapasImportacion = $OfertaImportacionId > 0 ? SgceEtapasAcademicasListar($Pdo, $OfertaImportacionId, true) : [];
        $MapaEtapasPorOrden = [];
        $MapaEtapasPorNombre = [];
        foreach ($EtapasImportacion as $EtImp) {
            $MapaEtapasPorOrden[(int)$EtImp['Orden']] = $EtImp;
            $MapaEtapasPorNombre[SgceNormalizarMayusculas($EtImp['Nombre'])] = $EtImp;
            $MapaEtapasPorNombre[SgceNormalizarMayusculas(SgceEtapaNombreVisual($EtImp, (string)($OfertaImportacion['TipoPeriodizacion'] ?? 'ANUAL')))] = $EtImp;
        }

        try {
            $Pdo->beginTransaction();

            foreach ($Filas as $NumeroFila => $Data) {
                $Data = array_map(static fn($Valor) => trim((string)$Valor), $Data);

                if (EsFilaVacia($Data)) { continue; }

                if (count(array_filter($Data, static fn($Valor) => trim((string)$Valor) !== '')) < 3 && isset($Data[0])) {
                    $Partes = preg_split('/[;\s]+/', trim($Data[0]));
                    $Data = array_values(array_filter($Partes ?: [], static fn($Valor) => trim((string)$Valor) !== ''));
                }

                if (count($Data) < 3) {
                    $Invalidos++;
                    continue;
                }

                if (EsEncabezadoGrupo($Data)) {
                    $Saltados++;
                    continue;
                }

                $Grado = SgceNormalizarEtapaAcademica($Data[0]);
                $Grupo = SgceNormalizarGrupo($Data[1]);
                $Turno = SgceNormalizarTurno($Data[2]);
                $ProgramaId = 0;
                if (!empty($OfertaImportacion['UsaProgramas'])) {
                    $ProgramaNombre = SgceNormalizarPrograma($Data[3] ?? '');
                    if ($ProgramaNombre === '') { $Invalidos++; continue; }
                    $ProgramaId = SgceProgramaCrearOReactivar($Pdo, $ProgramaNombre, '', $OfertaImportacionId);
                } else {
                    $ProgramaId = SgceProgramaGeneralId($Pdo, $OfertaImportacionId);
                }

                $EtapaId = 0;
                if ($Grado !== '' && !empty($EtapasImportacion)) {
                    if (ctype_digit($Grado) && isset($MapaEtapasPorOrden[(int)$Grado])) { $EtapaId = (int)$MapaEtapasPorOrden[(int)$Grado]['Id']; $Grado = SgceEtapaNombreVisual($MapaEtapasPorOrden[(int)$Grado], (string)($OfertaImportacion['TipoPeriodizacion'] ?? 'ANUAL')); }
                    elseif (isset($MapaEtapasPorNombre[SgceNormalizarMayusculas($Grado)])) { $EtapaSeleccionada = $MapaEtapasPorNombre[SgceNormalizarMayusculas($Grado)]; $EtapaId = (int)$EtapaSeleccionada['Id']; $Grado = SgceEtapaNombreVisual($EtapaSeleccionada, (string)($OfertaImportacion['TipoPeriodizacion'] ?? 'ANUAL')); }
                }

                if (!SgceValidarGrado($Grado) || $Grupo === '' || $Turno === '' || (!empty($EtapasImportacion) && $EtapaId <= 0)) {
                    $Invalidos++;
                    continue;
                }

                $Antes = SgceGrupoObtenerPorCicloEstructura($Pdo, $CicloActivoImportacionId, $OfertaImportacionId, $ProgramaId, $EtapaId, $Grupo, $Turno);
                $GrupoIdNuevo = SgceGrupoCrearOReactivar($Pdo, $CicloActivoImportacionId, $Grado, $Grupo, $Turno, $EtapaId, $ProgramaId, $OfertaImportacionId);
                if ($Antes && (int)$Antes['Activo'] === 1) { $Duplicados++; }
                elseif ($Antes) { $Reactivados++; }
                else { $Insertados++; }
            }

            $Pdo->commit();
            RegistrarBitacora($Pdo, $UserSession, 'IMPORTAR_GRUPOS', 'Grupos', null, 'GRUPOS IMPORTADOS: ' . $Insertados);

        } catch (Exception $E) {
            if ($Pdo->inTransaction()) { $Pdo->rollBack(); }
            error_log('SGCE importación grupos: ' . $E->getMessage());
            RedirectAdminImportar('grupos', 'Error al importar los grupos.', true);
        }

        if ($Invalidos > 0) {
            SgceImportacionReporteAgregar($ErroresImportacion, 0, 'Resumen de filas omitidas en grupos.', [
                'INVALIDOS' => $Invalidos,
                'ENCABEZADOS_OMITIDOS' => $Saltados,
            ]);
        }
        SgceGuardarReporteImportacionFinal('grupos', [
            'Insertados' => $Insertados,
            'Reactivados' => $Reactivados,
            'Duplicados omitidos' => $Duplicados,
            'Inválidos omitidos' => $Invalidos,
            'Encabezados omitidos' => $Saltados,
        ], $ErroresImportacion);

        $Mensaje = "Se importaron $Insertados grupos correctamente.";
        if ($Reactivados > 0) { $Mensaje .= " ($Reactivados grupos reactivados)"; }
        if ($Duplicados > 0) { $Mensaje .= " ($Duplicados duplicados omitidos)"; }
        if ($Invalidos > 0) { $Mensaje .= " ($Invalidos registros inválidos omitidos)"; }
        if ($Saltados > 0) { $Mensaje .= " ($Saltados encabezados omitidos)"; }

        RedirectAdminImportar('grupos', $Mensaje);
}
