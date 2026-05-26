<?php
require 'Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession || $UserSession['Rol'] !== 'admin') { header('Location: index.php'); exit; }

// --- LÓGICA DE PROCESAMIENTO ---
// Eliminaciones
if (isset($_GET['DelMaestro'])) { $Pdo->prepare("DELETE FROM Usuarios WHERE Id = ?")->execute([$_GET['DelMaestro']]); header("Location: Admin.php?M=Docente Eliminado"); exit; }
if (isset($_GET['DelGrupo'])) { $Pdo->prepare("DELETE FROM Grupos WHERE Id = ?")->execute([$_GET['DelGrupo']]); header("Location: Admin.php?M=Grupo Eliminado"); exit; }
if (isset($_GET['DelAlumno'])) { $Pdo->prepare("DELETE FROM Alumnos WHERE Id = ?")->execute([$_GET['DelAlumno']]); header("Location: Admin.php?M=Alumno Eliminado"); exit; }
if (isset($_GET['DelAsignacion'])) { $Pdo->prepare("DELETE FROM Asignaciones WHERE Id = ?")->execute([$_GET['DelAsignacion']]); header("Location: Admin.php?M=Materia Desasignada"); exit; }

// Altas y Ediciones Manuales
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Maestros
    if (isset($_POST['AltaMaestro'])) { $Pdo->prepare("INSERT INTO Usuarios (Username, Password, NombreCompleto, Rol) VALUES (?, ?, ?, 'maestro')")->execute([$_POST['User'], $_POST['Pass'], $_POST['Nombre']]); header("Location: Admin.php?M=Docente Registrado"); exit; }
    if (isset($_POST['EditMaestro'])) { $Pdo->prepare("UPDATE Usuarios SET NombreCompleto = ?, Username = ?, Password = ? WHERE Id = ?")->execute([$_POST['Nombre'], $_POST['User'], $_POST['Pass'], $_POST['Id']]); header("Location: Admin.php?M=Docente Actualizado"); exit; }
    
    // Grupos
    if (isset($_POST['AltaGrupo'])) { $Pdo->prepare("INSERT INTO Grupos (Grado, Grupo, Turno) VALUES (?, ?, ?)")->execute([$_POST['Grado'], $_POST['Grupo'], $_POST['Turno']]); header("Location: Admin.php?M=Grupo Creado"); exit; }
    if (isset($_POST['EditGrupo'])) { $Pdo->prepare("UPDATE Grupos SET Grado = ?, Grupo = ?, Turno = ? WHERE Id = ?")->execute([$_POST['Grado'], $_POST['Grupo'], $_POST['Turno'], $_POST['Id']]); header("Location: Admin.php?M=Grupo Actualizado"); exit; }
    
    // Alumnos
    if (isset($_POST['AltaAlumno'])) { $Pdo->prepare("INSERT INTO Alumnos (NombreCompleto, GrupoId) VALUES (?, ?)")->execute([$_POST['Nombre'], $_POST['GrupoId']]); header("Location: Admin.php?M=Alumno Inscrito"); exit; }
    if (isset($_POST['EditAlumno'])) { $Pdo->prepare("UPDATE Alumnos SET NombreCompleto = ?, GrupoId = ? WHERE Id = ?")->execute([$_POST['Nombre'], $_POST['GrupoId'], $_POST['Id']]); header("Location: Admin.php?M=Alumno Actualizado"); exit; }
    
    // Asignaciones
    if (isset($_POST['AltaAsignacion'])) { $Pdo->prepare("INSERT INTO Asignaciones (MaestroId, GrupoId, MateriaNombre) VALUES (?, ?, ?)")->execute([$_POST['MaestroId'], $_POST['GrupoId'], $_POST['Materia']]); header("Location: Admin.php?M=Materia Asignada"); exit; }
    if (isset($_POST['EditAsignacion'])) { $Pdo->prepare("UPDATE Asignaciones SET MaestroId = ?, GrupoId = ?, MateriaNombre = ? WHERE Id = ?")->execute([$_POST['MaestroId'], $_POST['GrupoId'], $_POST['Materia'], $_POST['Id']]); header("Location: Admin.php?M=Asignación Modificada"); exit; }
}

