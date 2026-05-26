<?php

/*
    Archivo: ConsultaPadre.php
    Descripción: Consulta pública para madres, padres o tutores.
    Permite revisar el estado de asistencia del día escribiendo el nombre completo
    del alumno y seleccionando grado, grupo y turno. No muestra listas completas
    para proteger la confidencialidad de los estudiantes.
*/

require 'Conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Mexico_City');

function NormalizarConsultaPublica($Valor) {
    $Valor = trim((string)$Valor);
    if ($Valor === '') { return ''; }
    $Valor = preg_replace('/\s+/u', ' ', $Valor);
    if (function_exists('mb_strtoupper')) {
        return mb_strtoupper($Valor, 'UTF-8');
    }
    return strtoupper($Valor);
}

function ValidarGradoPublico($Valor) {
    $Valor = trim((string)$Valor);
    return ($Valor !== '' && preg_match('/^[0-9A-ZÁÉÍÓÚÜÑ\-]+$/u', $Valor));
}

function ValidarGrupoPublico($Valor) {
    $Valor = NormalizarConsultaPublica($Valor);
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

$GradosDisponibles = $Pdo->query("SELECT DISTINCT Grado FROM Grupos ORDER BY Grado ASC")->fetchAll();
$GruposDisponibles = $Pdo->query("SELECT DISTINCT Grupo FROM Grupos ORDER BY Grupo ASC")->fetchAll();

$NombreAlumno = '';
$Grado = '';
$Grupo = '';
$Turno = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $NombreAlumno = NormalizarConsultaPublica($_POST['NombreAlumno'] ?? '');
    $Grado = NormalizarConsultaPublica($_POST['Grado'] ?? '');
    $Grupo = ValidarGrupoPublico($_POST['Grupo'] ?? '');
    $Turno = NormalizarConsultaPublica($_POST['Turno'] ?? '');

    if ($NombreAlumno === '' || !preg_match('/^[\p{L}\s]+$/u', $NombreAlumno)) {
        $Error = 'ESCRIBE EL NOMBRE COMPLETO DEL ALUMNO, SOLO CON LETRAS Y ESPACIOS.';
    } elseif (!ValidarGradoPublico($Grado) || $Grupo === '' || !in_array($Turno, ['MATUTINO', 'VESPERTINO'], true)) {
        $Error = 'SELECCIONA GRADO, GRUPO Y TURNO CORRECTAMENTE.';
    } else {

        $StmtGrupo = $Pdo->prepare("SELECT Id, Grado, Grupo, Turno FROM Grupos WHERE Grado = ? AND Grupo = ? AND Turno = ? LIMIT 1");
        $StmtGrupo->execute([$Grado, $Grupo, $Turno]);
        $InfoGrupo = $StmtGrupo->fetch();

        if (!$InfoGrupo) {
            $Error = 'NO SE ENCONTRÓ UN GRUPO CON ESOS DATOS. REVISA GRADO, GRUPO Y TURNO.';
        } else {

            $StmtAlumno = $Pdo->prepare("SELECT Id, NombreCompleto FROM Alumnos WHERE GrupoId = ? AND NombreCompleto = ? LIMIT 1");
            $StmtAlumno->execute([(int)$InfoGrupo['Id'], $NombreAlumno]);
            $Alumno = $StmtAlumno->fetch();

            if (!$Alumno) {
                $Error = 'NO SE ENCONTRÓ UN ALUMNO CON ESOS DATOS. REVISA QUE EL NOMBRE ESTÉ COMPLETO Y ESCRITO COMO FUE REGISTRADO.';
            } else {

                $StmtTotalMaterias = $Pdo->prepare("SELECT COUNT(*) FROM Asignaciones WHERE GrupoId = ?");
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
    <title>SGCE | Consulta De Asistencia</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <link rel="apple-touch-icon" href="favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root{
            --Guinda:#7A0818;
            --Guinda2:#A10D26;
            --Azul:#2563EB;
            --Verde:#16A34A;
            --Rojo:#DC2626;
            --Amarillo:#F59E0B;
            --Fondo:#EEF2F7;
            --Texto:#1F2937;
            --Muted:#6B7280;
            --Borde:#E5E7EB;
        }
        *{ box-sizing:border-box; font-family:'Poppins','Segoe UI',sans-serif; }
        body{
            min-height:100vh;
            background:
                radial-gradient(circle at top left, rgba(122,8,24,.14), transparent 34%),
                radial-gradient(circle at bottom right, rgba(37,99,235,.10), transparent 32%),
                linear-gradient(to bottom,#F8FAFC,#EEF2F7);
            color:var(--Texto);
            overflow-x:hidden;
        }
        body::before{
            content:"";
            position:fixed;
            inset:-40%;
            pointer-events:none;
            z-index:-1;
            background:
                radial-gradient(circle at 15% 15%, rgba(161,13,38,.12), transparent 28%),
                radial-gradient(circle at 85% 25%, rgba(37,99,235,.08), transparent 30%),
                radial-gradient(circle at 50% 90%, rgba(245,158,11,.08), transparent 28%);
            animation:FondoSuave 18s ease-in-out infinite alternate;
        }
        @keyframes FondoSuave{ from{ transform:translate3d(-1%,-1%,0) scale(1); } to{ transform:translate3d(1%,1%,0) scale(1.04); } }
        @keyframes Entrada{ from{ opacity:0; transform:translateY(14px) scale(.985); } to{ opacity:1; transform:translateY(0) scale(1); } }
        .NavbarPublica{
            background:linear-gradient(135deg,var(--Guinda),var(--Guinda2));
            box-shadow:0 12px 32px rgba(122,8,24,.22);
            position:sticky;
            top:0;
            z-index:10;
        }
        .HeroCard,
        .ConsultaCard,
        .ResultadoCard{
            border:0;
            border-radius:28px;
            box-shadow:0 16px 42px rgba(15,23,42,.08);
            animation:Entrada .45s cubic-bezier(.22,.61,.36,1) both;
            overflow:hidden;
        }
        .HeroCard{
            background:linear-gradient(135deg,var(--Guinda),var(--Guinda2));
            color:white;
            position:relative;
        }
        .HeroIcon{
            width:82px;
            height:82px;
            border-radius:24px;
            background:rgba(255,255,255,.15);
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:2.2rem;
            box-shadow:inset 0 0 0 1px rgba(255,255,255,.18);
        }
        .GlassBadge{
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:9px 16px;
            border-radius:999px;
            background:rgba(255,255,255,.14);
            border:1px solid rgba(255,255,255,.20);
            font-size:.86rem;
            font-weight:700;
        }
        .form-control,
        .form-select{
            border:2px solid var(--Borde);
            border-radius:18px;
            min-height:52px;
            padding:13px 15px;
            font-weight:700;
            text-transform:uppercase;
            box-shadow:none !important;
            transition:.2s ease;
        }
        .form-control:focus,
        .form-select:focus{
            border-color:var(--Guinda);
            box-shadow:0 0 0 4px rgba(122,8,24,.10) !important;
            transform:translateY(-1px);
        }
        label{ font-weight:800; color:var(--Muted); margin-bottom:7px; }
        .BtnPrincipal,
        .BtnSecundario{
            min-height:52px;
            border-radius:999px;
            font-weight:900;
            display:inline-flex;
            justify-content:center;
            align-items:center;
            gap:9px;
            transition:.2s ease;
            text-decoration:none;
        }
        .BtnPrincipal{
            background:white;
            color:var(--Guinda);
            border:2px solid var(--Guinda);
        }
        .BtnPrincipal:hover{
            background:var(--Guinda);
            color:white;
            transform:translateY(-2px);
            box-shadow:0 12px 26px rgba(122,8,24,.22);
        }
        .BtnSecundario{
            background:white;
            color:var(--Azul);
            border:2px solid var(--Azul);
        }
        .BtnSecundario:hover{
            background:var(--Azul);
            color:white;
            transform:translateY(-2px);
            box-shadow:0 12px 26px rgba(37,99,235,.20);
        }
        .EstadoBox{
            border-radius:24px;
            padding:28px;
            text-align:center;
            border:2px solid transparent;
        }
        .EstadoBox.success{ background:rgba(22,163,74,.08); border-color:rgba(22,163,74,.35); color:#166534; }
        .EstadoBox.warning{ background:rgba(245,158,11,.10); border-color:rgba(245,158,11,.40); color:#92400E; }
        .EstadoBox.danger{ background:rgba(220,38,38,.08); border-color:rgba(220,38,38,.35); color:#991B1B; }
        .EstadoBox.secondary{ background:rgba(107,114,128,.10); border-color:rgba(107,114,128,.30); color:#374151; }
        .EstadoIcon{
            width:76px;
            height:76px;
            margin:0 auto 14px;
            border-radius:24px;
            display:flex;
            align-items:center;
            justify-content:center;
            background:white;
            font-size:2rem;
            box-shadow:0 12px 26px rgba(15,23,42,.08);
        }
        .MetricCard{
            border:1px solid #EEF2F7;
            border-radius:20px;
            padding:18px;
            background:#FFFFFF;
            height:100%;
            text-align:center;
            box-shadow:0 8px 22px rgba(15,23,42,.04);
        }
        .MetricCard .Numero{ font-size:2rem; font-weight:900; color:var(--Guinda); }
        .MetricCard .Texto{ color:var(--Muted); font-weight:800; font-size:.82rem; }
        .AvisoPrivacidad{
            border-radius:20px;
            background:#F8FAFC;
            border:1px dashed #CBD5E1;
            padding:16px;
            color:#64748B;
            font-size:.9rem;
        }
        .alert{
            border:0;
            border-radius:18px;
            box-shadow:0 10px 28px rgba(15,23,42,.08);
        }
        @media(max-width:768px){
            .container{ padding-left:18px; padding-right:18px; }
            .HeroCard{ text-align:center; }
            .HeroIcon{ margin:auto; }
            .display-6{ font-size:1.8rem; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-dark NavbarPublica py-3">
    <div class="container-fluid px-4">
        <span class="navbar-brand fw-bold fs-4 d-flex align-items-center gap-2">
            <i class="fa-solid fa-shield-heart"></i>
            SGCE
            <span class="fw-light fs-6">Consulta Familiar</span>
        </span>
        <a href="index.php" class="btn btn-outline-light rounded-pill px-4 fw-bold">
            <i class="fa-solid fa-right-to-bracket"></i> Login
        </a>
    </div>
</nav>

<main class="container py-4 py-lg-5">

    <section class="HeroCard p-4 p-lg-5 mb-4">
        <div class="row align-items-center g-4">
            <div class="col-lg-auto">
                <div class="HeroIcon"><i class="fa-solid fa-user-shield"></i></div>
            </div>
            <div class="col-lg">
                <span class="GlassBadge mb-3"><i class="fa-solid fa-lock"></i> CONSULTA PROTEGIDA POR DATOS EXACTOS</span>
                <h1 class="display-6 fw-black mb-2" style="font-weight:900;">Consulta de asistencia del día</h1>
                <p class="mb-0 opacity-75">Escribe el nombre completo del alumno y selecciona su grado, grupo y turno. El sistema solo muestra el resultado del alumno consultado.</p>
            </div>
        </div>
    </section>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="ConsultaCard bg-white p-4 h-100">
                <h4 class="fw-bold mb-1"><i class="fa-solid fa-magnifying-glass text-danger me-2"></i>Buscar alumno</h4>
                <p class="text-muted mb-4">Todos los campos son obligatorios.</p>

                <?php if($Error): ?>
                    <div class="alert alert-danger mb-4">
                        <i class="fa-solid fa-circle-exclamation me-2"></i>
                        <?= htmlspecialchars($Error, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form method="POST" autocomplete="off">
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

                    <button type="submit" class="BtnPrincipal w-100 mt-4">
                        <i class="fa-solid fa-calendar-check"></i>
                        CONSULTAR ASISTENCIA DE HOY
                    </button>
                </form>

                <div class="AvisoPrivacidad mt-4">
                    <i class="fa-solid fa-shield-halved me-2"></i>
                    Por seguridad, esta consulta no muestra listas completas ni datos de otros alumnos.
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="ResultadoCard bg-white p-4 h-100">
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
<script>
document.addEventListener('DOMContentLoaded', function(){
    function NormalizarNombre(El){
        let Valor = El.value || '';
        Valor = Valor.toUpperCase();
        Valor = Valor.replace(/[^A-ZÁÉÍÓÚÜÑ\s]/g, '');
        Valor = Valor.replace(/\s+/g, ' ');
        El.value = Valor;
    }
    document.querySelectorAll('.SoloLetrasMayus').forEach(function(El){
        El.addEventListener('input', function(){ NormalizarNombre(El); });
        El.addEventListener('blur', function(){ NormalizarNombre(El); });
    });
});
</script>
</body>
</html>
