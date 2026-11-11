(() => {
    const toggle = document.querySelector('.password-toggle');
    const password = document.querySelector('#senha');
    if (!toggle || !password) return;

    toggle.addEventListener('click', () => {
        const mostrar = password.type === 'password';
        password.type = mostrar ? 'text' : 'password';
        toggle.setAttribute('aria-pressed', String(mostrar));
        toggle.setAttribute('aria-label', mostrar ? 'Ocultar senha' : 'Mostrar senha');
    });
})();
