document.addEventListener('DOMContentLoaded', function(){
    const PasswordInput = document.getElementById('PasswordLogin');
    const ToggleButton = document.getElementById('TogglePasswordLogin');

    if (PasswordInput && ToggleButton) {
        ToggleButton.addEventListener('click', function(){
            const Icon = ToggleButton.querySelector('i');
            const IsHidden = PasswordInput.type === 'password';

            PasswordInput.type = IsHidden ? 'text' : 'password';

            if (Icon) {
                Icon.classList.toggle('fa-eye', !IsHidden);
                Icon.classList.toggle('fa-eye-slash', IsHidden);
            }
        });
    }
});