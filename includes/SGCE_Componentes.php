<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceComponenteAlerta(string $Mensaje, string $Tipo = 'primary', string $Icono = 'fa-solid fa-circle-info'): string {
    $Mapa = ['primary'=>'','success'=>'SgceAlertSuccess','warning'=>'SgceAlertWarning','danger'=>'SgceAlertDanger'];
    $Clase = trim('SgceAlert ' . ($Mapa[$Tipo] ?? ''));
    $IconoClase = ($Icono !== '' && strpos($Icono, ' ') === false && str_starts_with($Icono, 'fa-')) ? 'fa-solid ' . $Icono : $Icono;
    return '<div class="' . HGlobal($Clase) . '"><span class="SgceAlertIcon"><i class="' . HGlobal($IconoClase) . '"></i></span><div>' . HGlobal($Mensaje) . '</div></div>';
}

function SgceComponenteTablaVacia(string $Mensaje = 'No hay registros para mostrar.'): string {
    return '<div class="SgceEmptyState"><i class="fa-solid fa-inbox me-1"></i>' . HGlobal($Mensaje) . '</div>';
}
