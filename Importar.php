<?php

require 'Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);

if (
    !$UserSession
    ||
    $UserSession['Rol'] !== 'admin'
) {

    header('Location: index.php');

    exit;
}

$Error = '';

// IMPORTAR

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // =====================================================
    // IMPORTAR ALUMNOS
    // =====================================================

    if (isset($_POST['ImportarAlumnos'])) {

        $GrupoId = intval($_POST['GrupoId'] ?? 0);

        if ($GrupoId <= 0) {

            $Error = "Por Favor, Selecciona Un Grupo.";

        } elseif (
            isset($_FILES['CsvAlumnos'])
            &&
            $_FILES['CsvAlumnos']['error'] == 0
        ) {

            // VALIDAR EXTENSIÓN

            $Extension = strtolower(
                pathinfo(
                    $_FILES['CsvAlumnos']['name'],
                    PATHINFO_EXTENSION
                )
            );

            if ($Extension !== 'csv') {

                $Error = "Solo Se Permiten Archivos CSV.";

            } else {

                $File = $_FILES['CsvAlumnos']['tmp_name'];

                $Handle = fopen($File, "r");

                if (!$Handle) {

                    $Error = "No Se Pudo Leer El Archivo.";

                } else {

                    BomStrip($Handle);

                    $Insertados = 0;

                    $Duplicados = 0;

                    // VALIDAR DUPLICADOS

                    $Check = $Pdo->prepare("
                        SELECT COUNT(*)
                        FROM Alumnos
                        WHERE NombreCompleto = ?
                        AND GrupoId = ?
                    ");

                    // INSERTAR

                    $Stmt = $Pdo->prepare("
                        INSERT INTO Alumnos
                        (
                            NombreCompleto,
                            GrupoId
                        )
                        VALUES
                        (
                            ?,
                            ?
                        )
                    ");

                    $Pdo->beginTransaction();

                    try {

                        while (($Data = fgetcsv($Handle, 1000, ",")) !== false) {

                            if (
                                !isset($Data[0])
                                ||
                                empty(trim($Data[0]))
                            ) {

                                continue;
                            }

                            $Nombre = trim($Data[0]);

                            // VERIFICAR SI YA EXISTE

                            $Check->execute([
                                $Nombre,
                                $GrupoId
                            ]);

                            if ($Check->fetchColumn() > 0) {

                                $Duplicados++;

                                continue;
                            }

                            // INSERTAR

                            $Stmt->execute([
                                $Nombre,
                                $GrupoId
                            ]);

                            $Insertados++;
                        }

                        $Pdo->commit();

                    } catch (Exception $E) {

                        $Pdo->rollBack();

                        $Error = "Error Al Importar Los Alumnos.";
                    }

                    fclose($Handle);

                    if (empty($Error)) {

                        $Mensaje = "
                            Se Importaron
                            $Insertados Alumnos Correctamente.
                        ";

                        if ($Duplicados > 0) {

                            $Mensaje .= "
                                ($Duplicados Registros Duplicados Fueron Omitidos)
                            ";
                        }

                        header(
                            "Location: Admin.php?M="
                            .
                            urlencode($Mensaje)
                        );

                        exit;
                    }
                }
            }

        } else {

            $Error = "Error Al Subir El Archivo De Alumnos.";
        }
    }

    // =====================================================
    // IMPORTAR DOCENTES
    // =====================================================

    if (isset($_POST['ImportarDocentes'])) {

        if (
            isset($_FILES['CsvDocentes'])
            &&
            $_FILES['CsvDocentes']['error'] == 0
        ) {

            // VALIDAR EXTENSIÓN

            $Extension = strtolower(
                pathinfo(
                    $_FILES['CsvDocentes']['name'],
                    PATHINFO_EXTENSION
                )
            );

            if ($Extension !== 'csv') {

                $Error = "Solo Se Permiten Archivos CSV.";

            } else {

                $File = $_FILES['CsvDocentes']['tmp_name'];

                $Handle = fopen($File, "r");

                if (!$Handle) {

                    $Error = "No Se Pudo Leer El Archivo.";

                } else {

                    BomStrip($Handle);

                    $Insertados = 0;

                    $Duplicados = 0;

                    // VERIFICAR DUPLICADOS

                    $Check = $Pdo->prepare("
                        SELECT COUNT(*)
                        FROM Usuarios
                        WHERE Username = ?
                    ");

                    // INSERTAR DOCENTE

                    $Stmt = $Pdo->prepare("
                        INSERT INTO Usuarios
                        (
                            Username,
                            Password,
                            NombreCompleto,
                            Rol
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?,
                            'maestro'
                        )
                    ");

                    $Pdo->beginTransaction();

                    try {

                        while (($Data = fgetcsv($Handle, 1000, ",")) !== false) {

                            if (count($Data) < 3) {

                                continue;
                            }

                            $Nombre = trim($Data[0]);

                            $Username = trim($Data[1]);

                            $Password = trim($Data[2]);

                            if (
                                empty($Nombre)
                                ||
                                empty($Username)
                                ||
                                empty($Password)
                            ) {

                                continue;
                            }

                            // VALIDAR DUPLICADOS

                            $Check->execute([
                                $Username
                            ]);

                            if ($Check->fetchColumn() > 0) {

                                $Duplicados++;

                                continue;
                            }

                            // HASH PASSWORD

                            $PasswordHash = password_hash(
                                $Password,
                                PASSWORD_DEFAULT
                            );

                            // INSERTAR

                            $Stmt->execute([
                                $Username,
                                $PasswordHash,
                                $Nombre
                            ]);

                            $Insertados++;
                        }

                        $Pdo->commit();

                    } catch (Exception $E) {

                        $Pdo->rollBack();

                        $Error = "Error Al Importar Los Docentes.";
                    }

                    fclose($Handle);

                    if (empty($Error)) {

                        $Mensaje = "
                            Se Importaron
                            $Insertados Docentes Correctamente.
                        ";

                        if ($Duplicados > 0) {

                            $Mensaje .= "
                                ($Duplicados Usuarios Duplicados Fueron Omitidos)
                            ";
                        }

                        header(
                            "Location: Admin.php?M="
                            .
                            urlencode($Mensaje)
                        );

                        exit;
                    }
                }
            }

        } else {

            $Error = "Error Al Subir El Archivo De Docentes.";
        }
    }
}

// =====================================================
// ELIMINAR BOM UTF8
// =====================================================

function BomStrip($Handle) {

    if (fgets($Handle, 4) !== "\xEF\xBB\xBF") {

        rewind($Handle);
    }
}

?>