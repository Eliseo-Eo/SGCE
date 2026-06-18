<?php
if (!defined('SGCE_APP')) { define('SGCE_APP', true); }

function SgceApiJson(array $Payload, int $Status = 200): void {
    http_response_code($Status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($Payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function SgceCapturarPartialAdmin(string $Ruta, array $Variables = []): string {
    if (!is_file($Ruta)) { return ''; }
    extract($Variables, EXTR_SKIP);
    ob_start();
    require $Ruta;
    return (string)ob_get_clean();
}

function SgceAdminApiResponder(string $TabEsperada, string $VistaArchivo): void {
    require_once dirname(__DIR__, 2) . '/config/Conexion.php';

    $UserSession = VerificarSesionCookie($Pdo);
    if (!$UserSession || !SgcePuedePanelAdmin($UserSession)) {
        SgceApiJson(['ok' => false, 'message' => 'Sesión no válida.'], 401);
    }

    $GLOBALS['UserSession'] = $UserSession;
    global $EsAdmin, $EsAdministrativo, $PuedeVerBitacora, $PuedeGestionarCatalogos, $TabActual;
    $EsAdmin = SgceTieneRol($UserSession, ['admin']);
    $EsAdministrativo = SgceTieneRol($UserSession, ['administrativo']);
    $PuedeVerBitacora = SgcePuedeBitacora($UserSession);
    $PuedeGestionarCatalogos = SgcePuedeGestionarCatalogos($UserSession);

    $_GET['Tab'] = $TabEsperada;
    $TabActual = SgceTabAdminPermitida($TabEsperada, $UserSession);
    if ($TabActual !== $TabEsperada) {
        SgceApiJson(['ok' => false, 'message' => 'No tienes permiso para consultar este módulo.'], 403);
    }

    require dirname(__DIR__, 2) . '/modules/admin/AdminDatos.php';
    require dirname(__DIR__, 2) . '/includes/SGCE_AdminViewContext.php';

    $BaseParcial = dirname(__DIR__, 2) . '/views/admin/partials/' . $TabEsperada;
    $Variables = get_defined_vars();
    $Tbody = SgceCapturarPartialAdmin($BaseParcial . '_tbody.php', $Variables);
    $Pager = SgceCapturarPartialAdmin($BaseParcial . '_pager.php', $Variables);
    $Modals = SgceCapturarPartialAdmin($BaseParcial . '_modals.php', $Variables);
    $Count = SgceCapturarPartialAdmin($BaseParcial . '_count.php', $Variables);

    if ($Tbody === '' && $Pager === '') {
        SgceApiJson(['ok' => false, 'message' => 'No se pudo generar la actualización parcial.'], 500);
    }

    $AdminUrl = 'Admin.php?' . http_build_query($_GET);
    SgceApiJson([
        'ok' => true,
        'tab' => $TabEsperada,
        'url' => $AdminUrl,
        'tbody' => $Tbody,
        'pager' => $Pager,
        'modals' => $Modals,
        'count' => $Count,
    ]);
}
