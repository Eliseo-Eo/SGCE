<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

function BomStrip($Handle) {
    if (fgets($Handle, 4) !== "\xEF\xBB\xBF") {
        rewind($Handle);
    }
}

function ExtensionImportacion($NombreArchivo) {
    return strtolower(pathinfo((string)$NombreArchivo, PATHINFO_EXTENSION));
}

function InfoServidorSubidasImportacion() {
    $UploadTmp = trim((string)ini_get('upload_tmp_dir'));
    $SysTmp = function_exists('sys_get_temp_dir') ? trim((string)sys_get_temp_dir()) : '';
    $TmpDetectado = $UploadTmp !== '' ? $UploadTmp : $SysTmp;
    $Existe = $TmpDetectado !== '' && is_dir($TmpDetectado) ? 'sí' : 'no';
    $Escribible = $TmpDetectado !== '' && is_writable($TmpDetectado) ? 'sí' : 'no';

    return 'Temporal detectado: ' . ($TmpDetectado !== '' ? $TmpDetectado : 'sin definir') .
        ' | Existe: ' . $Existe .
        ' | Escribible: ' . $Escribible .
        ' | upload_tmp_dir: ' . ($UploadTmp !== '' ? $UploadTmp : 'usa temporal del sistema') .
        ' | upload_max_filesize: ' . (string)ini_get('upload_max_filesize') .
        ' | post_max_size: ' . (string)ini_get('post_max_size');
}

function MensajeErrorSubidaImportacion($CodigoError) {
    $CodigoError = (int)$CodigoError;
    $InfoServidor = InfoServidorSubidasImportacion();
    $MapaErrores = [
        UPLOAD_ERR_INI_SIZE => 'El archivo supera el tamaño máximo permitido por el servidor. Sube un archivo más pequeño o aumenta upload_max_filesize/post_max_size. ' . $InfoServidor,
        UPLOAD_ERR_FORM_SIZE => 'El archivo supera el tamaño máximo permitido por el formulario.',
        UPLOAD_ERR_PARTIAL => 'El archivo se subió incompleto. Intenta subirlo nuevamente.',
        UPLOAD_ERR_NO_FILE => 'No se recibió ningún archivo. Selecciona nuevamente el CSV o Excel y vuelve a importar.',
        UPLOAD_ERR_NO_TMP_DIR => 'El archivo no llegó a SGCE porque PHP no tiene una carpeta temporal válida para recibir subidas. Corrige /tmp o upload_tmp_dir en el servidor. ' . $InfoServidor,
        UPLOAD_ERR_CANT_WRITE => 'PHP recibió el archivo, pero no pudo escribirlo en la carpeta temporal del servidor. Revisa permisos de la carpeta temporal. ' . $InfoServidor,
        UPLOAD_ERR_EXTENSION => 'Una extensión de PHP bloqueó la subida del archivo.',
    ];

    if ($CodigoError !== UPLOAD_ERR_OK) {
        error_log('SGCE importación: error de subida código ' . $CodigoError . ' | ' . $InfoServidor);
    }

    return $MapaErrores[$CodigoError] ?? 'Error al subir el archivo. Código de subida: ' . $CodigoError . '. ' . $InfoServidor;
}

function ValidarArchivoImportacionSubido($Archivo) {
    if (!isset($Archivo) || !is_array($Archivo)) {
        return MensajeErrorSubidaImportacion(UPLOAD_ERR_NO_FILE);
    }

    $CodigoError = (int)($Archivo['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($CodigoError !== UPLOAD_ERR_OK) {
        return MensajeErrorSubidaImportacion($CodigoError);
    }

    if (!is_uploaded_file($Archivo['tmp_name'] ?? '')) {
        return 'No se pudo validar el archivo temporal. Selecciona nuevamente el CSV o Excel e intenta otra vez.';
    }

    if (($Archivo['size'] ?? 0) <= 0) {
        return 'El archivo está vacío.';
    }

    if (($Archivo['size'] ?? 0) > 10 * 1024 * 1024) {
        return 'El archivo no debe pesar más de 10 MB.';
    }

    $Extension = ExtensionImportacion($Archivo['name'] ?? '');
    if (!in_array($Extension, ['csv', 'xlsx'], true)) {
        return 'Solo se permiten archivos CSV o Excel .xlsx.';
    }

    $MimePermitidos = [
        'text/plain',
        'text/csv',
        'application/csv',
        'application/vnd.ms-excel',
        'application/octet-stream',
        'application/zip',
        'application/x-zip',
        'application/x-zip-compressed',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    if (function_exists('finfo_open') && is_uploaded_file($Archivo['tmp_name'] ?? '')) {
        $Finfo = finfo_open(FILEINFO_MIME_TYPE);
        $Mime = $Finfo ? finfo_file($Finfo, $Archivo['tmp_name']) : '';
        if ($Mime !== '' && !in_array($Mime, $MimePermitidos, true)) {
            return 'El archivo no parece ser CSV o Excel válido.';
        }
    }

    return '';
}

function LeerFilasImportacionSubida($Archivo, $NombresHojaPreferidos = []) {
    $Ruta = $Archivo['tmp_name'] ?? '';
    $Extension = ExtensionImportacion($Archivo['name'] ?? '');

    if ($Extension === 'csv') {
        return LeerFilasCsv($Ruta);
    }
    if ($Extension === 'xlsx') {
        return LeerFilasXlsx($Ruta, $NombresHojaPreferidos);
    }

    throw new RuntimeException('Formato de archivo no permitido.');
}

