<?php
require 'Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);

if (!$UserSession || $UserSession['Rol'] !== 'admin') { 
    header('Location: index.php'); 
    exit; 
}

$Msg = '';

// --- PROCESAR ELIMINACIONES ---
if (isset($_GET['DelMaestro'])) {
    $Pdo->prepare("DELETE FROM Usuarios WHERE Id = ?")->execute([$_GET['DelMaestro']]);
    header("Location: Admin.php?M=Docente Eliminado"); exit;
}
if (isset($_GET['DelGrupo'])) {
    $Pdo->prepare("DELETE FROM Grupos WHERE Id = ?")->execute([$_GET['DelGrupo']]);
    header("Location: Admin.php?M=Grupo Eliminado"); exit;
}
if (isset($_GET['DelAlumno'])) {
    $Pdo->prepare("DELETE FROM Alumnos WHERE Id = ?")->execute([$_GET['DelAlumno']]);
    header("Location: Admin.php?M=Alumno Eliminado"); exit;
}
if (isset($_GET['DelAsignacion'])) {
    $Pdo->prepare("DELETE FROM Asignaciones WHERE Id = ?")->execute([$_GET['DelAsignacion']]);
    header("Location: Admin.php?M=Materia Desasignada"); exit;
}

// --- PROCESAR ALTAS MANUALES Y EDICIONES ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['AltaMaestro'])) {
        $Stmt = $Pdo->prepare("INSERT INTO Usuarios (Username, Password, NombreCompleto, Rol) VALUES (?, ?, ?, 'maestro')");
        $Stmt->execute([$_POST['User'], $_POST['Pass'], $_POST['Nombre']]);
        header("Location: Admin.php?M=Docente Registrado Con Éxito."); exit;
    }
    if (isset($_POST['EditMaestro'])) {
        $Stmt = $Pdo->prepare("UPDATE Usuarios SET NombreCompleto = ?, Username = ?, Password = ? WHERE Id = ?");
        $Stmt->execute([$_POST['Nombre'], $_POST['User'], $_POST['Pass'], $_POST['Id']]);
        header("Location: Admin.php?M=Docente Actualizado Con Éxito."); exit;
    }
    if (isset($_POST['AltaGrupo'])) {
        $Stmt = $Pdo->prepare("INSERT INTO Grupos (Grado, Grupo, Turno) VALUES (?, ?, ?)");
        $Stmt->execute([$_POST['Grado'], $_POST['Grupo'], $_POST['Turno']]);
        header("Location: Admin.php?M=Grupo Creado Con Éxito."); exit;
    }
    if (isset($_POST['EditGrupo'])) {
        $Stmt = $Pdo->prepare("UPDATE Grupos SET Grado = ?, Grupo = ?, Turno = ? WHERE Id = ?");
        $Stmt->execute([$_POST['Grado'], $_POST['Grupo'], $_POST['Turno'], $_POST['Id']]);
        header("Location: Admin.php?M=Grupo Actualizado Con Éxito."); exit;
    }
    if (isset($_POST['AltaAlumno'])) {
        $Stmt = $Pdo->prepare("INSERT INTO Alumnos (NombreCompleto, GrupoId) VALUES (?, ?)");
        $Stmt->execute([$_POST['Nombre'], $_POST['GrupoId']]);
        header("Location: Admin.php?M=Alumno Inscrito Con Éxito."); exit;
    }
    if (isset($_POST['EditAlumno'])) {
        $Stmt = $Pdo->prepare("UPDATE Alumnos SET NombreCompleto = ?, GrupoId = ? WHERE Id = ?");
        $Stmt->execute([$_POST['Nombre'], $_POST['GrupoId'], $_POST['Id']]);
        header("Location: Admin.php?M=Datos Del Alumno Actualizados."); exit;
    }
    if (isset($_POST['AltaAsignacion'])) {
        $Stmt = $Pdo->prepare("INSERT INTO Asignaciones (MaestroId, GrupoId, MateriaNombre) VALUES (?, ?, ?)");
        $Stmt->execute([$_POST['MaestroId'], $_POST['GrupoId'], $_POST['Materia']]);
        header("Location: Admin.php?M=Materia Asignada Correctamente."); exit;
    }
    if (isset($_POST['EditAsignacion'])) {
        $Stmt = $Pdo->prepare("UPDATE Asignaciones SET MaestroId = ?, GrupoId = ?, MateriaNombre = ? WHERE Id = ?");
        $Stmt->execute([$_POST['MaestroId'], $_POST['GrupoId'], $_POST['Materia'], $_POST['Id']]);
        header("Location: Admin.php?M=Asignación Modificada."); exit;
    }
}

