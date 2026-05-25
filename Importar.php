<?php
require 'Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);

if (!$UserSession || $UserSession['Rol'] !== 'admin') { 
    header('Location: index.php'); 
    exit; 
}

$Error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    if (isset($_POST['ImportarAlumnos'])) {
        $GrupoId = $_POST['GrupoId'] ?? '';
        
        if (empty($GrupoId)) {
            $Error = "Por Favor, Selecciona Un Grupo Antes De Cargar El Archivo.";
        } elseif (isset($_FILES['CsvAlumnos']) && $_FILES['CsvAlumnos']['error'] == 0) {
            $File = $_FILES['CsvAlumnos']['tmp_name'];
            $Handle = fopen($File, "r");
            
            BomStrip($Handle);
            
            $Insertados = 0;
            $Stmt = $Pdo->prepare("INSERT INTO Alumnos (NombreCompleto, GrupoId) VALUES (?, ?)");
            
            while (($Data = fgetcsv($Handle, 1000, ",")) !== FALSE) {
                if (!isset($Data[0]) || empty(trim($Data[0]))) continue;
                
                $Nombre = trim($Data[0]);
                $Stmt->execute([$Nombre, $GrupoId]);
                $Insertados++;
            }
            fclose($Handle);
            header("Location: Admin.php?M=Se Importaron $Insertados Alumnos Correctamente.");
            exit;
        } else {
            $Error = "Error Al Subir El Archivo De Alumnos.";
        }
    }

    if (isset($_POST['ImportarDocentes'])) {
        if (isset($_FILES['CsvDocentes']) && $_FILES['CsvDocentes']['error'] == 0) {
            $File = $_FILES['CsvDocentes']['tmp_name'];
            $Handle = fopen($File, "r");
            
            BomStrip($Handle);
            
            $Insertados = 0;
            $Duplicados = 0;
            
            $Check = $Pdo->prepare("SELECT COUNT(*) FROM Usuarios WHERE Username = ?");
            $Stmt = $Pdo->prepare("INSERT INTO Usuarios (Username, Password, NombreCompleto, Rol) VALUES (?, ?, ?, 'maestro')");
            
            while (($Data = fgetcsv($Handle, 1000, ",")) !== FALSE) {
                if (count($Data) < 3) continue;
                
                $Nombre   = trim($Data[0]);
                $Username = trim($Data[1]);
                $Password = trim($Data[2]);
                
                if (empty($Nombre) || empty($Username) || empty($Password)) continue;
                
                $Check->execute([$Username]);
                if ($Check->fetchColumn() > 0) {
                    $Duplicados++;
                    continue;
                }
                
                $Stmt->execute([$Username, $Password, $Nombre]);
                $Insertados++;
            }
            fclose($Handle);
            
            $MsgRes = "Se Importaron $Insertados Docentes Con Éxito.";
            if ($Duplicados > 0) { $MsgRes .= " ($Duplicados Usuarios Ya Existían Y Se Omitieron)."; }
            
            header("Location: Admin.php?M=" . urlencode($MsgRes));
            exit;
        } else {
            $Error = "Error Al Subir El Archivo De Docentes.";
        }
    }
}

function BomStrip(&$Handle) {
    $Bom = fread($Handle, 3);
    if ($Bom !== "\xEF\xBB\xBF") {
        rewind($Handle);
    }
}
?>