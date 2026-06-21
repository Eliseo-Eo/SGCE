# Evidencia SGCE 1.0.200 - Cancelar canónico en modales

Parche dirigido sobre SGCE 1.0.199. Alcance: CSS visual, versión identificable, README y títulos de manuales a 1.0.200. No cambia PHP funcional, SQL, `.htaccess`, JavaScript, Kardex, caché, CSRF, calificaciones, asistencia ni lógica académica.

## Archivos CSS modificados

- `assets/css/sgce-bundle.min.css`
- `assets/css/sgce-admin-bundle.min.css`
- `assets/css/sgce-docente-bundle.min.css`

## Regla canónica conservada

La regla efectiva única para botones Cancelar usa siempre fondo blanco, texto guinda, borde guinda y sombra suave. El botón de aceptar/confirmar conserva su color por contexto.

```css
html body .modal .BtnCancelEdit,
html body .modal .BtnCancelDelete,
html body .BtnCancelEdit,
html body .BtnCancelDelete,
html body .BtnModalCancel,
html body .PlaneacionReviewCancel,
html body .ConductaRevisionCancel,
html body .SgceConductaCancelBtn,
html body .SgceConfirmModal .SgceConfirmCancel,
html body .SgceConfirmCancel {
  background: #FFFFFF;
  color: var(--SgceGuindaOscuro);
  border: 1.5px solid rgba(var(--SgceGuindaRGB), .46);
  box-shadow: 0 8px 18px rgba(15,23,42,.07), inset 0 1px 0 rgba(255,255,255,.90);
}

/* Hover/focus/active: sigue siendo Cancelar, no se vuelve verde ni rojo sólido. */
html body .SgceConfirmModal .SgceConfirmCancel:hover,
html body .BtnCancelEdit:hover,
html body .BtnCancelDelete:hover {
  background: #FFFFFF;
  color: var(--SgceGuinda);
  border-color: rgba(var(--SgceGuindaRGB), .62);
  box-shadow: 0 10px 22px rgba(var(--SgceGuindaRGB), .14), inset 0 1px 0 rgba(255,255,255,.92);
}
```

## Reglas antiguas eliminadas o neutralizadas