if (isset($_GET['M'])) { $Msg = $_GET['M']; }

$Maestros = $Pdo->query("SELECT Id, NombreCompleto, Username, Password FROM Usuarios WHERE Rol='maestro' ORDER BY NombreCompleto ASC")->fetchAll();
$Grupos   = $Pdo->query("SELECT Id, Grado, Grupo, Turno FROM Grupos ORDER BY Turno, Grado, Grupo ASC")->fetchAll();
$Alumnos  = $Pdo->query("SELECT A.Id, A.NombreCompleto, A.GrupoId, G.Grado, G.Grupo, G.Turno FROM Alumnos A LEFT JOIN Grupos G ON A.GrupoId = G.Id ORDER BY G.Turno, G.Grado, G.Grupo, A.NombreCompleto ASC")->fetchAll();
$Asignaciones = $Pdo->query("SELECT Asn.Id, Asn.MateriaNombre, U.NombreCompleto AS Maestro, U.Id AS MaestroId, G.Id AS GrupoId, G.Grado, G.Grupo, G.Turno FROM Asignaciones Asn JOIN Usuarios U ON Asn.MaestroId = U.Id JOIN Grupos G ON Asn.GrupoId = G.Id ORDER BY U.NombreCompleto ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>EST 101 - Panel General</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --EstGuindaOscuro: #56040E; --EstGuindaBase: #7A0818; }
        body { background-color: #F8F9FA; font-family: 'Segoe UI', sans-serif; }
        .NavbarEst { background-color: var(--EstGuindaOscuro); border-bottom: 3px solid var(--EstGuindaBase); }
        .CardEst { border: none; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); background: white; margin-bottom: 15px; }
        .BtnEst { background-color: var(--EstGuindaBase); color: white; border: none; }
        .BtnEst:hover { background-color: var(--EstGuindaOscuro); color: white; }
        .SearchBox { border-radius: 20px; padding-left: 35px; border: 1px solid #ced4da; background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="%236c757d" class="bi bi-search" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>'); background-repeat: no-repeat; background-position: 12px center; }
        .BadgeM { background-color: #0d6efd; }
        .BadgeV { background-color: #fd7e14; }
        .SeparadorFlujo { border-left: 3px dashed #dee2e6; margin-left: 15px; padding-left: 15px; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark NavbarEst mb-4 shadow-sm">
    <div class="container-fluid px-4">
        <span class="navbar-brand fw-bold"><i class="fa-solid fa-sliders"></i> EST 101 &nbsp;|&nbsp; <small class="fw-normal fs-6 text-white-50">Administrador General</small></span>
        <a href="Logout.php" class="btn btn-sm btn-outline-light"><i class="fa-solid fa-power-off"></i> Cerrar Sesión</a>
    </div>
</nav>

<div class="container-fluid px-4">
    <?php if($Msg): ?> <div class="alert alert-success border-0 shadow-sm mb-4"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($Msg) ?></div> <?php endif; ?>

    <div class="row">
        <div class="col-xl-4 col-lg-5">
            
            <div class="mb-2 fw-bold text-uppercase text-secondary small"><i class="fa-solid fa-user-gear"></i> Paso 1: Personal Docente</div>
            
            <div class="card CardEst p-3 border-start border-3 border-danger">
                <h6 class="fw-bold mb-2 text-dark"><i class="fa-solid fa-user-tie text-danger"></i> 1.1 Registrar Docente Manual</h6>
                <form method="POST">
                    <input type="hidden" name="AltaMaestro">
                    <div class="mb-2"><input type="text" name="Nombre" class="form-control form-control-sm" placeholder="Nombre Completo" required></div>
                    <div class="row g-2 mb-2">
                        <div class="col-6"><input type="text" name="User" class="form-control form-control-sm" placeholder="Usuario" required></div>
                        <div class="col-6"><input type="text" name="Pass" class="form-control form-control-sm" placeholder="Contraseña" required></div>
                    </div>
                    <button type="submit" class="btn btn-sm BtnEst w-100"><i class="fa-solid fa-floppy-disk"></i> Guardar Maestro</button>
                </form>
            </div>

            <div class="card CardEst p-3 border-start border-3 border-success bg-light bg-opacity-50">
                <h6 class="fw-bold mb-1 text-success"><i class="fa-solid fa-file-excel text-success"></i> 1.2 Importar Docentes (Excel)</h6>
                <p class="text-muted fs-7 mb-2">Estructura CSV: <code>Nombre, Usuario, Contraseña</code></p>
                <form action="Importar.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="ImportarDocentes">
                    <div class="input-group input-group-sm mb-2">
                        <label class="input-group-text bg-success text-white" for="CsvDocentes"><i class="fa-solid fa-cloud-arrow-up"></i></label>
                        <input type="file" name="CsvDocentes" class="form-control" id="CsvDocentes" accept=".csv" required>
                    </div>
                    <button type="submit" class="btn btn-sm btn-success w-100 fw-bold"><i class="fa-solid fa-bolt"></i> Cargar Bloque Docentes</button>
                </form>
            </div>

            <hr class="my-3 text-muted opacity-25">

            <div class="mb-2 fw-bold text-uppercase text-secondary small"><i class="fa-solid fa-school-flag"></i> Paso 2: Estructura de Salones</div>

            <div class="card CardEst p-3 border-start border-3 border-primary">
                <h6 class="fw-bold mb-2 text-dark"><i class="fa-solid fa-users-rectangle text-primary"></i> 2.1 Crear Grado, Grupo y Turno</h6>
                <form method="POST">
                    <input type="hidden" name="AltaGrupo">
                    <div class="row g-2 mb-2">
                        <div class="col-6"><input type="text" name="Grado" class="form-control form-control-sm" placeholder="Ej: 1º" required></div>
                        <div class="col-6"><input type="text" name="Grupo" class="form-control form-control-sm" placeholder="Ej: C" required></div>
                    </div>
                    <div class="mb-2">
                        <select name="Turno" class="form-select form-select-sm" required>
                            <option value="">Selecciona Turno...</option>
                            <option value="Matutino">Matutino</option>
                            <option value="Vespertino">Vespertino</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-sm BtnEst w-100"><i class="fa-solid fa-plus"></i> Crear Grupo</button>
                </form>
            </div>

            <hr class="my-3 text-muted opacity-25">

            <div class="mb-2 fw-bold text-uppercase text-secondary small"><i class="fa-solid fa-children"></i> Paso 3: Registro de Alumnado</div>

            <div class="card CardEst p-3 border-start border-3 border-warning">
                <h6 class="fw-bold mb-2 text-dark"><i class="fa-solid fa-graduation-cap text-warning"></i> 3.1 Inscribir Alumno Manual</h6>
                <form method="POST">
                    <input type="hidden" name="AltaAlumno">
                    <div class="mb-2"><input type="text" name="Nombre" class="form-control form-control-sm" placeholder="Nombre Completo del Alumno" required></div>
                    <div class="mb-2">
                        <select name="GrupoId" class="form-select form-select-sm" required>
                            <option value="">Asignar a qué Salón...</option>
                            <?php foreach($Grupos as $G): ?>
                                <option value="<?= $G['Id'] ?>"><?= $G['Grado'] ?> "<?= $G['Grupo'] ?>" - <?= $G['Turno'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-sm BtnEst w-100"><i class="fa-solid fa-user-plus"></i> Inscribir Alumno</button>
                </form>
            </div>

            <div class="card CardEst p-3 border-start border-3 border-success bg-light bg-opacity-50">
                <h6 class="fw-bold mb-1 text-success"><i class="fa-solid fa-file-excel text-success"></i> 3.2 Importar Alumnos (Excel)</h6>
                <form action="Importar.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="ImportarAlumnos">
                    <div class="mb-2">
                        <select name="GrupoId" class="form-select form-select-sm" required>
                            <option value="">¿A qué grupo se cargará la lista?</option>
                            <?php foreach($Grupos as $G): ?>
                                <option value="<?= $G['Id'] ?>"><?= $G['Grado'] ?> "<?= $G['Grupo'] ?>" - <?= $G['Turno'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="input-group input-group-sm mb-2">
                        <label class="input-group-text bg-success text-white" for="CsvAlumnos"><i class="fa-solid fa-cloud-arrow-up"></i></label>
                        <input type="file" name="CsvAlumnos" class="form-control" id="CsvAlumnos" accept=".csv" required>
                    </div>
                    <button type="submit" class="btn btn-sm btn-success w-100 fw-bold"><i class="fa-solid fa-bolt"></i> Cargar Lista de Alumnos</button>
                </form>
            </div>

            <hr class="my-3 text-muted opacity-25">

            <div class="mb-2 fw-bold text-uppercase text-secondary small"><i class="fa-solid fa-book-open"></i> Paso 4: Vinculación Docente</div>

            <div class="card CardEst p-3 border-start border-3 border-dark">
                <h6 class="fw-bold mb-2 text-dark"><i class="fa-solid fa-book-bookmark text-secondary"></i> 4.1 Asignar Carga Académica</h6>
                <form method="POST">
                    <input type="hidden" name="AltaAsignacion">
                    <div class="mb-2">
                        <select name="MaestroId" class="form-select form-select-sm" required>
                            <option value="">Selecciona Docente...</option>
                            <?php foreach($Maestros as $M): ?> <option value="<?= $M['Id'] ?>"><?= $M['NombreCompleto'] ?></option> <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <select name="GrupoId" class="form-select form-select-sm" required>
                            <option value="">Selecciona Grupo y Turno...</option>
                            <?php foreach($Grupos as $G): ?> <option value="<?= $G['Id'] ?>"><?= $G['Grado'] ?> "<?= $G['Grupo'] ?>" - <?= $G['Turno'] ?></option> <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2"><input type="text" name="Materia" class="form-control form-control-sm" placeholder="Nombre de la Materia (Ej: Matemáticas I)" required></div>
                    <button type="submit" class="btn btn-sm btn-dark w-100"><i class="fa-solid fa-link"></i> Vincular Materia</button>
                </form>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7">
            <div class="card CardEst p-3">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                    <h6 class="fw-bold text-muted mb-0"><i class="fa-solid fa-table-list"></i> Plantilla De Docentes</h6>
                    <input type="text" id="FiltroMaestros" class="form-control form-control-sm SearchBox w-50" placeholder="Buscar Maestro...">
                </div>
                <div class="table-responsive" style="max-height: 160px;">
                    <table class="table table-sm table-hover text-center align-middle small" id="TablaMaestros">
                        <thead><tr><th>Nombre</th><th>Usuario</th><th>Contraseña</th><th>Acciones</th></tr></thead>
                        <tbody>
                            <?php foreach($Maestros as $M): ?>
                            <tr>
                                <td class="text-start DataBuscar"><?= htmlspecialchars($M['NombreCompleto']) ?></td>
                                <td class="DataBuscar"><?= htmlspecialchars($M['Username']) ?></td>
                                <td><?= htmlspecialchars($M['Password']) ?></td>
                                <td>
                                    <button class="btn btn-xs btn-outline-primary py-0 px-2 fs-7" data-bs-toggle="modal" data-bs-target="#EditM<?= $M['Id'] ?>"><i class="fa-solid fa-pen-to-square"></i> Modificar</button>
                                    <a href="Admin.php?DelMaestro=<?= $M['Id'] ?>" class="btn btn-xs btn-outline-danger py-0 px-2 fs-7" onclick="return confirm('¿Borrar Maestro?')"><i class="fa-solid fa-trash"></i> Eliminar</a>
                                </td>
                            </tr>
                            <div class="modal fade" id="EditM<?= $M['Id'] ?>" tabindex="-1"><div class="modal-dialog modal-sm"><div class="modal-content"><form method="POST">
                                <div class="modal-header py-2"><h6 class="modal-title">Modificar Docente</h6></div>
                                <div class="modal-body">
                                    <input type="hidden" name="EditMaestro"><input type="hidden" name="Id" value="<?= $M['Id'] ?>">
                                    <div class="mb-2"><label class="small text-muted">Nombre</label><input type="text" name="Nombre" value="<?= $M['NombreCompleto'] ?>" class="form-control form-control-sm" required></div>
                                    <div class="mb-2"><label class="small text-muted">Usuario</label><input type="text" name="User" value="<?= $M['Username'] ?>" class="form-control form-control-sm" required></div>
                                    <div class="mb-2"><label class="small text-muted">Clave</label><input type="text" name="Pass" value="<?= $M['Password'] ?>" class="form-control form-control-sm" required></div>
                                </div>
                                <div class="modal-footer py-1"><button type="submit" class="btn btn-xs btn-success">Guardar</button></div>
                            </form></div></div></div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card CardEst p-3">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                    <h6 class="fw-bold text-muted mb-0"><i class="fa-solid fa-layer-group"></i> Configuración De Grupos</h6>
                    <input type="text" id="FiltroGrupos" class="form-control form-control-sm SearchBox w-50" placeholder="Buscar Grupo...">
                </div>
                <div class="table-responsive" style="max-height: 160px;">
                    <table class="table table-sm table-hover text-center align-middle small" id="TablaGrupos">
                        <thead><tr><th>Grado</th><th>Grupo</th><th>Turno</th><th>Acciones</th></tr></thead>
                        <tbody>
                            <?php foreach($Grupos as $G): ?>
                            <tr>
                                <td class="DataBuscar fw-bold"><?= htmlspecialchars($G['Grado']) ?></td>
                                <td class="DataBuscar"><?= htmlspecialchars($G['Grupo']) ?></td>
                                <td class="DataBuscar"><span class="badge <?= $G['Turno']=='Matutino'?'BadgeM':'BadgeV' ?>"><?= $G['Turno'] ?></span></td>
                                <td>
                                    <button class="btn btn-xs btn-outline-primary py-0 px-2 fs-7" data-bs-toggle="modal" data-bs-target="#EditG<?= $G['Id'] ?>"><i class="fa-solid fa-pen-to-square"></i> Modificar</button>
                                    <a href="Admin.php?DelGrupo=<?= $G['Id'] ?>" class="btn btn-xs btn-outline-danger py-0 px-2 fs-7" onclick="return confirm('¿Eliminar Grupo?')"><i class="fa-solid fa-trash"></i> Eliminar</a>
                                </td>
                            </tr>
                            <div class="modal fade" id="EditG<?= $G['Id'] ?>" tabindex="-1"><div class="modal-dialog modal-sm"><div class="modal-content"><form method="POST">
                                <div class="modal-header py-2"><h6 class="modal-title">Modificar Grupo</h6></div>
                                <div class="modal-body">
                                    <input type="hidden" name="EditGrupo"><input type="hidden" name="Id" value="<?= $G['Id'] ?>">
                                    <div class="mb-2"><label class="small text-muted">Grado</label><input type="text" name="Grado" value="<?= $G['Grado'] ?>" class="form-control form-control-sm" required></div>
                                    <div class="mb-2"><label class="small text-muted">Grupo</label><input type="text" name="Grupo" value="<?= $G['Grupo'] ?>" class="form-control form-control-sm" required></div>
                                    <div class="mb-2"><label class="small text-muted">Turno</label>
                                        <select name="Turno" class="form-select form-select-sm" required>
                                            <option value="Matutino" <?= $G['Turno']=='Matutino'?'selected':'' ?>>Matutino</option>
                                            <option value="Vespertino" <?= $G['Turno']=='Vespertino'?'selected':'' ?>>Vespertino</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer py-1"><button type="submit" class="btn btn-xs btn-success">Guardar</button></div>
                            </form></div></div></div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card CardEst p-3">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                    <h6 class="fw-bold text-muted mb-0"><i class="fa-solid fa-children"></i> Alumnos Inscritos</h6>
                    <input type="text" id="FiltroAlumnos" class="form-control form-control-sm SearchBox w-50" placeholder="Buscar Alumno...">
                </div>
                <div class="table-responsive" style="max-height: 180px;">
                    <table class="table table-sm table-hover text-center align-middle small" id="TablaAlumnos">
                        <thead><tr><th>Nombre Completo</th><th>Salón Asignado</th><th>Acciones</th></tr></thead>
                        <tbody>
                            <?php foreach($Alumnos as $Al): ?>
                            <tr>
                                <td class="text-start DataBuscar"><?= htmlspecialchars($Al['NombreCompleto']) ?></td>
                                <td class="DataBuscar"><?= $Al['Grado'] ? $Al['Grado']." '".$Al['Grupo']."' - ".$Al['Turno'] : '<span class="text-danger">Sin Grupo</span>' ?></td>
                                <td>
                                    <button class="btn btn-xs btn-outline-primary py-0 px-2 fs-7" data-bs-toggle="modal" data-bs-target="#EditAl<?= $Al['Id'] ?>"><i class="fa-solid fa-pen-to-square"></i> Modificar</button>
                                    <a href="Admin.php?DelAlumno=<?= $Al['Id'] ?>" class="btn btn-xs btn-outline-danger py-0 px-2 fs-7" onclick="return confirm('¿Baja De Alumno?')"><i class="fa-solid fa-trash"></i> Eliminar</a>
                                </td>
                            </tr>
                            <div class="modal fade" id="EditAl<?= $Al['Id'] ?>" tabindex="-1"><div class="modal-dialog modal-sm"><div class="modal-content"><form method="POST">
                                <div class="modal-header py-2"><h6 class="modal-title">Modificar Alumno</h6></div>
                                <div class="modal-body">
                                    <input type="hidden" name="EditAlumno"><input type="hidden" name="Id" value="<?= $Al['Id'] ?>">
                                    <div class="mb-2"><label class="small text-muted">Nombre</label><input type="text" name="Nombre" value="<?= $Al['NombreCompleto'] ?>" class="form-control form-control-sm" required></div>
                                    <div class="mb-2"><label class="small text-muted">Grupo / Turno</label>
                                        <select name="GrupoId" class="form-select form-select-sm" required>
                                            <?php foreach($Grupos as $G): ?> <option value="<?= $G['Id'] ?>" <?= $G['Id'] == $Al['GrupoId'] ? 'selected' : '' ?>><?= $G['Grado'] ?> "<?= $G['Grupo'] ?>" - <?= $G['Turno'] ?></option> <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer py-1"><button type="submit" class="btn btn-xs btn-success">Guardar</button></div>
                            </form></div></div></div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card CardEst p-3">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                    <h6 class="fw-bold text-muted mb-0"><i class="fa-solid fa-graduation-cap"></i> Distribución De Clases</h6>
                    <input type="text" id="FiltroAsignaciones" class="form-control form-control-sm SearchBox w-50" placeholder="Buscar Materia O Docente...">
                </div>
                <div class="table-responsive" style="max-height: 180px;">
                    <table class="table table-sm table-hover text-center align-middle small" id="TablaAsignaciones">
                        <thead><tr><th>Docente</th><th>Materia</th><th>Grupo</th><th>Acciones</th></tr></thead>
                        <tbody>
                            <?php foreach($Asignaciones as $Asg): ?>
                            <tr>
                                <td class="text-start DataBuscar"><?= htmlspecialchars($Asg['Maestro']) ?></td>
                                <td class="fw-bold text-secondary DataBuscar"><?= htmlspecialchars($Asg['MateriaNombre']) ?></td>
                                <td class="DataBuscar"><?= $Asg['Grado'] ?> "<?= $Asg['Grupo'] ?>" - <small class="fw-bold text-muted"><?= $Asg['Turno'] ?></small></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary py-0 px-2" data-bs-toggle="modal" data-bs-target="#EditAsg<?= $Asg['Id'] ?>"><i class="fa-solid fa-pen-to-square"></i> Modificar</button>
                                        <a href="Exportar.php?AsignacionId=<?= $Asg['Id'] ?>&Tipo=Excel" class="btn btn-outline-success py-0 px-2" title="Descargar Excel"><i class="fa-solid fa-file-excel"></i></a>
                                        <a href="Exportar.php?AsignacionId=<?= $Asg['Id'] ?>&Tipo=Pdf" target="_blank" class="btn btn-outline-danger py-0 px-2" title="Ver PDF / Imprimir"><i class="fa-solid fa-file-pdf"></i></a>
                                        <a href="Admin.php?DelAsignacion=<?= $Asg['Id'] ?>" class="btn btn-outline-danger py-0 px-2" onclick="return confirm('¿Retirar Materia?')"><i class="fa-solid fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <div class="modal fade" id="EditAsg<?= $Asg['Id'] ?>" tabindex="-1"><div class="modal-dialog modal-sm"><div class="modal-content"><form method="POST">
                                <div class="modal-header py-2"><h6 class="modal-title">Modificar Carga</h6></div>
                                <div class="modal-body">
                                    <input type="hidden" name="EditAsignacion"><input type="hidden" name="Id" value="<?= $Asg['Id'] ?>">
                                    <div class="mb-2"><label class="small text-muted">Profesor</label>
                                        <select name="MaestroId" class="form-select form-select-sm" required>
                                            <?php foreach($Maestros as $M): ?> <option value="<?= $M['Id'] ?>" <?= $M['Id'] == $Asg['MaestroId'] ? 'selected' : '' ?>><?= $M['NombreCompleto'] ?></option> <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-2"><label class="small text-muted">Grupo Y Turno</label>
                                        <select name="GrupoId" class="form-select form-select-sm" required>
                                            <?php foreach($Grupos as $G): ?> <option value="<?= $G['Id'] ?>" <?= $G['Id'] == $Asg['GrupoId'] ? 'selected' : '' ?>><?= $G['Grado'] ?> "<?= $G['Grupo'] ?>" - <?= $G['Turno'] ?></option> <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-2"><label class="small text-muted">Asignatura</label><input type="text" name="Materia" value="<?= $Asg['MateriaNombre'] ?>" class="form-control form-control-sm" required></div>
                                </div>
                                <div class="modal-footer py-1"><button type="submit" class="btn btn-xs btn-success">Actualizar</button></div>
                            </form></div></div></div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    function AsociarBusqueda(InputId, TableId) {
        const Input = document.getElementById(InputId);
        const Table = document.getElementById(TableId);
        if(!Input || !Table) return;
        Input.addEventListener("keyup", function() {
            const Query = Input.value.toLowerCase().trim();
            const Rows = Table.getElementsByTagName("tbody")[0].getElementsByTagName("tr");
            for (let I = 0; I < Rows.length; I++) {
                let Match = false;
                const Targets = Rows[I].getElementsByClassName("DataBuscar");
                for (let J = 0; J < Targets.length; J++) {
                    const Text = Targets[J].textContent || Targets[J].innerText;
                    if (Text.toLowerCase().indexOf(Query) > -1) { Match = true; break; }
                }
                Rows[I].style.display = Match ? "" : "none";
            }
        });
    }
    AsociarBusqueda("FiltroMaestros", "TablaMaestros");
    AsociarBusqueda("FiltroGrupos", "TablaGrupos");
    AsociarBusqueda("FiltroAlumnos", "TablaAlumnos");
    AsociarBusqueda("FiltroAsignaciones", "TablaAsignaciones");

    const Alertas = document.querySelectorAll('.alert-success');
    Alertas.forEach(function(Alerta) {
        setTimeout(function() {
            Alerta.style.transition = "opacity 0.6s ease";
            Alerta.style.opacity = "0";
            setTimeout(function() { Alerta.remove(); }, 600);
        }, 3000);
    });
});
</script>
</body>
</html>