<?php
require 'Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);

if (!$UserSession || $UserSession['Rol'] !== 'maestro') { 
    header('Location: index.php'); 
    exit; 
}

$AsignacionId = $_GET['AsignacionId'] ?? 0;

$Stmt = $Pdo->prepare("SELECT A.*, G.Grado, G.Grupo, G.Turno FROM Asignaciones A JOIN Grupos G ON A.GrupoId = G.Id WHERE A.Id = ? AND A.MaestroId = ?");
$Stmt->execute([$AsignacionId, $UserSession['Id']]);
$InfoClase = $Stmt->fetch();

if (!$InfoClase) { die("Acceso Denegado O Grupo No Encontrado."); }

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['GuardarNotes'])) {
    foreach ($_POST['Notas'] as $AlumnoId => $Calificacion) {
        if ($Calificacion !== '') {
            $Stmt = $Pdo->prepare("
                INSERT INTO Calificaciones (AlumnoId, AsignacionId, Calificacion) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE Calificacion = VALUES(Calificacion)
            ");
            $Stmt->execute([$AlumnoId, $AsignacionId, $Calificacion]);
        }
    }
    header("Location: Calificar.php?AsignacionId=$AsignacionId&Success=1");
    exit;
}

$Stmt = $Pdo->prepare("
    SELECT Al.Id AS AlumnoId, Al.NombreCompleto, C.Calificacion 
    FROM Alumnos Al
    LEFT JOIN Calificaciones C ON C.AlumnoId = Al.Id AND C.AsignacionId = ?
    WHERE Al.GrupoId = ?
    ORDER BY Al.NombreCompleto ASC
");
$Stmt->execute([$AsignacionId, $InfoClase['GrupoId']]);
$Alumnos = $Stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>EST 101 - Evaluar Grupo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4" style="max-width: 800px;">
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="fw-bold mb-0">
                <i class="fa-solid fa-book"></i> <?= htmlspecialchars($InfoClase['MateriaNombre']) ?> 
                <span class="text-muted fs-5">(<?= $InfoClase['Grado'] ?> "<?= $InfoClase['Grupo'] ?>")</span>
            </h3>
            <span class="badge <?= $InfoClase['Turno']=='Matutino'?'bg-primary':'bg-warning text-dark' ?> mb-2"><i class="fa-solid <?= $InfoClase['Turno']=='Matutino'?'fa-sun':'fa-moon' ?>"></i> Turno <?= $InfoClase['Turno'] ?></span>
            <br>
            <a href="Maestro.php" class="text-decoration-none small text-secondary"><i class="fa-solid fa-arrow-left"></i> Volver a mis asignaturas</a>
        </div>
    </div>

    <?php if(isset($_GET['Success'])): ?> <div class="alert alert-success border-0 shadow-sm py-2"><i class="fa-solid fa-square-check"></i> Calificaciones Guardadas Exitosamente.</div> <?php endif; ?>
    
    <div id="JsAlert" class="alert alert-warning border-0 shadow-sm py-2 d-none"></div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="POST" id="FormCalificaciones">
                <input type="hidden" name="GuardarNotes">
                <table class="table table-hover align-middle table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre Completo Del Alumno</th>
                            <th style="width: 140px;" class="text-center">Calificación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($Alumnos)): ?>
                            <tr><td colspan="2" class="text-center text-muted"><i class="fa-solid fa-folder-open"></i> No Hay Alumnos Inscritos.</td></tr>
                        <?php else: ?>
                            <?php foreach($Alumnos as $Al): ?>
                                <tr>
                                    <td><i class="fa-solid fa-user text-black-50 me-2" style="font-size:0.85rem"></i> <?= htmlspecialchars($Al['NombreCompleto']) ?></td>
                                    <td>
                                        <input type="number" name="Notas[<?= $Al['AlumnoId'] ?>]" 
                                               class="form-control form-control-sm text-center fw-bold InputNota" 
                                               step="0.1" min="0" max="10" 
                                               data-original="<?= $al['Calificacion'] !== null ? $al['Calificacion'] : '' ?>"
                                               value="<?= $Al['Calificacion'] !== null ? $Al['Calificacion'] : '' ?>" placeholder="-">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php if(!empty($Alumnos)): ?>
                    <button type="submit" class="btn btn-success btn-sm float-end mt-2 px-4 fw-bold"><i class="fa-solid fa-floppy-disk"></i> Guardar Notas</button>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const Alertas = document.querySelectorAll('.alert-success');
    Alertas.forEach(function(Al) {
        setTimeout(function() {
            Al.style.transition = "opacity 0.6s ease";
            Al.style.opacity = "0";
            setTimeout(function() { Al.remove(); }, 600);
        }, 3000);
    });

    document.getElementById('FormCalificaciones').addEventListener('submit', function(E) {
        const Inputs = document.querySelectorAll('.InputNota');
        const Alerta = document.getElementById('JsAlert');
        let HuboCambios = false;
        let TieneCeros = false;

        Inputs.forEach(Input => {
            const ValorOriginal = Input.getAttribute('data-original');
            const ValorActual = Input.value;
            if (ValorActual !== ValorOriginal) { HuboCambios = true; }
            if (ValorActual !== '' && parseFloat(ValorActual) === 0) { TieneCeros = true; }
        });

        if (!HuboCambios) {
            E.preventDefault();
            Alerta.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> <strong>¡Sin Cambios!</strong> No Has Modificado Ningún Valor De La Lista.';
            Alerta.classList.remove('d-none');
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
        }

        if (TieneCeros) {
            if(!confirm('Has Ingresado Una Nota De "0". ¿Quieres Continuar De Todos Modos?')) {
                E.preventDefault();
            }
        }
    });
});
</script>
</body>
</html>