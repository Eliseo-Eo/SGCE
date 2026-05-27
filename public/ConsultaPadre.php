<?php

/*
    Archivo: ConsultaPadre.php
    Descripción: Consulta pública para madres, padres o tutores.
    Permite revisar el estado de asistencia del día escribiendo el nombre completo
    del alumno y seleccionando grado, grupo y turno. No muestra listas completas
    para proteger la confidencialidad de los estudiantes.
*/

require_once dirname(__DIR__) . '/config/Conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$ConfigSistema = SgceObtenerConfiguracion($Pdo);
$NombreEscuelaConsulta = trim((string)($ConfigSistema['NombreEscuela'] ?? 'SGCE'));
date_default_timezone_set('America/Mexico_City');

function ValidarGrupoPublico($Valor) {
    $Valor = SgceNormalizarMayusculas($Valor);
    return ($Valor !== '' && preg_match('/^[A-ZÁÉÍÓÚÜÑ0-9\-]+$/u', $Valor)) ? $Valor : '';
}

function TextoEstadoPublico($Estado) {
    switch ($Estado) {
        case 'A': return 'ASISTENCIA';
        case 'F': return 'FALTA';
        case 'R': return 'RETARDO';
        case 'J': return 'JUSTIFICANTE';
        default: return 'SIN REGISTRO';
    }
}

$Hoy = date('Y-m-d');
$FechaHumana = date('d/m/Y');
$Resultado = null;
$Error = '';

// Cargo avisos públicos dirigidos a padres o a todo el sistema.
$StmtAvisosPadres = $Pdo->query("SELECT Titulo, Mensaje, FechaCreacion FROM Avisos WHERE Activo = 1 AND Publico IN ('TODOS','PADRES') ORDER BY FechaCreacion DESC LIMIT 3");
$AvisosPadres = $StmtAvisosPadres ? $StmtAvisosPadres->fetchAll() : [];

$GradosDisponibles = $Pdo->query("SELECT DISTINCT Grado FROM Grupos ORDER BY Grado ASC")->fetchAll();
$GruposDisponibles = $Pdo->query("SELECT DISTINCT Grupo FROM Grupos ORDER BY Grupo ASC")->fetchAll();

