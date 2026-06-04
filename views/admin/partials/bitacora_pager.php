<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
echo SgceRenderPager('PagBitacora', $PagBitacora, $TotalBitacoraTabla, $PageSizeBitacora, ['Tab' => 'bitacora']);
