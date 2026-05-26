<?php
require 'Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);

if (!$UserSession) {

    die("Acceso Denegado.");
}

$AsignacionId = $_GET['AsignacionId'] ?? 0;

$Tipo = $_GET['Tipo'] ?? 'Excel';



/*
|--------------------------------------------------------------------------
| INFORMACIÓN DE LA CLASE
|--------------------------------------------------------------------------
*/

$Stmt = $Pdo->prepare("

    SELECT
        A.MateriaNombre,
        A.MaestroId,
        G.Grado,
        G.Grupo,
        G.Turno,
        U.NombreCompleto AS Maestro

    FROM Asignaciones A

    JOIN Grupos G
        ON A.GrupoId = G.Id

    JOIN Usuarios U
        ON A.MaestroId = U.Id

    WHERE A.Id = ?

");

$Stmt->execute([$AsignacionId]);

$Info = $Stmt->fetch();

if (!$Info) {

    die("Reporte No Disponible.");
}



/*
|--------------------------------------------------------------------------
| SEGURIDAD
|--------------------------------------------------------------------------
*/

if (

    $UserSession['Rol'] === 'maestro' &&
    $UserSession['Id'] != $Info['MaestroId']

) {

    die("No Tienes Permiso.");
}



/*
|--------------------------------------------------------------------------
| LISTA DE ALUMNOS Y CALIFICACIONES
|--------------------------------------------------------------------------
*/

$StmtAlumnos = $Pdo->prepare("

    SELECT

        Al.NombreCompleto,
        C.Calificacion

    FROM Alumnos Al

    LEFT JOIN Calificaciones C

        ON C.AlumnoId = Al.Id
        AND C.AsignacionId = ?

    WHERE Al.GrupoId = (

        SELECT GrupoId
        FROM Asignaciones
        WHERE Id = ?

    )

    ORDER BY Al.NombreCompleto ASC

");

$StmtAlumnos->execute([

    $AsignacionId,
    $AsignacionId

]);

$ListaAlumnos = $StmtAlumnos->fetchAll();



/*
|--------------------------------------------------------------------------
| NOMBRE DEL ARCHIVO
|--------------------------------------------------------------------------
*/

$TituloArchivo =
"Reporte_Calificaciones_" .
str_replace(' ', '_', $Info['MateriaNombre']) .
"_" .
$Info['Grado'] .
$Info['Grupo'];



/*
|--------------------------------------------------------------------------
| EXCEL
|--------------------------------------------------------------------------
*/

if ($Tipo === 'Excel') {

    header("Content-Type: application/vnd.ms-excel; charset=utf-8");

    header("Content-Disposition: attachment; filename=$TituloArchivo.xls");

    header("Pragma: no-cache");

    header("Expires: 0");

?>

<html>

<head>

    <meta charset="utf-8">

    <style>

        body{
            font-family:Arial;
        }

        table{
            border-collapse:collapse;
            width:100%;
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

        .Titulo{
            background:#7A0818;
            color:white;
            font-size:18px;
            font-weight:bold;
            text-align:center;
            padding:18px;
        }

        .SubTitulo{
            background:#A10D26;
            color:white;
            text-align:center;
            padding:10px;
        }

        .Info{
            background:#F8F9FA;
            font-weight:bold;
            width:180px;
        }

        .Calificacion{
            text-align:center;
            font-weight:bold;
        }

    </style>

</head>

<body>

<table>

    <tr>

        <td colspan="3" class="Titulo">

            ESCUELA SECUNDARIA TÉCNICA 101

        </td>

    </tr>

    <tr>

        <td colspan="3" class="SubTitulo">

            REPORTE OFICIAL DE CALIFICACIONES

        </td>

    </tr>

</table>

<br>

<table>

    <tr>

        <td class="Info">
            Materia
        </td>

        <td colspan="2">
            <?= htmlspecialchars($Info['MateriaNombre']) ?>
        </td>

    </tr>

    <tr>

        <td class="Info">
            Grupo
        </td>

        <td colspan="2">

            <?= $Info['Grado'] ?>
            "<?= $Info['Grupo'] ?>"

        </td>

    </tr>

    <tr>

        <td class="Info">
            Turno
        </td>

        <td colspan="2">
            <?= $Info['Turno'] ?>
        </td>

    </tr>

    <tr>

        <td class="Info">
            Docente
        </td>

        <td colspan="2">
            <?= htmlspecialchars($Info['Maestro']) ?>
        </td>

    </tr>

</table>

<br>

<table>

    <tr>

        <th style="width:70px;">
            No.
        </th>

        <th>
            Nombre Del Alumno
        </th>

        <th style="width:150px;">
            Calificación
        </th>

    </tr>

    <?php $Numero = 1; ?>

    <?php foreach($ListaAlumnos as $Al): ?>

        <tr>

            <td align="center">

                <?= $Numero++ ?>

            </td>

            <td>

                <?= htmlspecialchars($Al['NombreCompleto']) ?>

            </td>

            <td class="Calificacion">

                <?= $Al['Calificacion'] !== null
                    ? number_format($Al['Calificacion'], 2)
                    : '-'
                ?>

            </td>

        </tr>

    <?php endforeach; ?>

</table>

<br><br><br>

<table style="width:100%; border:none;">

    <tr>

        <td style="border:none;"></td>

        <td
            style="
                border:none;
                text-align:center;
                width:300px;
            "
        >

            ___________________________

            <br>

            <strong>

                <?= htmlspecialchars($Info['Maestro']) ?>

            </strong>

            <br>

            Firma Del Docente

        </td>

        <td style="border:none;"></td>

    </tr>

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

    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        rel="stylesheet"
    >

    <style>

        @page{
            size:letter;
            margin:1.5cm;
        }

        body{
            font-family:'Segoe UI',sans-serif;
            color:#333;
            font-size:12px;
        }

        .NoPrint{
            background:#F8F9FA;
            border:1px solid #DDD;
        }

        .HeaderReporte{
            border-bottom:4px solid #7A0818;
            padding-bottom:12px;
            margin-bottom:20px;
        }

        .HeaderReporte h2{
            color:#7A0818;
            font-weight:800;
            margin:0;
        }

        .HeaderReporte h5{
            color:#666;
            margin-top:5px;
            text-transform:uppercase;
        }

        .TableReporte{
            width:100%;
            border-collapse:collapse;
        }

        .TableReporte th{
            background:#7A0818;
            color:white;
            padding:10px;
            border:1px solid #CCC;
            text-transform:uppercase;
            font-size:11px;
        }

        .TableReporte td{
            border:1px solid #DDD;
            padding:8px;
        }

        .TableReporte tbody tr:nth-child(even){
            background:#F8F9FA;
        }

        .Firma{
            margin-top:60px;
            text-align:center;
        }

        .FirmaLinea{
            width:260px;
            margin:auto;
            border-top:1px solid #333;
            padding-top:5px;
        }

        @media print{

            .NoPrint{
                display:none;
            }

            .TableReporte th{
                background:#7A0818 !important;
                color:white !important;

                -webkit-print-color-adjust:exact;
                print-color-adjust:exact;
            }

            .TableReporte tbody tr:nth-child(even){

                background:#F8F9FA !important;

                -webkit-print-color-adjust:exact;
                print-color-adjust:exact;
            }

        }

    </style>

</head>

<body>

<div class="NoPrint p-3 rounded mb-4 d-flex justify-content-between align-items-center">

    <div>

        <i class="fa-solid fa-eye"></i>

        Vista Preliminar

    </div>

    <div>

        <button
            onclick="window.print()"
            class="btn btn-danger btn-sm"
        >

            <i class="fa-solid fa-print"></i>

            Imprimir / Guardar PDF

        </button>

    </div>

</div>



<div class="HeaderReporte d-flex justify-content-between align-items-end">

    <div>

        <h2>

            ESCUELA SECUNDARIA TÉCNICA 101

        </h2>

        <h5>

            Reporte Oficial De Calificaciones

        </h5>

    </div>

    <div class="text-end">

        <div>

            <strong>Materia:</strong>

            <?= htmlspecialchars($Info['MateriaNombre']) ?>

        </div>

        <div>

            <strong>Grupo:</strong>

            <?= $Info['Grado'] ?>
            "<?= $Info['Grupo'] ?>"

        </div>

        <div>

            <strong>Turno:</strong>

            <?= $Info['Turno'] ?>

        </div>

        <div>

            <strong>Docente:</strong>

            <?= htmlspecialchars($Info['Maestro']) ?>

        </div>

    </div>

</div>



<table class="TableReporte">

    <thead>

        <tr>

            <th style="width:8%;">
                No.
            </th>

            <th>
                Nombre Del Alumno
            </th>

            <th style="width:18%;">
                Calificación
            </th>

        </tr>

    </thead>

    <tbody>

        <?php $Numero = 1; ?>

        <?php foreach($ListaAlumnos as $Al): ?>

            <tr>

                <td align="center">

                    <?= $Numero++ ?>

                </td>

                <td>

                    <?= htmlspecialchars($Al['NombreCompleto']) ?>

                </td>

                <td align="center">

                    <strong>

                        <?= $Al['Calificacion'] !== null
                            ? number_format($Al['Calificacion'], 2)
                            : '-'
                        ?>

                    </strong>

                </td>

            </tr>

        <?php endforeach; ?>

    </tbody>

</table>



<div class="Firma">

    <div class="FirmaLinea">

        <strong>

            <?= htmlspecialchars($Info['Maestro']) ?>

        </strong>

        <br>

        Firma Del Docente

    </div>

</div>



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