// --- CONSULTAS A LA BASE DE DATOS ---
$Maestros = $Pdo->query("SELECT * FROM Usuarios WHERE Rol='maestro' ORDER BY NombreCompleto ASC")->fetchAll();
$Grupos   = $Pdo->query("SELECT * FROM Grupos ORDER BY Turno, Grado, Grupo ASC")->fetchAll();
$Alumnos  = $Pdo->query("SELECT A.Id, A.NombreCompleto, A.GrupoId, G.Grado, G.Grupo, G.Turno FROM Alumnos A LEFT JOIN Grupos G ON A.GrupoId = G.Id ORDER BY G.Turno, G.Grado, G.Grupo, A.NombreCompleto ASC")->fetchAll();
$Asignaciones = $Pdo->query("SELECT Asn.Id, Asn.MateriaNombre, U.NombreCompleto AS Maestro, U.Id AS MaestroId, G.Id AS GrupoId, G.Grado, G.Grupo, G.Turno FROM Asignaciones Asn JOIN Usuarios U ON Asn.MaestroId = U.Id JOIN Grupos G ON Asn.GrupoId = G.Id ORDER BY U.NombreCompleto ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SGCE - Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
       :root{

    --Guinda:#7A0818;
    --GuindaHover:#5E0612;

    --Fondo:#EEF2F7;

    --Card:#FFFFFF;

    --Texto:#1F2937;

    --TextoClaro:#6B7280;

    --Borde:#E5E7EB;

    --Success:#16A34A;

    --Warning:#F59E0B;

    --Danger:#DC2626;

    --Primary:#2563EB;
}

body{

    background:
    linear-gradient(
        to bottom,
        #F8FAFC,
        #EEF2F7
    );

    font-family:'Segoe UI',sans-serif;

    color:var(--Texto);

    min-height:100vh;
}

/* NAVBAR */

.navbar-custom{

    background:
    linear-gradient(
        135deg,
        var(--Guinda),
        #A10D26
    );

    padding:14px 0;

    box-shadow:
    0 8px 24px rgba(122,8,24,0.18);
}

.navbar-brand{

    font-size:1.35rem;

    font-weight:800;

    letter-spacing:0.5px;
}

/* CONTENEDOR */

.ContainerAdmin{

    padding-top:30px;
    padding-bottom:40px;
}

/* DASHBOARD */

.CardStats{

    border:none;

    border-radius:24px;

    background:white;

    padding:22px;

    position:relative;

    overflow:hidden;

    transition:0.25s;

    box-shadow:
    0 10px 30px rgba(0,0,0,0.05);
}

.CardStats:hover{

    transform:translateY(-4px);

    box-shadow:
    0 16px 40px rgba(0,0,0,0.08);
}

.IconStats{

    width:65px;
    height:65px;

    border-radius:18px;

    display:flex;
    align-items:center;
    justify-content:center;

    font-size:28px;

    color:white;

    margin-bottom:18px;
}

.IconGuinda{
    background:linear-gradient(135deg,#7A0818,#B31332);
}

.IconAzul{
    background:linear-gradient(135deg,#2563EB,#3B82F6);
}

.IconVerde{
    background:linear-gradient(135deg,#16A34A,#22C55E);
}

.IconNaranja{
    background:linear-gradient(135deg,#F59E0B,#FBBF24);
}

.NumeroStats{

    font-size:2rem;

    font-weight:800;

    line-height:1;
}

.TextoStats{

    color:var(--TextoClaro);

    margin-top:6px;
}

/* TABS */

.nav-tabs{

    border:none;

    gap:10px;

    margin-bottom:28px;
}

.nav-tabs .nav-link{

    border:none;

    background:white;

    border-radius:16px;

    padding:14px 22px;

    font-weight:700;

    color:#6B7280;

    transition:0.2s;

    box-shadow:
    0 5px 15px rgba(0,0,0,0.04);
}

.nav-tabs .nav-link:hover{

    transform:translateY(-2px);

    color:var(--Guinda);
}

.nav-tabs .nav-link.active{

    background:
    linear-gradient(
        135deg,
        var(--Guinda),
        #A10D26
    );

    color:white;

    box-shadow:
    0 10px 24px rgba(122,8,24,0.25);
}

/* CARDS */

.card-custom{

    border:none;

    border-radius:26px;

    overflow:hidden;

    background:white;

    box-shadow:
    0 8px 24px rgba(0,0,0,0.05);

    transition:0.25s;
}

.card-custom:hover{

    transform:translateY(-3px);
}

.card-header-custom{

    padding:20px 24px;

    font-size:1rem;

    font-weight:800;

    border-bottom:1px solid #F1F5F9;

    background:#FCFCFD;
}

/* INPUTS */

.form-control,
.form-select{

    border:2px solid #E5E7EB;

    border-radius:16px;

    padding:12px 14px;

    min-height:48px;

    box-shadow:none !important;

    transition:0.2s;
}

.form-control:focus,
.form-select:focus{

    border-color:var(--Guinda);

    box-shadow:
    0 0 0 4px rgba(122,8,24,0.08) !important;
}

/* BUSCADOR */

.search-container{

    background:white;

    border-radius:18px;

    overflow:hidden;

    border:2px solid #E5E7EB;
}

.search-container .input-group-text{

    background:white;

    border:none;

    color:#9CA3AF;

    padding-left:16px;
}

.search-container .form-control{

    border:none !important;
}

/* BOTONES */

.btn{

    border-radius:14px;

    font-weight:700;

    transition:0.2s;
}

.btn:hover{

    transform:translateY(-2px);
}

.btn-guinda{

    background:
    linear-gradient(
        135deg,
        var(--Guinda),
        #A10D26
    );

    color:white;

    border:none;
}

.btn-guinda:hover{

    color:white;

    box-shadow:
    0 10px 22px rgba(122,8,24,0.25);
}

/* TABLAS */

.table{

    border-collapse:separate;

    border-spacing:0 10px;
}

.table thead th{

    border:none;

    color:#6B7280;

    font-size:0.82rem;

    text-transform:uppercase;

    letter-spacing:0.5px;
}

.table tbody tr{

    background:white;

    box-shadow:
    0 4px 12px rgba(0,0,0,0.03);

    transition:0.2s;
}

.table tbody tr:hover{

    transform:scale(1.01);

    box-shadow:
    0 8px 18px rgba(0,0,0,0.06);
}

.table td{

    vertical-align:middle;

    border:none;

    padding:16px 14px;
}

.table tbody tr td:first-child{

    border-radius:16px 0 0 16px;
}

.table tbody tr td:last-child{

    border-radius:0 16px 16px 0;
}

/* BADGES */

.badge{

    padding:9px 14px;

    border-radius:999px;

    font-weight:700;
}

/* ALERTA */

.alert{

    border:none;

    border-radius:18px;

    padding:18px;

    box-shadow:
    0 6px 18px rgba(0,0,0,0.05);
}

/* MODALES */

.modal-content{

    border:none;

    border-radius:26px;

    overflow:hidden;

    box-shadow:
    0 20px 50px rgba(0,0,0,0.18);
}

.modal-body{

    padding:28px;
}

/* SCROLLBAR */

::-webkit-scrollbar{
    width:10px;
}

::-webkit-scrollbar-thumb{

    background:#C7CBD1;

    border-radius:20px;
}
    </style>
</head>
<body>

<nav class="navbar navbar-dark navbar-custom py-2">
    <div class="container-fluid px-4">
        <span class="navbar-brand"><i class="fa-solid fa-sliders text-light"></i> SGCE | <span class="fw-light fs-6">Administrador</span></span>
        <a href="Logout.php" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="fa-solid fa-power-off"></i> Cerrar Sesión</a>
    </div>
</nav>

<div class="container-fluid px-4 mt-4">
    <?php if(isset($_GET['M'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
            <i class="fa-solid fa-check-circle me-2"></i> <?= htmlspecialchars($_GET['M']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#maestros"><i class="fa-solid fa-user-tie"></i> Maestros</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#grupos"><i class="fa-solid fa-users-rectangle"></i> Grupos</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#alumnos"><i class="fa-solid fa-children"></i> Alumnos</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#asignaciones"><i class="fa-solid fa-book-open"></i> Asignaciones</button></li>
    </ul>

    <div class="tab-content">

        <div class="tab-pane fade show active" id="maestros">
    <div class="row">

        <div class="col-xl-3 col-lg-4">

            <div class="card card-custom border-start border-3 border-danger mb-3">
                <div class="card-header-custom text-danger">
                    <i class="fa-solid fa-user-plus"></i> Registrar Maestro
                </div>

                <div class="card-body">
                    <form method="POST">

                        <input type="hidden" name="AltaMaestro">

                        <div class="mb-2">
                            <input type="text" name="Nombre" class="form-control form-control-sm" placeholder="Nombre Completo" required>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <input type="text" name="User" class="form-control form-control-sm" placeholder="Usuario" required>
                            </div>

                            <div class="col-6">
                                <input type="password" name="Pass" class="form-control form-control-sm" placeholder="Contraseña" required>
                            </div>
                        </div>

                        <button class="btn btn-sm btn-guinda w-100 fw-bold">
                            Guardar Maestro
                        </button>

                    </form>
                </div>
            </div>

            <div class="card card-custom border-start border-3 border-success">
                <div class="card-header-custom text-success">
                    <i class="fa-solid fa-file-excel"></i> Importar Excel
                </div>

                <div class="card-body">
                    <form action="Importar.php" method="POST" enctype="multipart/form-data">

                        <input type="hidden" name="ImportarDocentes">

                        <p class="text-muted small mb-2">
                            Formato CSV: <code>Nombre, Usuario, Contraseña</code>
                        </p>

                        <input type="file" name="CsvDocentes" class="form-control form-control-sm mb-3" accept=".csv" required>

                        <button type="submit" class="btn btn-sm btn-outline-success w-100 fw-bold">
                            Cargar Archivo
                        </button>

                    </form>
                </div>
            </div>

        </div>

        <div class="col-xl-9 col-lg-8">

            <div class="card card-custom p-3">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0 text-muted">Docentes Registrados</h6>

                    <div class="input-group input-group-sm search-container w-50">
                        <span class="input-group-text">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </span>

                        <input type="text" id="SearchMaestros" class="form-control" placeholder="Buscar docente o usuario...">
                    </div>
                </div>

                <div class="table-responsive">

                    <table class="table table-hover text-center align-middle" id="TableMaestros">

                        <thead>
                            <tr>
                                <th class="text-start">Nombre</th>
                                <th>Usuario</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach($Maestros as $M): ?>

                            <tr>

                                <td class="text-start searchable">
                                    <?= htmlspecialchars($M['NombreCompleto']) ?>
                                </td>

                                <td class="searchable">
                                    <?= htmlspecialchars($M['Username']) ?>
                                </td>

                                <td>

                                    <button class="btn btn-sm btn-outline-primary py-0 px-2"
                                            data-bs-toggle="modal"
                                            data-bs-target="#EM<?= $M['Id'] ?>">
                                        <i class="fa fa-pen"></i>
                                    </button>

                                    <a href="Admin.php?DelMaestro=<?= $M['Id'] ?>"
                                       class="btn btn-sm btn-outline-danger py-0 px-2"
                                       onclick="return confirm('¿Eliminar Docente?')">

                                        <i class="fa fa-trash"></i>

                                    </a>

                                </td>

                            </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

    <?php foreach($Maestros as $M): ?>

    <div class="modal fade" id="EM<?= $M['Id'] ?>" tabindex="-1">

        <div class="modal-dialog modal-sm">

            <div class="modal-content">

                <form method="POST">

                    <div class="modal-body">

                        <h6 class="mb-3 border-bottom pb-2">
                            Modificar Docente
                        </h6>

                        <input type="hidden" name="EditMaestro">
                        <input type="hidden" name="Id" value="<?= $M['Id'] ?>">

                        <label class="small text-muted">
                            Nombre
                        </label>

                        <input type="text"
                               name="Nombre"
                               value="<?= htmlspecialchars($M['NombreCompleto']) ?>"
                               class="form-control form-control-sm mb-2"
                               required>

                        <label class="small text-muted">
                            Usuario
                        </label>

                        <input type="text"
                               name="User"
                               value="<?= htmlspecialchars($M['Username']) ?>"
                               class="form-control form-control-sm mb-2"
                               required>

                        <label class="small text-muted">
                            Contraseña
                        </label>

                        <input type="text"
                               name="Pass"
                               value="<?= htmlspecialchars($M['Password']) ?>"
                               class="form-control form-control-sm mb-3"
                               required>

                        <button class="btn btn-sm btn-success w-100">
                            Guardar Cambios
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

    <?php endforeach; ?>

</div>

        <div class="tab-pane fade" id="grupos">
            <div class="row">
                <div class="col-xl-3 col-lg-4">
                    <div class="card card-custom border-start border-3 border-primary">
                        <div class="card-header-custom text-primary"><i class="fa-solid fa-plus-square"></i> Crear Grupo</div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="AltaGrupo">
                                <div class="row g-2 mb-2">
                                    <div class="col-6"><input type="text" name="Grado" class="form-control form-control-sm" placeholder="Grado (Ej: 1º)" required></div>
                                    <div class="col-6"><input type="text" name="Grupo" class="form-control form-control-sm" placeholder="Grupo (Ej: A)" required></div>
                                </div>
                                <select name="Turno" class="form-select form-select-sm mb-3" required>
                                    <option value="">Selecciona Turno...</option><option value="Matutino">Matutino</option><option value="Vespertino">Vespertino</option>
                                </select>
                                <button class="btn btn-sm btn-primary w-100 fw-bold">Guardar Grupo</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-9 col-lg-8">
    <div class="card card-custom p-3">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-muted">Grupos Existentes</h6>

            <div class="input-group input-group-sm search-container w-50">
                <span class="input-group-text">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>

                <input type="text"
                       id="SearchGrupos"
                       class="form-control"
                       placeholder="Buscar grupo o turno...">
            </div>
        </div>

        <div class="table-responsive">

            <table class="table table-hover text-center align-middle" id="TableGrupos">

                <thead>
                    <tr>
                        <th>Grado</th>
                        <th>Grupo</th>
                        <th>Turno</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach($Grupos as $G): ?>

                    <tr>

                        <td class="searchable fw-bold">
                            <?= htmlspecialchars($G['Grado']) ?>
                        </td>

                        <td class="searchable">
                            <?= htmlspecialchars($G['Grupo']) ?>
                        </td>

                        <td class="searchable">
                            <span class="badge bg-<?= $G['Turno']=='Matutino' ? 'primary' : 'warning text-dark' ?>">
                                <?= htmlspecialchars($G['Turno']) ?>
                            </span>
                        </td>

                        <td>

                            <button class="btn btn-sm btn-outline-primary py-0 px-2"
                                    data-bs-toggle="modal"
                                    data-bs-target="#EG<?= $G['Id'] ?>">

                                <i class="fa fa-pen"></i>
                            </button>

                            <a href="Admin.php?DelGrupo=<?= $G['Id'] ?>"
                               class="btn btn-sm btn-outline-danger py-0 px-2"
                               onclick="return confirm('¿Eliminar Grupo?')">

                                <i class="fa fa-trash"></i>
                            </a>

                        </td>

                    </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </div>
</div>

<!-- MODALES -->

<?php foreach($Grupos as $G): ?>

<div class="modal fade" id="EG<?= $G['Id'] ?>" tabindex="-1">

    <div class="modal-dialog modal-sm">

        <div class="modal-content">

            <form method="POST">

                <div class="modal-body">

                    <h6 class="mb-3 border-bottom pb-2">
                        Modificar Grupo
                    </h6>

                    <input type="hidden" name="EditGrupo">
                    <input type="hidden" name="Id" value="<?= $G['Id'] ?>">

                    <label class="small text-muted">Grado</label>

                    <input type="text"
                           name="Grado"
                           value="<?= htmlspecialchars($G['Grado']) ?>"
                           class="form-control form-control-sm mb-2"
                           required>

                    <label class="small text-muted">Grupo</label>

                    <input type="text"
                           name="Grupo"
                           value="<?= htmlspecialchars($G['Grupo']) ?>"
                           class="form-control form-control-sm mb-2"
                           required>

                    <label class="small text-muted">Turno</label>

                    <select name="Turno"
                            class="form-select form-select-sm mb-3"
                            required>

                        <option value="Matutino"
                            <?= $G['Turno']=='Matutino' ? 'selected' : '' ?>>
                            Matutino
                        </option>

                        <option value="Vespertino"
                            <?= $G['Turno']=='Vespertino' ? 'selected' : '' ?>>
                            Vespertino
                        </option>

                    </select>

                    <button class="btn btn-sm btn-success w-100">
                        Guardar Cambios
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<?php endforeach; ?>

</div>
</div>

        <div class="tab-pane fade" id="alumnos">
    <div class="row g-4">

        <div class="col-xl-3 col-lg-4">

            <div class="card card-custom border-0 shadow-sm mb-4">

                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="fa-solid fa-user-plus me-2 text-warning"></i>
                        Inscribir Alumno
                    </h6>
                </div>

                <div class="card-body">

                    <form method="POST">

                        <input type="hidden" name="AltaAlumno">

                        <div class="mb-3">
                            <label class="form-label small text-muted fw-bold">
                                Nombre Completo
                            </label>
                            <input type="text" name="Nombre" class="form-control" placeholder="Nombre completo" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small text-muted fw-bold">
                                Grupo
                            </label>

                            <select name="GrupoId" class="form-select" required>
                                <option value="">Seleccionar...</option>

                                <?php foreach($Grupos as $G): ?>
                                    <option value="<?= $G['Id'] ?>">
                                        <?= $G['Grado'] ?> "<?= $G['Grupo'] ?>" (<?= $G['Turno'] ?>)
                                    </option>
                                <?php endforeach; ?>

                            </select>
                        </div>

                        <button type="submit" class="btn btn-warning w-100 fw-bold text-dark">
                            <i class="fa-solid fa-plus-circle me-1"></i> Registrar
                        </button>

                    </form>

                </div>

            </div>

            <div class="card card-custom border-0 shadow-sm">

                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="fa-solid fa-file-excel me-2 text-success"></i>
                        Importar Datos
                    </h6>
                </div>

                <div class="card-body">

                    <form action="Importar.php" method="POST" enctype="multipart/form-data">

                        <input type="hidden" name="ImportarAlumnos">

                        <div class="mb-3">
                            <label class="form-label small text-muted fw-bold">
                                Grupo Destino
                            </label>

                            <select name="GrupoId" class="form-select form-select-sm" required>
                                <option value="">¿A dónde van?</option>

                                <?php foreach($Grupos as $G): ?>
                                    <option value="<?= $G['Id'] ?>">
                                        <?= $G['Grado'] ?> "<?= $G['Grupo'] ?>"
                                    </option>
                                <?php endforeach; ?>

                            </select>
                        </div>

                        <div class="mb-3">
                            <input type="file" name="CsvAlumnos" class="form-control form-control-sm" accept=".csv" required>
                        </div>

                        <button type="submit" class="btn btn-outline-success w-100 fw-bold">
                            <i class="fa-solid fa-cloud-upload me-1"></i> Cargar CSV
                        </button>

                    </form>

                </div>

            </div>

        </div>

        <div class="col-xl-9 col-lg-8">

            <div class="card card-custom shadow-sm border-0 h-100">

                <div class="card-body p-4">

                    <div class="d-flex justify-content-between align-items-center mb-4">

                        <h5 class="mb-0 fw-bold text-secondary">
                            Padrón de Alumnos
                        </h5>

                        <div class="input-group search-container" style="max-width: 300px;">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </span>

                            <input type="text" id="SearchAlumnos" class="form-control border-start-0" placeholder="Buscar alumno...">
                        </div>

                    </div>

                    <div class="table-responsive">

                        <table class="table table-hover align-middle" id="TableAlumnos">

                            <thead class="table-light">
                                <tr>
                                    <th>Nombre del Alumno</th>
                                    <th>Grupo</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach($Alumnos as $Al): ?>
                                <tr>

                                    <td class="searchable fw-medium">
                                        <?= htmlspecialchars($Al['NombreCompleto']) ?>
                                    </td>

                                    <td class="searchable">
                                        <?= $Al['Grado']
                                            ? "<span class='badge bg-light text-dark border'>".$Al['Grado']." ".$Al['Grupo']."</span>"
                                            : '<span class="text-danger small">Sin Grupo</span>' ?>
                                    </td>

                                    <td class="text-center">

                                        <button class="btn btn-sm btn-outline-primary me-2"
                                                data-bs-toggle="modal"
                                                data-bs-target="#EAl<?= $Al['Id'] ?>">
                                            <i class="fa fa-pen"></i>
                                        </button>

                                        <a href="Admin.php?DelAlumno=<?= $Al['Id'] ?>"
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('¿Confirmar baja?')">
                                            <i class="fa fa-trash"></i>
                                        </a>

                                    </td>

                                </tr>
                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>

    </div>
</div>

<!-- ✅ MODALES CORREGIDOS (FUERA DE LA TABLA) -->

<?php foreach($Alumnos as $Al): ?>

<div class="modal fade" id="EAl<?= $Al['Id'] ?>" tabindex="-1">

    <div class="modal-dialog">

        <div class="modal-content">

            <form method="POST">

                <div class="modal-body">

                    <h5 class="mb-4">Editar Alumno</h5>

                    <input type="hidden" name="EditAlumno">
                    <input type="hidden" name="Id" value="<?= $Al['Id'] ?>">

                    <div class="mb-3">
                        <label class="small">Nombre</label>
                        <input type="text"
                               name="Nombre"
                               value="<?= htmlspecialchars($Al['NombreCompleto']) ?>"
                               class="form-control"
                               required>
                    </div>

                    <div class="mb-3">
                        <label class="small">Grupo</label>

                        <select name="GrupoId" class="form-select" required>

                            <?php foreach($Grupos as $G): ?>
                                <option value="<?= $G['Id'] ?>"
                                    <?= $G['Id'] == $Al['GrupoId'] ? 'selected' : '' ?>>
                                    <?= $G['Grado'] ?> "<?= $G['Grupo'] ?>"
                                </option>
                            <?php endforeach; ?>

                        </select>

                    </div>

                    <button class="btn btn-primary w-100">
                        Guardar Cambios
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<?php endforeach; ?>

<div class="tab-pane fade" id="asignaciones">
    <div class="card card-custom shadow-sm border-0">

        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="mb-0 fw-bold text-dark">
                <i class="fa-solid fa-link text-dark me-2"></i>
                Nueva Asignación Académica
            </h6>
        </div>

        <div class="card-body p-4">

            <!-- FORMULARIO -->
            <form method="POST" class="row g-3 align-items-end mb-4">
                <input type="hidden" name="AltaAsignacion">

                <div class="col-md-4">
                    <label class="small fw-bold text-muted">Seleccionar Docente</label>
                    <select name="MaestroId" class="form-select" required>
                        <option value="">Elegir profesor...</option>
                        <?php foreach($Maestros as $M): ?>
                            <option value="<?= $M['Id'] ?>">
                                <?= htmlspecialchars($M['NombreCompleto']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="small fw-bold text-muted">Asignar Grupo</label>
                    <select name="GrupoId" class="form-select" required>
                        <option value="">Elegir grupo...</option>
                        <?php foreach($Grupos as $G): ?>
                            <option value="<?= $G['Id'] ?>">
                                <?= $G['Grado'] ?> "<?= $G['Grupo'] ?>" (<?= $G['Turno'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="small fw-bold text-muted">Nombre de la Materia</label>
                    <input type="text" name="Materia" class="form-control"
                           placeholder="Ej: Matemáticas I" required>
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-dark w-100 fw-bold">
                        <i class="fa-solid fa-plus me-1"></i> Vincular
                    </button>
                </div>
            </form>

            <!-- BUSCADOR -->
            <div class="d-flex justify-content-between align-items-center mb-3 border-top pt-4">
                <h6 class="mb-0 fw-bold text-secondary">Cargas Académicas Activas</h6>

                <div class="input-group search-container w-25">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" id="SearchAsig" class="form-control border-start-0"
                           placeholder="Buscar carga...">
                </div>
            </div>

            <!-- TABLA -->
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="TableAsig">

                    <thead class="table-light">
                        <tr>
                            <th>Docente</th>
                            <th>Materia</th>
                            <th>Grupo</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach($Asignaciones as $Asg): ?>
                        <tr>
                            <td class="searchable fw-medium">
                                <?= htmlspecialchars($Asg['Maestro']) ?>
                            </td>

                            <td class="searchable text-danger fw-bold">
                                <?= htmlspecialchars($Asg['MateriaNombre']) ?>
                            </td>

                            <td class="searchable">
                                <span class="badge bg-light text-dark border">
                                    <?= $Asg['Grado'] ?> "<?= $Asg['Grupo'] ?>"
                                </span>
                                <small class="text-muted"><?= $Asg['Turno'] ?></small>
                            </td>

                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary me-2"
                                        data-bs-toggle="modal"
                                        data-bs-target="#EAsg<?= $Asg['Id'] ?>">
                                    <i class="fa fa-pen"></i>
                                </button>

                                <a href="Admin.php?DelAsignacion=<?= $Asg['Id'] ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('¿Eliminar esta asignación?')">
                                    <i class="fa fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>

        </div>
    </div>
</div>

<!-- ================= MODALES (FUERA DE LA TABLA) ================= -->
<?php foreach($Asignaciones as $Asg): ?>
<div class="modal fade" id="EAsg<?= $Asg['Id'] ?>" tabindex="-1">

    <div class="modal-dialog modal-sm">
        <div class="modal-content">

            <form method="POST">

                <div class="modal-body">

                    <h6 class="mb-3 border-bottom pb-2">
                        Editar Asignación
                    </h6>

                    <input type="hidden" name="EditAsignacion">
                    <input type="hidden" name="Id" value="<?= $Asg['Id'] ?>">

                    <label class="small text-muted">Docente</label>
                    <select name="MaestroId" class="form-select mb-2" required>
                        <?php foreach($Maestros as $M): ?>
                            <option value="<?= $M['Id'] ?>"
                                <?= $M['Id'] == $Asg['MaestroId'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($M['NombreCompleto']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label class="small text-muted">Grupo</label>
                    <select name="GrupoId" class="form-select mb-2" required>
                        <?php foreach($Grupos as $G): ?>
                            <option value="<?= $G['Id'] ?>"
                                <?= $G['Id'] == $Asg['GrupoId'] ? 'selected' : '' ?>>
                                <?= $G['Grado'] ?> "<?= $G['Grupo'] ?>"
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label class="small text-muted">Materia</label>
                    <input type="text"
                           name="Materia"
                           value="<?= htmlspecialchars($Asg['MateriaNombre']) ?>"
                           class="form-control mb-3"
                           required>

                    <button class="btn btn-primary w-100">
                        Guardar Cambios
                    </button>

                </div>

            </form>

        </div>
    </div>

</div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// FUNCIÓN PARA EL BUSCADOR EN TIEMPO REAL
document.addEventListener("DOMContentLoaded", function() {
    function setupSearch(inputId, tableId) {
        const input = document.getElementById(inputId);
        const table = document.getElementById(tableId);
        if(!input || !table) return;
        
        input.addEventListener("keyup", function() {
            let filter = input.value.toLowerCase();
            let rows = table.getElementsByTagName("tbody")[0].getElementsByTagName("tr");
            
            for (let i = 0; i < rows.length; i++) {
                let match = false;
                let cells = rows[i].getElementsByClassName("searchable");
                for (let j = 0; j < cells.length; j++) {
                    if (cells[j].innerText.toLowerCase().indexOf(filter) > -1) {
                        match = true; break;
                    }
                }
                rows[i].style.display = match ? "" : "none";
            }
        });
    }

    setupSearch("SearchMaestros", "TableMaestros");
    setupSearch("SearchGrupos", "TableGrupos");
    setupSearch("SearchAlumnos", "TableAlumnos");
    setupSearch("SearchAsig", "TableAsig");
});
</script>
</body>
</html>