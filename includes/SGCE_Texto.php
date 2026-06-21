<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }
$SgceBootstrapLegacy = dirname(__DIR__) . '/app/bootstrap.php';
if (is_file($SgceBootstrapLegacy)) { require_once $SgceBootstrapLegacy; }

function SgceLongitudTexto($Texto) { return \Sgce\Support\Text::length($Texto); }

function SgceNormalizarMayusculas($Valor) { return \Sgce\Support\Text::normalizeUpper($Valor); }

function SgceNormalizarNombre($Valor) { return \Sgce\Support\Text::normalizeName($Valor); }

function SgceNormalizarGrupo($Valor) { return \Sgce\Support\Text::normalizeGroup($Valor); }

function SgceValidarGrado($Valor) {
    // El campo Grado funciona como etiqueta académica configurable:
    // puede representar grados, semestres, cuatrimestres, módulos o niveles.
    return \Sgce\Support\Text::validateGrade($Valor);
}

function SgceNormalizarEtapaAcademica($Valor) { return \Sgce\Support\Text::normalizeAcademicStage($Valor); }

function SgceNormalizarTurno($Valor) { return \Sgce\Support\Text::normalizeTurn($Valor); }

function SgceNormalizarTextoUsuarios($Valor) { return SgceNormalizarMayusculas($Valor); }

function SgceNormalizarColorHex($Color, $Default = '#97051E') { return \Sgce\Support\Text::normalizeHexColor($Color, (string)$Default); }

function SgceColorAjustar($Color, $Porcentaje) { return \Sgce\Support\Text::adjustColor($Color, $Porcentaje); }

function SgceColorRgb($Color) { return \Sgce\Support\Text::colorRgb($Color); }

function SgceNormalizarMateriaPlaneacion($Texto) { return \Sgce\Support\Text::normalizePlanningSubject($Texto); }

function SgceNombreArchivoSeguro($Texto) { return \Sgce\Support\Text::safeFilename($Texto); }

function SgceNormalizarPrograma($Valor): string { return \Sgce\Support\Text::normalizeProgram($Valor); }
