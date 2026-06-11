<?php if (!defined('SGCE_APP')) { exit; } ?>
<div class="row g-3 mb-4">
<?php $Stats=[['A','Asistencias','✅','ContadorAsistencia','StatsAsistencia'],['R','Retardos','⏰','ContadorRetardo','StatsRetardo'],['F','Faltas','❌','ContadorFalta','StatsFalta'],['J','Justificantes','📄','ContadorJustificante','StatsJustificante']]; foreach($Stats as $S): ?>
<div class="col-6 col-xl-3"><div class="card StatsCard"><div class="card-body d-flex align-items-center gap-3"><div class="StatsIcon SgceAsistenciaStatIcon <?= HGlobal($S[4]) ?>"><span class="SgceColorIcon" aria-hidden="true"><?= HGlobal($S[2]) ?></span></div><div><div class="text-muted small"><?= HGlobal($S[1]) ?></div><h3 id="<?= HGlobal($S[3]) ?>" class="fw-bold mb-0"><?= (int)$ResumenAsistencia[$S[0]] ?></h3></div></div></div></div>
<?php endforeach; ?>
</div>
