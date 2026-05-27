document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.UpperInput').forEach(Input => {
        Input.addEventListener('input', () => {
            const Start = Input.selectionStart;
            const End = Input.selectionEnd;
            Input.value = Input.value.toUpperCase();
            try { Input.setSelectionRange(Start, End); } catch (e) {}
        });
    });

    const ModalBaja = document.getElementById('ModalBajaUsuario');
    const TextoBaja = document.getElementById('TextoBajaUsuario');
    const BotonConfirmar = document.querySelector('.BtnConfirmarBajaUsuario');
    let FormularioBajaId = '';

    document.querySelectorAll('.BtnAbrirBajaUsuario').forEach(Boton => {
        Boton.addEventListener('click', () => {
            FormularioBajaId = Boton.dataset.formId || '';
            const Usuario = Boton.dataset.usuario || 'el usuario seleccionado';
            if (TextoBaja) {
                TextoBaja.textContent = `Se desactivará el acceso de: ${Usuario}.`;
            }
        });
    });

    if (ModalBaja) {
        ModalBaja.addEventListener('hidden.bs.modal', () => {
            FormularioBajaId = '';
            if (TextoBaja) {
                TextoBaja.textContent = 'Esta acción desactivará el usuario seleccionado.';
            }
        });
    }

    if (BotonConfirmar) {
        BotonConfirmar.addEventListener('click', () => {
            const Formulario = document.getElementById(FormularioBajaId);
            if (!Formulario) { return; }
            BotonConfirmar.disabled = true;
            BotonConfirmar.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando';
            Formulario.submit();
        });
    }
});
