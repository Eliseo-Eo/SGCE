<?php
require 'Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);

if (!$UserSession) { die("Acceso Denegado."); }

$AsignacionId = $_GET['AsignacionId'] ?? 0;
$Tipo = $_GET['Tipo'] ?? 'Excel';

$Stmt = $Pdo->prepare("
    SELECT A.MateriaNombre, A.MaestroId, G.Grado, G.Grupo, G.Turno, U.NombreCompleto AS Maestro 
    FROM Asignaciones A 
    JOIN Grupos G ON A.GrupoId = G.Id 
    JOIN Usuarios U ON A.MaestroId = U.Id
    WHERE A.Id = ?
");
$Stmt->execute([$AsignacionId]);
$Info = $Stmt->fetch();

if (!$Info) { die("Reporte No Disponible."); }

if ($UserSession['Rol'] === 'maestro' && $UserSession['Id'] != $Info['MaestroId']) {
    die("No Tienes Autorización Sobre Este Grupo.");
}

$StmtAlumnos = $Pdo->prepare("
    SELECT Al.NombreCompleto, C.Calificacion 
    FROM Alumnos Al
    LEFT JOIN Calificaciones C ON C.AlumnoId = Al.Id AND C.AsignacionId = ?
    WHERE Al.GrupoId = (SELECT GrupoId FROM Asignaciones WHERE Id = ?)
    ORDER BY Al.NombreCompleto ASC
");
$StmtAlumnos->execute([$AsignacionId, $AsignacionId]);
$ListaAlumnos = $StmtAlumnos->fetchAll();

$TituloArchivo = "Reporte_" . str_replace(' ', '_', $Info['MateriaNombre']) . "_" . $Info['Grado'] . $Info['Grupo'] . "_" . $Info['Turno'];

// ==========================================
// MÓDULO EXCEL (CONSERVADO EXACTAMENTE IGUAL)
// ==========================================
if ($Tipo === 'Excel') {
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=$TituloArchivo.xls");
    header("Pragma: no-cache");
    header("Expires: 0");
    ?>
    <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
    <head>
        <meta charset="utf-8">
        </x:WorksheetOptions>
                    </x:ExcelWorksheet>
                </x:ExcelWorksheets>
            </x:ExcelWorkbook>
        </xml>
        <![endif]-->
        <style>
            /* Estilos globales para la interpretación de Excel */
            td {
                vertical-align: middle;
                font-family: 'Segoe UI', Arial, sans-serif;
                font-size: 11px;
            }
            .text-center { text-align: center; }
            .text-left { text-align: left; }
            
            /* Formato de celdas de datos */
            .celda-alumno {
                border: 0.5pt solid #cccccc;
                height: 22px; /* Renglones más altos y cómodos */
                padding-left: 5px;
            }
            .celda-calificacion {
                border: 0.5pt solid #cccccc;
                font-weight: bold;
                text-align: center;
                /* Fuerza a Excel a tratar el número con dos decimales */
                mso-number-format: "0\.00"; 
            }
            .celda-vacia {
                border: 0.5pt solid #cccccc;
                color: #999999;
                font-weight: bold;
                text-align: center;
            }
        </style>
    </head>
    <body>
    <table>
        <tr>
            <th colspan="3" style="background-color: #7A0818; color: #ffffff; font-size: 14px; font-weight: bold; height: 35px; text-align: center; vertical-align: middle;">
                ESCUELA SECUNDARIA TÉCNICA 101
            </th>
        </tr>
        <tr>
            <th colspan="3" style="background-color: #56040E; color: #ffffff; font-size: 11px; font-weight: normal; height: 20px; text-align: center; vertical-align: middle; text-transform: uppercase;">
                Reporte Oficial de Evaluaciones
            </th>
        </tr>

        <tr><td colspan="3" style="height: 10px;"></td></tr>
        <tr>
            <td style="font-weight: bold; background-color: #f2f2f2; border: 0.5pt solid #d9d9d9; height: 20px; padding-left: 5px; width: 50px;">No.</td>
            <td style="font-weight: bold; background-color: #f2f2f2; border: 0.5pt solid #d9d9d9; width: 320px; padding-left: 5px;">Materia:</td>
            <td style="border: 0.5pt solid #d9d9d9; padding-left: 5px; width: 100px;"><?= htmlspecialchars($Info['MateriaNombre']) ?></td>
        </tr>
        <tr>
            <td style="background-color: #f2f2f2; border: 0.5pt solid #d9d9d9; height: 20px;"></td>
            <td style="font-weight: bold; background-color: #f2f2f2; border: 0.5pt solid #d9d9d9; padding-left: 5px;">Grupo / Turno:</td>
            <td style="border: 0.5pt solid #d9d9d9; padding-left: 5px; font-weight: bold;"><?= $Info['Grado'] ?> "<?= $Info['Grupo'] ?>" - <?= $Info['Turno'] ?></td>
        </tr>
        <tr>
            <td style="background-color: #f2f2f2; border: 0.5pt solid #d9d9d9; height: 20px;"></td>
            <td style="font-weight: bold; background-color: #f2f2f2; border: 0.5pt solid #d9d9d9; padding-left: 5px;">Docente:</td>
            <td style="border: 0.5pt solid #d9d9d9; padding-left: 5px;"><?= htmlspecialchars($Info['Maestro']) ?></td>
        </tr>
        <tr><td colspan="3" style="height: 15px;"></td></tr>

        <tr style="background-color: #7A0818; color: #ffffff; font-weight: bold;">
            <th style="border: 0.5pt solid #7A0818; height: 25px; text-align: center; vertical-align: middle;">N°</th>
            <th style="border: 0.5pt solid #7A0818; text-align: left; vertical-align: middle; padding-left: 5px;">Nombre Del Alumno</th>
            <th style="border: 0.5pt solid #7A0818; text-align: center; vertical-align: middle;">Calificación</th>
        </tr>

        <?php $num = 1; foreach($ListaAlumnos as $Al): ?>
            <tr>
                <td class="text-center celda-alumno" style="background-color: #f9f9f9; color: #666666; font-weight: bold;"><?= $num++ ?></td>
                <td class="text-left celda-alumno"><?= htmlspecialchars($Al['NombreCompleto']) ?></td>
                <?php if ($Al['Calificacion'] !== null): ?>
                    <td class="celda-calificacion"><?= $Al['Calificacion'] ?></td>
                <?php else: ?>
                    <td class="celda-vacia">-</td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        
        <tr><td colspan="3" style="height: 30px;"></td></tr>
        <tr>
            <td></td>
            <td style="border-top: 1.5pt solid #333333; text-align: center; font-size: 10px; color: #555555;">
                Firma del Docente<br><strong><?= htmlspecialchars($Info['Maestro']) ?></strong>
            </td>
            <td></td>
        </tr>
    </table>
    </body>
    </html>
    <?php
    exit;
}

// ==========================================
// MÓDULO PDF (DISEÑO REDISEÑADO Y COMPACTO)
// ==========================================
if ($Tipo === 'Pdf') {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title><?= $TituloArchivo ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
        <style>
            /* Configuración de página de impresión */
            @page {
                size: letter;
                margin: 1cm 1.5cm;
            }
            
            body { 
                font-family: 'Segoe UI', Arial, sans-serif; 
                color: #333; 
                background-color: #fff;
                font-size: 11px;
                line-height: 1.2;
            }

            .NoPrint { 
                background-color: #f8f9fa; 
                border: 1px solid #dee2e6;
            }

            /* Encabezado compacto y elegante estilo institucional guinda */
            .HeaderReporte { 
                border-bottom: 3px solid #7A0818; 
                padding-bottom: 6px; 
                margin-bottom: 15px; 
            }
            .HeaderReporte h2 { 
                color: #7A0818; 
                font-size: 18px; 
                font-weight: 800;
                margin: 0;
            }
            .HeaderReporte h5 { 
                font-size: 12px; 
                margin: 2px 0 0 0;
                color: #6c757d;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            /* Tabla altamente optimizada para ocupar poco espacio vertical */
            .TableReporte {
                width: 100% !important;
                margin-bottom: 15px;
            }
            .TableReporte th { 
                background-color: #7A0818 !important; 
                color: white !important; 
                font-size: 10.5px;
                font-weight: bold;
                text-transform: uppercase;
                padding: 4px 8px !important;
                text-align: center;
                border: 1px solid #7A0818 !important;
            }
            .TableReporte td { 
                font-size: 10.5px;
                padding: 3px 8px !important; /* Margen vertical mínimo por renglón */
                vertical-align: middle;
                border: 1px solid #dee2e6 !important;
            }
            
            /* Cebra sutil para la lectura de alumnos */
            .TableReporte tbody tr:nth-child(even) {
                background-color: #f8f9fa !important;
            }

            .FirmaSeccion {
                margin-top: 35px;
            }
            .FirmaLinea {
                border-top: 1.5px solid #6c757d; 
                width: 220px; 
                margin: 0 auto;
                padding-top: 4px;
                font-size: 10px;
                color: #495057;
            }

            /* Parámetros de impresión nativa del navegador */
            @media print {
                .NoPrint { display: none !important; }
                body { padding: 0 !important; }
                .TableReporte th { 
                    background-color: #7A0818 !important; 
                    color: white !important; 
                    -webkit-print-color-adjust: exact; 
                    print-color-adjust: exact; 
                }
                .TableReporte tbody tr:nth-child(even) {
                    background-color: #f8f9fa !important;
                    -webkit-print-color-adjust: exact; 
                    print-color-adjust: exact;
                }
                tr { page-break-inside: avoid; }
            }
        </style>
    </head>
    <body>
        <div class="container-fluid px-0">
            <div class="d-flex justify-content-between align-items-center NoPrint mb-3 p-2 rounded shadow-sm">
                <span class="text-secondary small"><i class="fa-solid fa-eye"></i> <strong>Vista Preliminar Escolar</strong></span>
                <div>
                    <button onclick="window.print();" class="btn btn-danger btn-sm fw-bold px-3"><i class="fa-solid fa-print"></i> Imprimir / Guardar PDF</button>
                    <button onclick="window.close();" class="btn btn-secondary btn-sm px-2">Cerrar</button>
                </div>
            </div>

            <div class="HeaderReporte d-flex justify-content-between align-items-end">
                <div>
                    <h2>ESCUELA SECUNDARIA TÉCNICA 101</h2>
                    <h5>Reporte Oficial de Evaluaciones</h5>
                </div>
                <div class="text-end small lh-sm text-secondary">
                    <div><strong>Materia:</strong> <span class="text-dark"><?= htmlspecialchars($Info['MateriaNombre']) ?></span></div>
                    <div><strong>Grupo:</strong> <span class="text-dark"><?= $Info['Grado'] ?> "<?= $Info['Grupo'] ?>"</span> &nbsp;|&nbsp; <strong>Turno:</strong> <span class="text-dark"><?= $Info['Turno'] ?></span></div>
                    <div><strong>Docente:</strong> <span class="text-dark"><?= htmlspecialchars($Info['Maestro']) ?></span></div>
                </div>
            </div>

            <table class="table table-sm TableReporte">
                <thead>
                    <tr>
                        <th style="width: 7%;">No.</th>
                        <th style="text-align: left;">Nombre del Estudiante</th>
                        <th style="width: 18%;">Calificación</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach($ListaAlumnos as $Al): ?>
                        <tr>
                            <td class="text-center text-muted fw-bold"><?= $i++ ?></td>
                            <td style="text-align: left;"><?= htmlspecialchars($Al['NombreCompleto']) ?></td>
                            <td class="text-center fw-bold text-dark"><?= $Al['Calificacion'] !== null ? number_format($Al['Calificacion'], 2) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="text-center FirmaSeccion">
                <div class="FirmaLinea">
                    Firma del Docente<br>
                    <strong class="text-dark"><?= htmlspecialchars($Info['Maestro']) ?></strong>
                </div>
            </div>
        </div>
        
        <script>
            window.onload = function() { 
                // Pequeño delay para asegurar la correcta renderización antes de abrir el cuadro de impresión
                setTimeout(function() { window.print(); }, 300); 
            }
        </script>
    </body>
    </html>
    <?php
    exit;
}
?>