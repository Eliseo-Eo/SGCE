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
- `modules/`, `reports/`, `public/`, `includes/`, `services/` y `config/` estan protegidos contra acceso directo.
- Los archivos generados por usuarios o respaldos se guardan en `storage/`.
- Los manuales y auditoria se guardan en `docs/`.
- Favicon e imagen PNG institucional del sistema estan en `assets/media/img/`.
