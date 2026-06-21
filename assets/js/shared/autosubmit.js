document.addEventListener('change', function (Evento) {
    const Control = Evento.target.closest('[data-sgce-auto-submit="1"]');
    if (!Control || !Control.form) { return; }
    Control.form.submit();
});
