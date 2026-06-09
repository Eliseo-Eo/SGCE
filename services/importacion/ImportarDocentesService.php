<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceImportarDocentesService(PDO $Pdo, array $UserSession, int $CicloActivoImportacionId): void {
        $ErrorArchivo = ValidarArchivoImportacionSubido($_FILES['CsvDocentes'] ?? null);
        if ($ErrorArchivo !== '') {
            RedirectAdminImportar('maestros', $ErrorArchivo, true);
        }

        try {
            $Filas = LeerFilasImportacionSubida($_FILES['CsvDocentes'], ['Docentes', 'Maestros']);
        } catch (Exception $E) {
            error_log('SGCE importación docentes: ' . $E->getMessage());
            RedirectAdminImportar('maestros', $E->getMessage(), true);
        }

        $ErrorFormatoPrevio = SgceValidarImportacionDocentesPrevia($Filas);
        if ($ErrorFormatoPrevio !== '') {
            RedirectAdminImportar('maestros', $ErrorFormatoPrevio, true);
        }

        $Insertados = 0;
        $Reactivados = 0;
        $Duplicados = 0;
        $Invalidos = 0;
        $Saltados = 0;
        $Pendientes = [];
        $UsuariosArchivo = [];
        $ErroresImportacion = [];

        foreach ($Filas as $NumeroFila => $Data) {
            if (EsFilaVacia($Data)) { continue; }
            if (EsEncabezadoDocente($Data)) { $Saltados++; continue; }

            if (!isset($Data[0], $Data[1], $Data[2])) {
                $Invalidos++;
                continue;
            }

            $Nombre = SgceNormalizarNombre($Data[0]);
            $User = trim((string)$Data[1]);
            $Pass = trim((string)$Data[2]);

            if ($Nombre === '' || $User === '' || $Pass === '' || SgceValidarPasswordFuerte($Pass) !== true) {
                $Invalidos++;
                continue;
            }

            if (!preg_match('/^[a-zA-Z0-9._@-]{3,80}$/', $User)) {
                $Invalidos++;
                continue;
            }

            if (isset($UsuariosArchivo[$User])) {
                $Duplicados++;
                continue;
            }

            $UsuariosArchivo[$User] = true;
            $Pendientes[] = [
                'Nombre' => $Nombre,
                'Username' => $User,
                'Password' => $Pass,
            ];
        }

        try {
            $Existentes = UsuariosExistentesPorUsername($Pdo, array_column($Pendientes, 'Username'));
            $StmtReactivar = $Pdo->prepare("UPDATE Usuarios SET Password = ?, NombreCompleto = ?, NombreBusqueda = ?, Rol = 'maestro', Activo = 1, SessionToken = NULL, SessionTokenExpira = NULL WHERE Id = ? AND Rol = 'maestro'");
            $Stmt = $Pdo->prepare("INSERT INTO Usuarios (Username, Password, NombreCompleto, NombreBusqueda, Rol, Activo) VALUES (?, ?, ?, ?, 'maestro', 1)");
            $HashCache = [];

            $Pdo->beginTransaction();

            foreach ($Pendientes as $Docente) {
                $UsuarioExistente = $Existentes[$Docente['Username']] ?? null;
                $PasswordHash = HashPasswordImportacion($HashCache, $Docente['Password']);

                if ($UsuarioExistente) {
                    if ((string)$UsuarioExistente['Rol'] !== 'maestro' || (int)$UsuarioExistente['Activo'] === 1) {
                        $Duplicados++;
                        continue;
                    }

                    $StmtReactivar->execute([
                        $PasswordHash,
                        $Docente['Nombre'],
                        SgceTextoBusquedaNormalizado($Docente['Nombre']),
                        (int)$UsuarioExistente['Id'],
                    ]);
                    SgcePrepararCarpetaDocentePlaneaciones((int)$UsuarioExistente['Id'], $Docente['Username']);
                    $Reactivados++;
                    continue;
                }

                $Stmt->execute([
                    $Docente['Username'],
                    $PasswordHash,
                    $Docente['Nombre'],
                    SgceTextoBusquedaNormalizado($Docente['Nombre']),
                ]);
                SgcePrepararCarpetaDocentePlaneaciones((int)$Pdo->lastInsertId(), $Docente['Username']);
                $Insertados++;
            }

            $Pdo->commit();
            RegistrarBitacora($Pdo, $UserSession, 'IMPORTAR_DOCENTES', 'Usuarios', null, 'DOCENTES IMPORTADOS: ' . $Insertados);

        } catch (Exception $E) {
            if ($Pdo->inTransaction()) { $Pdo->rollBack(); }
            error_log('SGCE importación docentes: ' . $E->getMessage());
            RedirectAdminImportar('maestros', 'Error al importar los docentes.', true);
        }

        if ($Invalidos > 0) {
            SgceImportacionReporteAgregar($ErroresImportacion, 0, 'Resumen de filas omitidas en docentes.', [
                'INVALIDOS' => $Invalidos,
                'DUPLICADOS' => $Duplicados,
                'ENCABEZADOS_OMITIDOS' => $Saltados,
            ]);
        }
        SgceGuardarReporteImportacionFinal('docentes', [
            'Insertados' => $Insertados,
            'Reactivados' => $Reactivados,
            'Duplicados omitidos' => $Duplicados,
            'Inválidos omitidos' => $Invalidos,
            'Encabezados omitidos' => $Saltados,
        ], $ErroresImportacion);

        $Mensaje = "Se importaron $Insertados docentes correctamente.";
        if ($Reactivados > 0) { $Mensaje .= " ($Reactivados docentes reactivados)"; }
        if ($Duplicados > 0) { $Mensaje .= " ($Duplicados usuarios duplicados omitidos)"; }
        if ($Invalidos > 0) { $Mensaje .= " ($Invalidos registros inválidos omitidos)"; }
        if ($Saltados > 0) { $Mensaje .= " ($Saltados encabezados omitidos)"; }

        RedirectAdminImportar('maestros', $Mensaje);
}
