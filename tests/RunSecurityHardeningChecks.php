<?php
$Root = dirname(__DIR__);
$Errores = [];
$Seguridad = file_get_contents($Root . '/includes/SGCE_Seguridad.php');
$Logout = file_get_contents($Root . '/public/Logout.php');
$Publico = file_get_contents($Root . '/includes/SGCE_PublicConsultas.php');
$BaseCss = file_get_contents($Root . '/assets/css/sgce-base.min.css');
$MobileCss = file_get_contents($Root . '/assets/css/components/mobile-buttons.css');
$Config = file_get_contents($Root . '/config/Conexion.php');

if (strpos($Seguridad, 'SessionToken IN') !== false || strpos($Logout, 'SessionToken IN') !== false) { $Errores[] = 'Aún existe búsqueda de SessionToken IN.'; }
if (strpos($Seguridad, 'WHERE SessionToken = ?') === false) { $Errores[] = 'VerificarSesionCookie no busca únicamente token hasheado.'; }
if (strpos($Seguridad, 'SgceRegenerarSesionRevalidadaPorCookie') === false || strpos($Seguridad, 'session_regenerate_id(true)') === false) { $Errores[] = 'No se detecta regeneración de sesión al revalidar cookie.'; }
if (strpos($Seguridad, 'SGCE_FORCE_HTTPS') === false || strpos($Seguridad, 'SGCE_TRUST_PROXY_HEADERS') === false) { $Errores[] = 'EsHttps no contempla configuración endurecida.'; }
if (strpos($Seguridad, 'SgceForzarHttpsRedirect') === false || strpos($Config, 'SgceForzarHttpsRedirect') === false) { $Errores[] = 'No se detecta redirección HTTPS real con force_https.'; }
if (strpos($Seguridad, 'SgceCookiePath') === false || strpos($Seguridad, "'path' => SgceCookiePath()") === false) { $Errores[] = 'Las cookies no usan path basado en URL base.'; }
if (strpos($Seguridad, 'return true; }\n    $Remoto') !== false) { $Errores[] = 'Proxy confiable aún acepta headers sin validar IP remota.'; }
if (strpos($Config, 'SGCE_BASE_URL') === false || strpos($Config, 'SGCE_TRUSTED_PROXIES') === false) { $Errores[] = 'Conexion.php no define configuración de URL/HTTPS/proxy.'; }
if (strpos($Publico, 'UrlBaseSistema') === false || strpos($Publico, 'SGCE_BASE_URL') === false) { $Errores[] = 'La consulta pública no usa URL base configurada.'; }
if (strpos($Publico, '$LimiteConsulta = $LimiteDetalle + 1') === false || strpos($Publico, 'RegistrosTruncados') === false) { $Errores[] = 'La asistencia pública no detecta truncamiento con LIMIT + 1.'; }
if (strpos($BaseCss, '.ConsultaPublicaBody .ConsultaHeroActions{order:-1!important') !== false) { $Errores[] = 'sgce-base.min.css conserva order:-1 en acciones del hero público.'; }
if (strpos($MobileCss, 'html body.ConsultaPublicaBody section.ConsultaHero.ConsultaHeroCompact') !== false) { $Errores[] = 'mobile-buttons.css conserva selectores públicos demasiado específicos.'; }

if ($Errores) { echo "RunSecurityHardeningChecks: ERROR
" . implode("
", $Errores) . "
"; exit(1); }
echo "RunSecurityHardeningChecks: OK
";
