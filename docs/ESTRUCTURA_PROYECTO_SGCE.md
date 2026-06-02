# Estructura del proyecto SGCE

```text
SGCE/
├── assets/
│   ├── css/
│   ├── js/
│   └── media/img/
├── config/
├── cron/
├── docs/
├── includes/
├── install/
├── modules/
│   └── admin/
├── public/
├── reports/
├── services/
├── storage/
│   ├── backups/
│   ├── locks/
│   ├── logs/
│   ├── planeaciones/
│   └── tmp_uploads/
├── .gitignore
├── .htaccess
├── .user.ini
├── Instalar.php
├── index.php
└── README.md
```

## Criterio de organizacion

- La raiz conserva entradas PHP publicas por compatibilidad de rutas.
- `modules/`, `reports`, `public`, `includes`, `services` y `config` estan protegidos contra acceso directo.
- Los archivos generados por usuarios, respaldos y logs se guardan en `storage`.
- Los manuales, revision y auditoria se guardan en `docs`.
- El favicon y la imagen PNG del sistema estan centralizados en `assets/media/img`.
- Los servicios reutilizables estan en `services`; no se conservan servicios vacios o sin uso.

## Entradas principales

| Entrada | Destino interno |
|---|---|
| `index.php` | `public/index.php` |
| `Admin.php` | `modules/Admin.php` |
| `Maestro.php` | `modules/Maestro.php` |
| `Asistencia.php` | `modules/Asistencia.php` |
| `Calificar.php` | `modules/Calificar.php` |
| `ReportesAdmin.php` | `reports/ReportesAdmin.php` |
| `ConsultaPadre.php` | `public/ConsultaPadre.php` |
| `ConsultaCalificaciones.php` | `public/ConsultaCalificaciones.php` |
| `Instalar.php` | Instalador principal |
