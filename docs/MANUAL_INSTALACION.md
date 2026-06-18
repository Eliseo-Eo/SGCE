# Manual de instalación - SGCE 1.0.185

## Archivos principales

- Manual Word: `docs/manuales/Manual_Instalacion_SGCE_1.0.185.docx`
- Manual PDF: `docs/manuales/Manual_Instalacion_SGCE_1.0.185.pdf`

## Instalación limpia

1. Copia `Produccion/` al servidor.
2. Configura permisos de escritura en `storage/`.
3. Crea o selecciona una base MySQL vacía.
4. Abre `Instalar.php`.
5. Captura los datos solicitados y ejecuta la instalación.
6. Elimina credenciales temporales del navegador y conserva `storage/install.lock`.

## Prueba posterior

Inicia sesión como administrador y valida maestros, grupos, alumnos, asignaciones, asistencia, conducta, calificaciones, expediente y reportes.
