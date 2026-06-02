# Changelog SGCE

## Entrega final desde cero

- Diseno visual aprobado conservado.
- Cache de CSS/JS normalizado como `?v=sgce`.
- Favicon centralizado en `assets/media/img/`, sin duplicados en raiz.
- Panel administrativo optimizado para renderizar solamente la pestana activa.
- Paginacion desde base de datos en secciones de mayor crecimiento.
- Consultas optimizadas para alumnos, asignaciones, asistencia, calificaciones y bitacora.
- Indices SQL reforzados para alto volumen escolar.
- Login reforzado con consulta preparada, rate limit y rehash automatico.
- Instalador protegido mediante archivo `storage/install.lock` despues de finalizar.
- Consulta publica con coincidencia exacta y control de intentos.
- Reportes protegidos para evitar cargas excesivas.
- Portal docente con iconos visuales suavizados.
- Consulta publica para padres con avisos suavizados y transiciones uniformes.
- README, manual tecnico, manual de usuario, estructura del proyecto, revision tecnica y auditoria actualizados.
- Manifest SHA256 regenerado.
- Pruebas estaticas incluidas en `tests/RunStaticChecks.php`.
