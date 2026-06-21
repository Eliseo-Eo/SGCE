# Checklist de pruebas de aceptación — SGCE 1.0.196

Marcar cada punto como PASA/NO PASA antes de entregar a producción.

## 1. Instalación

- [ ] El servidor cumple requisitos PHP.
- [ ] `storage/` es escribible.
- [ ] `install/SGCE.sql` existe.
- [ ] `Instalar.php` abre correctamente.
- [ ] El diagnóstico del instalador no muestra bloqueos críticos.
- [ ] La conexión a base de datos se valida.
- [ ] La instalación termina sin error.
- [ ] El usuario administrador inicial puede iniciar sesión.
- [ ] `Instalar.php` queda eliminado o bloqueado después de instalar.

## 2. Login y sesiones

- [ ] Login correcto entra al panel.
- [ ] Login incorrecto no entra.
- [ ] Cerrar sesión invalida acceso posterior.
- [ ] Abrir módulo protegido sin sesión muestra acceso denegado o redirige.
- [ ] Después de inactividad, la sesión se comporta según configuración.

## 3. Permisos por rol

### Administrador

- [ ] Ve módulos administrativos.
- [ ] Puede administrar usuarios.
- [ ] Puede acceder a configuración.
- [ ] Puede crear respaldos.
- [ ] Puede revisar bitácora.

### Administrativo

- [ ] No ve funciones críticas que no le corresponden.
- [ ] Puede operar alumnos/grupos/materias si está permitido.
- [ ] No puede entrar por URL directa a módulos restringidos.

### Maestro

- [ ] Solo ve funciones docentes.
- [ ] No puede entrar por URL directa a administración restringida.
- [ ] Solo opera asignaciones que le corresponden.

## 4. CSRF y formularios

- [ ] Guardar formulario normal funciona.
- [ ] Reenviar formulario con token faltante falla.
- [ ] Reenviar formulario con token viejo/falso falla.
- [ ] Instalador no marca “Solicitud inválida” en uso normal.

## 5. Catálogos base

- [ ] Crear maestro.
- [ ] Editar maestro.
- [ ] Buscar maestro.
- [ ] Crear grupo.
- [ ] Editar grupo.
- [ ] Crear alumno.
- [ ] Editar alumno.
- [ ] Buscar alumno.
- [ ] Crear materia.
- [ ] Crear asignación.

## 6. Importaciones

- [ ] Importar maestros con formato requerido.
- [ ] Importar alumnos con formato requerido.
- [ ] Importar grupos/materias si aplica.
- [ ] Archivo inválido se rechaza.
- [ ] Errores de importación se pueden descargar.
- [ ] Registros correctos sí quedan guardados.

## 7. Asistencia

- [ ] Maestro puede abrir asistencia de su grupo.
- [ ] Captura asistencia por día.
- [ ] Guarda presentes/faltas/retardos según catálogo.
- [ ] Reporte de asistencia refleja captura.
- [ ] Consulta pública muestra información correcta si aplica.

## 8. Calificaciones

- [ ] Maestro puede abrir periodo permitido.
- [ ] Sistema respeta escala configurada.
- [ ] Calificación fuera de rango se rechaza.
- [ ] Guardar calificaciones funciona.
- [ ] Reporte de calificaciones coincide con captura.
- [ ] Kardex individual exporta PDF.
- [ ] Kardex individual exporta Excel.

## 9. Planeaciones y archivos

- [ ] Subir PDF válido funciona.
- [ ] Subir DOCX/XLSX/PPTX válido funciona si está permitido.
- [ ] Archivo con extensión falsa se rechaza.
- [ ] Archivo demasiado grande se rechaza.
- [ ] Descargar planeación propia funciona.
- [ ] Usuario sin permiso no descarga archivo ajeno por URL directa.

## 10. Reportes

- [ ] Exportar listado PDF.
- [ ] Exportar listado Excel.
- [ ] Exportar asistencia PDF/Excel.
- [ ] Exportar calificaciones PDF/Excel.
- [ ] Exportar historial/Kardex PDF/Excel.
- [ ] Los archivos abren correctamente en visor/ofimática.

## 11. Respaldos y restauración

- [ ] Crear respaldo completo.
- [ ] Descargar respaldo.
- [ ] Verificar que el respaldo no esté vacío.
- [ ] Restaurar en entorno de prueba.
- [ ] Confirmar que usuarios y datos aparecen después de restaurar.
- [ ] Confirmar que la bitácora registra operación.

## 12. Migración de ciclo

- [ ] Diagnóstico previo muestra datos esperados.
- [ ] Crear grupos destino funciona.
- [ ] Promover alumnos funciona en entorno de prueba.
- [ ] Tercer grado egresa correctamente si aplica.
- [ ] Ciclo anterior queda intacto.
- [ ] Se crea respaldo antes de migrar.

## 13. Responsive y navegación

- [ ] Login se ve bien en móvil.
- [ ] Dashboard administrativo se ve bien en móvil.
- [ ] Tablas críticas son usables en móvil.
- [ ] Botones de descarga no se ven comprimidos.
- [ ] Modales se pueden cerrar en móvil.

## 14. Producción

- [ ] HTTPS activo.
- [ ] `storage/` bloqueado desde navegador.
- [ ] `config/` bloqueado desde navegador.
- [ ] Logs sin errores críticos después de pruebas.
- [ ] Respaldo inicial descargado.
- [ ] Cliente validó flujo principal.
