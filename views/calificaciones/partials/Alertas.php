<?php if (!defined('SGCE_APP')) { exit; } ?>
<?php if(isset($_GET['Success'])): ?><?= SgceComponenteAlerta('Calificaciones guardadas correctamente.', 'success', 'fa-circle-check') ?><?php endif; ?><div id="JsAlert" class="alert alert-warning border-0 shadow-sm d-none mb-4"></div>
