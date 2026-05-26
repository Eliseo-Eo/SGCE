<?php
require 'Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);

if (!$UserSession) {
    die("Acceso Denegado.");
}

$AsignacionId = $_GET['AsignacionId'] ?? 0;
$Tipo = $_GET['Tipo'] ?? 'Excel';

$Stmt = $Pdo->prepare("
    SELECT 
        A.MateriaNombre,
        A.MaestroId,
        G.Grado,
        G.Grupo,
        G.Turno,
        U.NombreCompleto AS Maestro
    FROM Asignaciones A
    JOIN Grupos G ON A.GrupoId = G.Id
    JOIN Usuarios U ON A.MaestroId = U.Id
    WHERE A.Id = ?
");

$Stmt->execute([$AsignacionId]);

$Info = $Stmt->fetch();

if (!$Info) {
    die("Reporte No Disponible.");
}

if (
    $UserSession['Rol'] === 'maestro' &&
    $UserSession['Id'] != $Info['MaestroId']
) {
    die("No Tienes Permiso.");
}

$StmtAsistencia = $Pdo->prepare("
    SELECT
        Al.NombreCompleto,
        Asis.Estado,
        DATE_FORMAT(Asis.Fecha, '%d/%m/%Y') AS Fecha
    FROM Asistencias Asis
    JOIN Alumnos Al ON Asis.AlumnoId = Al.Id
    WHERE Asis.AsignacionId = ?
    ORDER BY Asis.Fecha DESC, Al.NombreCompleto ASC
");

$StmtAsistencia->execute([$AsignacionId]);

$Lista = $StmtAsistencia->fetchAll();

$TituloArchivo =
"Reporte_Asistencia_" .
str_replace(' ', '_', $Info['MateriaNombre']);



/*
|--------------------------------------------------------------------------
| EXCEL
|--------------------------------------------------------------------------
*/

if ($Tipo === 'Excel') {

    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=$TituloArchivo.xls");

    ?>

    <html>
    <head>
        <meta charset="utf-8">

        <style>

            body{
                font-family: Arial;
            }

            table{
                border-collapse: collapse;
                width: 100%;
            }

            th{
                background:#7A0818;
                color:white;
                padding:10px;
                border:1px solid #ccc;
            }

            td{
                border:1px solid #ccc;
                padding:8px;
            }

        </style>

    </head>

    <body>

        <h2>
            ESCUELA SECUNDARIA TÉCNICA 101
        </h2>

        <h3>
            Reporte De Asistencia
        </h3>

        <table>

            <tr>
                <td><strong>Materia</strong></td>
                <td><?= htmlspecialchars($Info['MateriaNombre']) ?></td>
            </tr>

            <tr>
                <td><strong>Grupo</strong></td>
                <td>
                    <?= $Info['Grado'] ?>
                    "<?= $Info['Grupo'] ?>"
                </td>
            </tr>

            <tr>
                <td><strong>Turno</strong></td>
                <td><?= $Info['Turno'] ?></td>
            </tr>

            <tr>
                <td><strong>Docente</strong></td>
                <td><?= htmlspecialchars($Info['Maestro']) ?></td>
            </tr>

        </table>

        <br>

        <table>

            <tr>

                <th>No.</th>

                <th>Alumno</th>

                <th>Estado</th>

                <th>Fecha</th>

            </tr>

            <?php $i = 1; ?>

            <?php foreach($Lista as $Row): ?>

                <tr>

                    <td>
                        <?= $i++ ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($Row['NombreCompleto']) ?>
                    </td>

                    <td>

                        <?php

                        switch($Row['Estado']) {

                            case 'A':
                                echo 'Asistencia';
                            break;

                            case 'F':
                                echo 'Falta';
                            break;

                            case 'R':
                                echo 'Retardo';
                            break;

                            case 'J':
                                echo 'Justificante';
                            break;
                        }

                        ?>

                    </td>

                    <td>
                        <?= $Row['Fecha'] ?>
                    </td>

                </tr>

            <?php endforeach; ?>

        </table>

    </body>
    </html>

    <?php

    exit;
}



/*
|--------------------------------------------------------------------------
| PDF
|--------------------------------------------------------------------------
*/

if ($Tipo === 'Pdf') {

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <title>
        <?= $TituloArchivo ?>
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        body{
            font-family: Arial;
            padding:30px;
            color:#333;
        }

        .Header{
            border-bottom:3px solid #7A0818;
            margin-bottom:25px;
            padding-bottom:10px;
        }

        .Header h2{
            color:#7A0818;
            font-weight:800;
        }

        .TableReporte{
            width:100%;
            border-collapse: collapse;
        }

        .TableReporte th{
            background:#7A0818;
            color:white;
            padding:10px;
            border:1px solid #ddd;
        }

        .TableReporte td{
            border:1px solid #ddd;
            padding:8px;
        }

        @media print{

            .NoPrint{
                display:none;
            }

        }

    </style>

</head>

<body>

<div class="NoPrint mb-4">

    <button
        onclick="window.print()"
        class="btn btn-danger"
    >

        Imprimir / Guardar PDF

    </button>

</div>

<div class="Header">

    <h2>
        ESCUELA SECUNDARIA TÉCNICA 101
    </h2>

    <h5>
        Reporte Oficial De Asistencia
    </h5>

</div>

<div class="mb-4">

    <strong>Materia:</strong>
    <?= htmlspecialchars($Info['MateriaNombre']) ?>

    <br>

    <strong>Grupo:</strong>

    <?= $Info['Grado'] ?>
    "<?= $Info['Grupo'] ?>"

    <br>

    <strong>Turno:</strong>
    <?= $Info['Turno'] ?>

    <br>

    <strong>Docente:</strong>
    <?= htmlspecialchars($Info['Maestro']) ?>

</div>

<table class="TableReporte">

    <thead>

        <tr>

            <th>No.</th>

            <th>Alumno</th>

            <th>Estado</th>

            <th>Fecha</th>

        </tr>

    </thead>

    <tbody>

        <?php $i = 1; ?>

        <?php foreach($Lista as $Row): ?>

            <tr>

                <td>
                    <?= $i++ ?>
                </td>

                <td>
                    <?= htmlspecialchars($Row['NombreCompleto']) ?>
                </td>

                <td>

                    <?php

                    switch($Row['Estado']) {

                        case 'A':
                            echo 'Asistencia';
                        break;

                        case 'F':
                            echo 'Falta';
                        break;

                        case 'R':
                            echo 'Retardo';
                        break;

                        case 'J':
                            echo 'Justificante';
                        break;
                    }

                    ?>

                </td>

                <td>
                    <?= $Row['Fecha'] ?>
                </td>

            </tr>

        <?php endforeach; ?>

    </tbody>

</table>

<script>

window.onload = function(){

    setTimeout(function(){

        window.print();

    },300);

}

</script>

</body>
</html>

<?php

exit;

}
?>