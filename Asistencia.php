<?php
require_once 'Conexion.php';

// Capturamos el momento exacto (Fecha y Hora)
$MomentoRegistro = date('Y-m-d H:i:s');
$AsignacionId = $_GET['id'] ?? null;
$Mensaje = "";

// 1. Lógica para guardar asistencia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {
    $AsignacionId = $_POST['asignacion_id'];
    
    try {
        foreach ($_POST['estado'] as $AlumnoId => $Estado) {
            // Guardamos el momento exacto en que se presionó el botón
            $Stmt = $Pdo->prepare("INSERT INTO Asistencias (AsignacionId, AlumnoId, Fecha, Estado) 
                                   VALUES (?, ?, ?, ?)");
            $Stmt->execute([$AsignacionId, $AlumnoId, $MomentoRegistro, $Estado]);
        }
        $Mensaje = "<div class='alert alert-success'>Asistencia registrada correctamente a las: " . $MomentoRegistro . "</div>";
    } catch (PDOException $e) {
        $Mensaje = "<div class='alert alert-danger'>Error al guardar: " . $e->getMessage() . "</div>";
    }
}

// 2. Cargar alumnos del grupo asignado
$Alumnos = [];
if ($AsignacionId) {
    $Stmt = $Pdo->prepare("SELECT a.Id, a.NombreCompleto 
                           FROM Alumnos a 
                           JOIN Asignaciones asig ON a.GrupoId = asig.GrupoId 
                           WHERE asig.Id = ?");
    $Stmt->execute([$AsignacionId]);
    $Alumnos = $Stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pase de Lista - EST 101</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
    <div class="container bg-white p-4 shadow-sm rounded" style="max-width: 800px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3><i class="fa-solid fa-clock text-danger"></i> Pase de Lista</h3>
            <a href="Maestro.php" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> Volver al Panel</a>
        </div>
        
        <p class="text-muted">Momento de registro: <strong><?= $MomentoRegistro ?></strong></p>
        <?= $Mensaje ?>
        
        <?php if ($AsignacionId && !empty($Alumnos)): ?>
            <form method="POST">
                <input type="hidden" name="asignacion_id" value="<?= $AsignacionId ?>">
                
                <table class="table table-striped align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Nombre del Alumno</th>
                            <th style="width: 220px;">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($Alumnos as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['NombreCompleto']) ?></td>
                            <td>
                                <select name="estado[<?= $a['Id'] ?>]" class="form-select form-select-sm">
                                    <option value="A">Asistencia (A)</option>
                                    <option value="F">Falta (F)</option>
                                    <option value="R">Retardo (R)</option>
                                    <option value="J">Justificante (J)</option>
                                </select>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="submit" name="guardar" class="btn btn-danger w-100 fw-bold">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar Asistencia
                </button>
            </form>
        <?php else: ?>
            <div class="alert alert-warning">No se encontraron alumnos vinculados a esta materia.</div>
        <?php endif; ?>
    </div>
</body>
</html>