- `assets/css/sgce-admin-bundle.min.css` - selector eliminado: `html body .SgceAsignacionEditModal .BtnCancelEdit`
- `assets/css/sgce-admin-bundle.min.css` - selector eliminado: `html body .SgceAsignacionEditModal .BtnCancelEdit:hover,html body .SgceAsignacionEditModal .BtnCancelEdit:focus,html body .SgceAsignacionEditModal .BtnCancelEdit:active`
- `assets/css/sgce-admin-bundle.min.css` - selector eliminado: `html body .PlaneacionReviewCancel`
- `assets/css/sgce-admin-bundle.min.css` - selector eliminado: `.ConductaRevisionCancel`
- `assets/css/sgce-admin-bundle.min.css` - selector eliminado: `.ConductaRevisionCancel:hover`
- `assets/css/sgce-bundle.min.css` - selector eliminado: `.BtnCancelEdit`
- `assets/css/sgce-bundle.min.css` - selector eliminado: `.BtnCancelEdit:hover`
- `assets/css/sgce-bundle.min.css` - selector eliminado: `.BtnCancelDelete`
- `assets/css/sgce-bundle.min.css` - selector eliminado: `.BtnCancelDelete:hover`
- `assets/css/sgce-bundle.min.css` - selector eliminado: `.BtnModalCancel`
- `assets/css/sgce-bundle.min.css` - selector eliminado: `html body .modal .BtnCancelEdit`
- `assets/css/sgce-bundle.min.css` - selector eliminado: `html body .modal .BtnCancelEdit:hover`
- `assets/css/sgce-bundle.min.css` - selector eliminado: `html body .SgceBtnVolverInicio,html body .BtnCancelDelete,html body .BtnCancelEdit,html body .modal .BtnCancelDelete,html body .modal .BtnCancelEdit`
- `assets/css/sgce-bundle.min.css` - selector eliminado: `html body .SgceBtnVolverInicio:hover,html body .BtnCancelDelete:hover,html body .BtnCancelEdit:hover,html body .modal .BtnCancelDelete:hover,html body .modal .BtnCancelEdit:hover`
- `assets/css/sgce-bundle.min.css` - selector eliminado: `.PlaneacionReviewCancel`
- `assets/css/sgce-bundle.min.css` - selector eliminado: `.PlaneacionReviewCancel:hover`
- `assets/css/sgce-bundle.min.css` - selector eliminado: `.SgceConfirmCancel`
- `assets/css/sgce-bundle.min.css` - selector eliminado: `html body .modal .BtnCancelEdit,html body .modal .BtnCancelDelete,html body .SgceConfirmModal .SgceConfirmCancel,html body .SgceConfirmCancel`
- `assets/css/sgce-bundle.min.css` - selector eliminado: `html body .modal .BtnCancelEdit i,html body .modal .BtnCancelEdit span,html body .modal .BtnCancelDelete i,html body .modal .BtnCancelDelete span,html body .SgceConfirmModal .SgceConfirmCancel i,html body .SgceConfirmModal .SgceConfirmCancel span,html body .SgceConfirmCancel i,html body .SgceConfirmCancel span`
- `assets/css/sgce-bundle.min.css` - selector eliminado: `html body .modal .BtnCancelEdit:hover,html body .modal .BtnCancelEdit:focus,html body .modal .BtnCancelEdit:focus-visible,html body .modal .BtnCancelEdit:active,html body .modal .BtnCancelDelete:hover,html body .modal .BtnCancelDelete:focus,html body .modal .BtnCancelDelete:focus-visible,html body .modal .BtnCancelDelete:active,html body .SgceConfirmModal .SgceConfirmCancel:hover,html body .SgceConfirmModal .SgceConfirmCancel:focus,html body .SgceConfirmModal .SgceConfirmCancel:focus-visible,html body .SgceConfirmModal .SgceConfirmCancel:active,html body .SgceConfirmCancel:hover,html body .SgceConfirmCancel:focus,html body .SgceConfirmCancel:focus-visible,html body .SgceConfirmCancel:active`
- `assets/css/sgce-bundle.min.css` - selector eliminado: `.SgceConfirmModal:not(.SgceConfirmModalDanger) .SgceConfirmCancel:hover,.SgceConfirmModal:not(.SgceConfirmModalDanger) .SgceConfirmCancel:focus,.SgceConfirmModal:not(.SgceConfirmModalDanger) .SgceConfirmCancel:focus-visible,.SgceConfirmModal:not(.SgceConfirmModalDanger) .SgceConfirmCancel:active`
- `assets/css/sgce-bundle.min.css` - selector eliminado: `html body .SgceConfirmModal.SgceConfirmModalLogout .SgceConfirmCancel,html body .modal.SgceConfirmModal.SgceConfirmModalLogout .SgceConfirmCancel`
- `assets/css/sgce-bundle.min.css` - selector eliminado: `html body .SgceConfirmModal.SgceConfirmModalLogout .SgceConfirmCancel:hover,html body .SgceConfirmModal.SgceConfirmModalLogout .SgceConfirmCancel:focus,html body .SgceConfirmModal.SgceConfirmModalLogout .SgceConfirmCancel:focus-visible,html body .modal.SgceConfirmModal.SgceConfirmModalLogout .SgceConfirmCancel:hover,html body .modal.SgceConfirmModal.SgceConfirmModalLogout .SgceConfirmCancel:focus,html body .modal.SgceConfirmModal.SgceConfirmModalLogout .SgceConfirmCancel:focus-visible`
- `assets/css/sgce-docente-bundle.min.css` - selector eliminado: `.SgceConductaCancelBtn`
- `assets/css/sgce-docente-bundle.min.css` - selector eliminado: `.SgceConductaCancelBtn:hover`
- `assets/css/sgce-docente-bundle.min.css` - selector eliminado: `html body .SgceConductaModalContent.is-create .SgceConductaCancelBtn:hover,html body .SgceConductaModalContent.is-edit .SgceConductaCancelBtn:hover,html body .SgceConductaModalContent.is-readonly .SgceConductaCancelBtn:hover`
- `assets/css/sgce-docente-bundle.min.css` - selector eliminado: `html body .SgceConductaModalContent .SgceConductaCancelBtn`
- `assets/css/sgce-docente-bundle.min.css` - selector eliminado: `html body .SgceConductaModalContent .SgceConductaCancelBtn:hover,html body .SgceConductaModalContent .SgceConductaCancelBtn:focus-visible`


Nota: Las reglas compartidas que mezclaban `SgceBtnVolverInicio` con `BtnCancel*` se separaron; `SgceBtnVolverInicio` conserva su estilo anterior en una regla propia y ya no comparte declaración con botones Cancelar.

## Verificación de reglas problemáticas

- Reglas `.SgceConfirmModal:not(.SgceConfirmModalDanger) .SgceConfirmCancel...`: 0
- Reglas específicas `.SgceAsignacionEditModal .BtnCancelEdit` con color/fondo/borde: 0
- Resultado esperado: `Cancelar` ya no cambia a verde en importación, ni a guinda sólido en Editar Asignación.

## Botones Cancel detectados después del cambio

- `.BtnCancelEdit` cubierto por la regla canónica.
- `.BtnCancelDelete` cubierto por la regla canónica.
- `.BtnModalCancel` cubierto por la regla canónica.
- `.PlaneacionReviewCancel` cubierto por la regla canónica.
- `.ConductaRevisionCancel` cubierto por la regla canónica.
- `.SgceConductaCancelBtn` cubierto por la regla canónica.
- `.SgceConfirmCancel` cubierto por la regla canónica.

## Versión

- `VERSION.txt = 1.0.200`
- `Sgce\Foundation\Version::CURRENT = 1.0.200`

## Manuales y README

- README actualizado a 1.0.200.
- Manuales extensos conservados; solo se actualizó el marcador/título de versión a 1.0.200 y se regeneraron DOCX/PDF desde el mismo contenido.