$NombreAlumno = '';
$Grado = '';
$Grupo = '';
$Turno = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    RequerirCsrfPost();

    $NombreAlumno = SgceNormalizarMayusculas($_POST['NombreAlumno'] ?? '');
    $Grado = SgceNormalizarMayusculas($_POST['Grado'] ?? '');
    $Grupo = ValidarGrupoPublico($_POST['Grupo'] ?? '');
    $Turno = SgceNormalizarMayusculas($_POST['Turno'] ?? '');

    if (!RateLimitDisponible($Pdo, 'consulta_padre', $NombreAlumno . '|' . $Grado . '|' . $Grupo . '|' . $Turno)) {
        $Error = 'DEMASIADOS INTENTOS DE CONSULTA. ESPERA 15 MINUTOS E INTENTA NUEVAMENTE.';
    } elseif ($NombreAlumno === '' || !preg_match('/^[\p{L}\s]+$/u', $NombreAlumno)) {
        $Error = 'ESCRIBE EL NOMBRE COMPLETO DEL ALUMNO, SOLO CON LETRAS Y ESPACIOS.';
    } elseif (!SgceValidarGrado($Grado) || $Grupo === '' || !in_array($Turno, ['MATUTINO', 'VESPERTINO'], true)) {
        $Error = 'SELECCIONA GRADO, GRUPO Y TURNO PARA VALIDAR LA CONSULTA.';
    } else {

        $StmtGrupo = $Pdo->prepare("SELECT Id, Grado, Grupo, Turno FROM Grupos WHERE Grado = ? AND Grupo = ? AND Turno = ? AND Activo = 1 LIMIT 1");
        $StmtGrupo->execute([$Grado, $Grupo, $Turno]);
        $InfoGrupo = $StmtGrupo->fetch();

        if (!$InfoGrupo) {
            $Error = 'NO SE ENCONTRÓ UN GRUPO CON ESOS DATOS. REVISA GRADO, GRUPO Y TURNO.';
            RateLimitRegistrarFallo($Pdo, 'consulta_padre', $NombreAlumno . '|' . $Grado . '|' . $Grupo . '|' . $Turno, 8, 15);
        } else {

            $StmtAlumno = $Pdo->prepare("SELECT Id, NombreCompleto FROM Alumnos WHERE GrupoId = ? AND NombreCompleto = ? AND Activo = 1 LIMIT 1");
            $StmtAlumno->execute([(int)$InfoGrupo['Id'], $NombreAlumno]);
            $Alumno = $StmtAlumno->fetch();

            if (!$Alumno) {
                $Error = 'NO SE ENCONTRÓ UN ALUMNO CON ESOS DATOS. REVISA NOMBRE COMPLETO, GRADO, GRUPO Y TURNO.';
                RateLimitRegistrarFallo($Pdo, 'consulta_padre', $NombreAlumno . '|' . $Grado . '|' . $Grupo . '|' . $Turno, 8, 15);
            } else {

                $StmtTotalMaterias = $Pdo->prepare("SELECT COUNT(*) FROM Asignaciones WHERE GrupoId = ? AND Activo = 1");
                $StmtTotalMaterias->execute([(int)$InfoGrupo['Id']]);
                $TotalMaterias = (int)$StmtTotalMaterias->fetchColumn();

                $StmtAsistencia = $Pdo->prepare("
                    SELECT Asis.Estado, COUNT(*) AS Total
                    FROM Asistencias Asis
                    JOIN Asignaciones Asg ON Asg.Id = Asis.AsignacionId
                    WHERE Asis.AlumnoId = ?
                    AND Asis.FechaDia = ?
                    AND Asg.GrupoId = ?
                    GROUP BY Asis.Estado
                ");

                $StmtAsistencia->execute([
                    (int)$Alumno['Id'],
                    $Hoy,
                    (int)$InfoGrupo['Id']
                ]);

                $Conteos = ['A' => 0, 'F' => 0, 'R' => 0, 'J' => 0];
                foreach ($StmtAsistencia->fetchAll() as $Fila) {
                    $Estado = (string)$Fila['Estado'];
                    if (isset($Conteos[$Estado])) {
                        $Conteos[$Estado] = (int)$Fila['Total'];
                    }
                }

                $RegistrosCapturados = array_sum($Conteos);
                $SinCapturar = max(0, $TotalMaterias - $RegistrosCapturados);
                $RegistrosPositivos = $Conteos['A'] + $Conteos['R'] + $Conteos['J'];

                if ($TotalMaterias <= 0) {
                    $EstatusGeneral = 'SIN MATERIAS ASIGNADAS';
                    $ClaseEstado = 'warning';
                    $IconoEstado = 'fa-triangle-exclamation';
                    $Descripcion = 'EL GRUPO TODAVÍA NO TIENE MATERIAS ASIGNADAS EN EL SISTEMA.';
                } elseif ($RegistrosCapturados <= 0) {
                    $EstatusGeneral = 'SIN REGISTRO CAPTURADO HOY';
                    $ClaseEstado = 'secondary';
                    $IconoEstado = 'fa-clock';
                    $Descripcion = 'AÚN NO SE HA CAPTURADO ASISTENCIA DE HOY PARA ESTE ALUMNO.';
                } elseif ($RegistrosPositivos > 0 && $Conteos['F'] <= 0) {
                    $EstatusGeneral = 'CON ASISTENCIA REGISTRADA';
                    $ClaseEstado = 'success';
                    $IconoEstado = 'fa-circle-check';
                    $Descripcion = 'EL ALUMNO TIENE ASISTENCIA, RETARDO O JUSTIFICANTE EN LOS REGISTROS CAPTURADOS HOY.';
                } elseif ($RegistrosPositivos > 0 && $Conteos['F'] > 0) {
                    $EstatusGeneral = 'ASISTENCIA PARCIAL';
                    $ClaseEstado = 'warning';
                    $IconoEstado = 'fa-circle-half-stroke';
                    $Descripcion = 'EL ALUMNO TIENE AL MENOS UNA ASISTENCIA O JUSTIFICACIÓN, PERO TAMBIÉN PRESENTA FALTA EN ALGUNA MATERIA CAPTURADA.';
                } else {
                    $EstatusGeneral = 'SIN ASISTENCIA EN REGISTROS CAPTURADOS';
                    $ClaseEstado = 'danger';
                    $IconoEstado = 'fa-circle-xmark';
                    $Descripcion = 'EN LAS MATERIAS CAPTURADAS HOY, EL ALUMNO APARECE CON FALTA.';
                }

                $Resultado = [
                    'Alumno' => $Alumno['NombreCompleto'],
                    'Grado' => $InfoGrupo['Grado'],
                    'Grupo' => $InfoGrupo['Grupo'],
                    'Turno' => $InfoGrupo['Turno'],
                    'TotalMaterias' => $TotalMaterias,
                    'RegistrosCapturados' => $RegistrosCapturados,
                    'SinCapturar' => $SinCapturar,
                    'Conteos' => $Conteos,
                    'EstatusGeneral' => $EstatusGeneral,
                    'ClaseEstado' => $ClaseEstado,
                    'IconoEstado' => $IconoEstado,
                    'Descripcion' => $Descripcion
                ];

                RateLimitLimpiar($Pdo, 'consulta_padre', $NombreAlumno . '|' . $Grado . '|' . $Grupo . '|' . $Turno);
                RegistrarBitacora($Pdo, ['Id' => null, 'Rol' => 'publico'], 'CONSULTA_PADRE', 'Alumnos', (int)$Alumno['Id'], 'CONSULTA PÚBLICA DE ASISTENCIA');
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($NombreEscuelaConsulta, ENT_QUOTES, 'UTF-8') ?> | Consulta De Asistencia</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <link rel="apple-touch-icon" href="favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/sgce-base.css?v=1.0.0">

</head>
<body class="ConsultaPublicaBody">

<main class="ConsultaPublicaWrap">

    <section class="ConsultaHero">
        <div class="ConsultaHeroIcon"><i class="fa-solid fa-user-shield"></i></div>
        <div>
            <span class="ConsultaBadge"><i class="fa-solid fa-lock"></i> Consulta protegida</span>
            <div class="ConsultaSchoolName"><?= htmlspecialchars($NombreEscuelaConsulta, ENT_QUOTES, 'UTF-8') ?></div>
            <h1>Consulta de asistencia del día</h1>
            <p>Escribe el nombre completo del alumno y selecciona su grado, grupo y turno. El sistema solo muestra el resultado del alumno consultado.</p>
        </div>
    </section>

    <?php if(!empty($AvisosPadres)): ?>
        <section class="ConsultaAvisosCard ConsultaPadresAvisosPanel mb-4">
            <div class="ConsultaPadresAvisosHeader">
                <div class="ConsultaPadresAvisosTitleBlock">
                    <div class="ConsultaPadresAvisosIcon"><i class="fa-solid fa-bullhorn"></i></div>
                    <div>
                        <span class="ConsultaPadresAvisosEyebrow">Comunicación escolar</span>
                        <h5>Avisos para padres</h5>
                    </div>
                </div>
                <span class="ConsultaPadresAvisosBadge"><?= count($AvisosPadres) === 1 ? '1 aviso' : count($AvisosPadres) . ' avisos' ?></span>
            </div>

            <div class="ConsultaPadresAvisosGrid">
                <?php foreach($AvisosPadres as $Aviso): ?>
                    <article class="ConsultaPadresAvisoItem">
                        <div class="ConsultaPadresAvisoItemIcon"><i class="fa-solid fa-bell"></i></div>
                        <div class="ConsultaPadresAvisoItemBody">
                            <h6><?= htmlspecialchars($Aviso['Titulo'], ENT_QUOTES, 'UTF-8') ?></h6>
                            <div class="ConsultaPadresAvisoFecha">
                                <i class="fa-regular fa-clock"></i>
                                <?= htmlspecialchars(date('d/m/Y H:i', strtotime($Aviso['FechaCreacion'])), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <p><?= nl2br(htmlspecialchars($Aviso['Mensaje'], ENT_QUOTES, 'UTF-8')) ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <div class="ConsultaGrid">
        <div>
            <div class="ConsultaCard">
                <h4 class="fw-bold mb-1"><i class="fa-solid fa-magnifying-glass text-danger me-2"></i>Buscar alumno</h4>
                <p class="text-muted mb-4">Escribe el nombre completo y selecciona grado, grupo y turno. No se muestran listas completas por privacidad.</p>

                <?php if($Error): ?>
                    <div class="alert alert-danger mb-4">
                        <i class="fa-solid fa-circle-exclamation me-2"></i>
                        <?= htmlspecialchars($Error, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form method="POST" autocomplete="off">
                    <?php echo CampoCsrf(); ?>
                    <div class="mb-3">
                        <label>Nombre completo del alumno</label>
                        <input type="text" name="NombreAlumno" class="form-control SoloLetrasMayus" placeholder="NOMBRE COMPLETO" value="<?= htmlspecialchars($NombreAlumno, ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label>Grado</label>
                            <select name="Grado" class="form-select" required>
                                <option value="">GRADO</option>
                                <?php foreach($GradosDisponibles as $G): ?>
                                    <option value="<?= htmlspecialchars($G['Grado'], ENT_QUOTES, 'UTF-8') ?>" <?= $Grado === $G['Grado'] ? 'selected' : '' ?>><?= htmlspecialchars($G['Grado'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label>Grupo</label>
                            <select name="Grupo" class="form-select" required>
                                <option value="">GRUPO</option>
                                <?php foreach($GruposDisponibles as $G): ?>
                                    <option value="<?= htmlspecialchars($G['Grupo'], ENT_QUOTES, 'UTF-8') ?>" <?= $Grupo === $G['Grupo'] ? 'selected' : '' ?>><?= htmlspecialchars($G['Grupo'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label>Turno</label>
                            <select name="Turno" class="form-select" required>
                                <option value="">TURNO</option>
                                <option value="MATUTINO" <?= $Turno === 'MATUTINO' ? 'selected' : '' ?>>MATUTINO</option>
                                <option value="VESPERTINO" <?= $Turno === 'VESPERTINO' ? 'selected' : '' ?>>VESPERTINO</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="BtnPrincipal mt-4">
                        <i class="fa-solid fa-calendar-check"></i>
                        CONSULTAR ASISTENCIA DE HOY
                    </button>
                </form>

                <div class="AvisoPrivacidad mt-4">
                    <i class="fa-solid fa-shield-halved me-2"></i>
                    Por seguridad, la consulta requiere coincidencia exacta de nombre completo, grado, grupo y turno; no muestra listas completas ni datos de otros alumnos.
                </div>
            </div>
        </div>

        <div>
            <div class="ResultadoCard">
                <?php if($Resultado): ?>
                    <div class="EstadoBox <?= htmlspecialchars($Resultado['ClaseEstado'], ENT_QUOTES, 'UTF-8') ?> mb-4">
                        <div class="EstadoIcon">
                            <i class="fa-solid <?= htmlspecialchars($Resultado['IconoEstado'], ENT_QUOTES, 'UTF-8') ?>"></i>
                        </div>
                        <h3 class="fw-bold mb-2"><?= htmlspecialchars($Resultado['EstatusGeneral'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p class="mb-0"><?= htmlspecialchars($Resultado['Descripcion'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>

                    <div class="mb-4">
                        <h5 class="fw-bold mb-2"><i class="fa-solid fa-user-graduate text-danger me-2"></i><?= htmlspecialchars($Resultado['Alumno'], ENT_QUOTES, 'UTF-8') ?></h5>
                        <div class="text-muted fw-semibold">
                            <?= htmlspecialchars($Resultado['Grado'], ENT_QUOTES, 'UTF-8') ?> "<?= htmlspecialchars($Resultado['Grupo'], ENT_QUOTES, 'UTF-8') ?>" · <?= htmlspecialchars($Resultado['Turno'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($FechaHumana, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-6 col-md-4"><div class="MetricCard"><div class="Numero"><?= (int)$Resultado['Conteos']['A'] ?></div><div class="Texto">ASISTENCIAS</div></div></div>
                        <div class="col-6 col-md-4"><div class="MetricCard"><div class="Numero"><?= (int)$Resultado['Conteos']['F'] ?></div><div class="Texto">FALTAS</div></div></div>
                        <div class="col-6 col-md-4"><div class="MetricCard"><div class="Numero"><?= (int)$Resultado['Conteos']['R'] ?></div><div class="Texto">RETARDOS</div></div></div>
                        <div class="col-6 col-md-4"><div class="MetricCard"><div class="Numero"><?= (int)$Resultado['Conteos']['J'] ?></div><div class="Texto">JUSTIFICANTES</div></div></div>
                        <div class="col-6 col-md-4"><div class="MetricCard"><div class="Numero"><?= (int)$Resultado['RegistrosCapturados'] ?></div><div class="Texto">CAPTURADAS</div></div></div>
                        <div class="col-6 col-md-4"><div class="MetricCard"><div class="Numero"><?= (int)$Resultado['SinCapturar'] ?></div><div class="Texto">SIN CAPTURAR</div></div></div>
                    </div>

                    <div class="AvisoPrivacidad mt-4">
                        <strong>Nota:</strong> Si aparece “sin capturar”, significa que puede haber materias donde todavía no se ha pasado lista hoy.
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <div class="EstadoIcon mb-3"><i class="fa-solid fa-clipboard-question text-danger"></i></div>
                        <h3 class="fw-bold">Resultado de la consulta</h3>
                        <p class="text-muted mb-0">Aquí aparecerá el estado de asistencia del alumno consultado.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php ImprimirCsrfScript(); ?>
<script src="assets/js/sgce-shared.js?v=1.0.0"></script>
<script src="assets/js/ConsultaPadre.js?v=1.0.0"></script>
</body>
</html>
