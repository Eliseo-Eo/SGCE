<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
require_once dirname(__DIR__) . '/config/Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession) { header('Location: index.php'); exit; }
SgceCrearTablaPlaneacionesSiNoExiste($Pdo);

$Id = (int)($_GET['Id'] ?? 0);
if ($Id <= 0) { http_response_code(404); exit('Archivo no encontrado.'); }
$Stmt = $Pdo->prepare('SELECT P.*, U.NombreCompleto, C.Nombre AS CicloNombre FROM Planeaciones P INNER JOIN Usuarios U ON U.Id = P.MaestroId INNER JOIN CiclosEscolares C ON C.Id = P.CicloId WHERE P.Id = ? LIMIT 1');
$Stmt->execute([$Id]);
$Row = $Stmt->fetch();
if (!$Row) { http_response_code(404); exit('Archivo no encontrado.'); }

$EsDocentePropietario = $UserSession['Rol'] === 'maestro' && (int)$UserSession['Id'] === (int)$Row['MaestroId'];
$EsGestion = SgcePuedeGestionarPlaneaciones($UserSession);
if (!$EsDocentePropietario && !$EsGestion) { http_response_code(403); exit('No tienes permiso para descargar este archivo.'); }

$Ruta = (string)$Row['ArchivoGuardado'];
$Base = realpath(SgceCarpetaPlaneaciones());
$Real = realpath($Ruta);
$BaseSeguro = $Base ? rtrim($Base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : '';
if (!$Base || !$Real || strpos($Real, $BaseSeguro) !== 0 || !is_file($Real)) { http_response_code(404); exit('Archivo no disponible.'); }
$Ext = pathinfo((string)$Row['ArchivoOriginal'], PATHINFO_EXTENSION);
$ArchivoDescarga = SgceNombrePlaneacionEstandar($Row['CicloNombre'] ?? 'CICLO', $Row['NombreCompleto'] ?? 'DOCENTE', $Row['MateriaNombre'] ?? 'MATERIA', (int)($Row['Numero'] ?? 1), $Ext, (int)($Row['VersionArchivo'] ?? 1));
$Mime = preg_match('/^[A-Za-z0-9][A-Za-z0-9!#$&^_.+\-\/]{0,120}$/', (string)($Row['MimeType'] ?? '')) ? (string)$Row['MimeType'] : 'application/octet-stream';
if (ob_get_length()) { ob_end_clean(); }
header('Content-Type: ' . $Mime);
header('Content-Disposition: attachment; filename="' . $ArchivoDescarga . '"');
header('Content-Length: ' . filesize($Real));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, must-revalidate');
readfile($Real);
exit;
