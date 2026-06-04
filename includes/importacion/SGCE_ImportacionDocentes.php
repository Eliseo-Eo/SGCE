<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

function UsuariosExistentesPorUsername($Pdo, $Usernames) {
    $Usernames = array_values(array_unique(array_filter(array_map('strval', $Usernames), static fn($Valor) => trim($Valor) !== '')));
    if (!$Usernames) { return []; }

    $Existentes = [];
    foreach (array_chunk($Usernames, 250) as $Chunk) {
        $Placeholders = implode(',', array_fill(0, count($Chunk), '?'));
        $Stmt = $Pdo->prepare("SELECT Id, Username, Rol, Activo FROM Usuarios WHERE Username IN ($Placeholders)");
        $Stmt->execute($Chunk);
        foreach ($Stmt->fetchAll() as $Usuario) {
            $Existentes[(string)$Usuario['Username']] = [
                'Id' => (int)$Usuario['Id'],
                'Rol' => (string)$Usuario['Rol'],
                'Activo' => (int)$Usuario['Activo'],
            ];
        }
    }

    return $Existentes;
}

function HashPasswordImportacion(&$Cache, $Password) {
    $Password = (string)$Password;
    if (!isset($Cache[$Password])) {
        $Cache[$Password] = SgcePasswordHash($Password);
    }
    return $Cache[$Password];
}

