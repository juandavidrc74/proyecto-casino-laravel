(() => {
    const hex = (buffer) => Array.from(new Uint8Array(buffer), (byte) => byte.toString(16).padStart(2, '0')).join('');
    const digest = async (value) => hex(await crypto.subtle.digest('SHA-256', new TextEncoder().encode(value)));

    document.querySelectorAll('form[data-zk-registro]').forEach((form) => {
        const clave = form.querySelector('[data-zk-clave]');
        const confirmacion = form.querySelector('[data-zk-confirmacion]');
        const hash = form.querySelector('[name="password_hash"]');
        const error = form.querySelector('[data-zk-error]');

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            error.hidden = true;
            const vacias = clave.value === '' && confirmacion.value === '';
            if (form.hasAttribute('data-zk-opcional') && vacias) {
                hash.value = '';
                HTMLFormElement.prototype.submit.call(form);
                return;
            }
            if (!window.crypto?.subtle) {
                error.textContent = 'La protección de contraseñas requiere entrar por https o localhost.';
                error.hidden = false;
                return;
            }
            if (clave.value.length < 8 || confirmacion.value.length < 8) {
                error.textContent = 'La contraseña debe tener al menos 8 caracteres.';
                error.hidden = false;
                return;
            }
            if (clave.value !== confirmacion.value) {
                error.textContent = 'Las contraseñas no coinciden.';
                error.hidden = false;
                return;
            }
            try {
                hash.value = await digest(clave.value);
                clave.value = '';
                confirmacion.value = '';
                HTMLFormElement.prototype.submit.call(form);
            } catch (exception) {
                error.textContent = 'No fue posible proteger la contraseña. Intenta de nuevo por https o localhost.';
                error.hidden = false;
            }
        });
    });

    const form = document.getElementById('login-form');
    if (!form) return;

    const password = document.getElementById('password');
    const nonce = form.querySelector('[name="nonce"]').value;
    const response = form.querySelector('[name="respuesta"]');
    const error = document.getElementById('zk-error');

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        error.hidden = true;

        if (!window.crypto?.subtle) {
            error.textContent = 'La autenticación segura requiere entrar por https o localhost.';
            error.hidden = false;
            return;
        }

        try {
            const h1 = await digest(password.value);
            response.value = await digest(h1 + nonce);
            password.value = '';
            HTMLFormElement.prototype.submit.call(form);
        } catch (exception) {
            error.textContent = 'No fue posible proteger el inicio de sesión. Intenta de nuevo por https o localhost.';
            error.hidden = false;
        }
    });
})();
