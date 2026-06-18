<?php if (!defined('SGCE_APP')) { exit; } ?>
<?php if ($YaSeRegistro && !isset($_GET['Success']) && !isset($_GET['Error'])): ?><?= SgceComponenteAlerta('Ya existe asistencia registrada para esta fecha. Puedes actualizarla si necesitas corregirla.', 'warning', 'fa-circle-exclamation') ?><?php endif; ?>
