// validaciones.js
// Valida en el navegador el formulario de registro ANTES de enviarlo.
// Esto es solo para avisarle rápido al usuario; la validación real y
// segura se vuelve a hacer en procesar_registro.php (nunca hay que
// confiar solo en lo que se valida en el navegador).

document.addEventListener('DOMContentLoaded', function () {
    // Ícono de ojo abierto (contraseña visible)
    const iconoOjoAbierto = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
    // Ícono de ojo tachado (contraseña oculta)
    const iconoOjoTachado = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.5 18.5 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';

    // Botón de ojito para mostrar/ocultar contraseña (login y registro)
    document.querySelectorAll('.boton-ver-clave').forEach(function (boton) {
        boton.innerHTML = iconoOjoTachado; // por defecto la contraseña está oculta
        boton.addEventListener('click', function () {
            const campo = document.getElementById(boton.dataset.objetivo);
            if (!campo) return;
            const mostrando = campo.type === 'text';
            campo.type = mostrando ? 'password' : 'text';
            boton.innerHTML = mostrando ? iconoOjoTachado : iconoOjoAbierto;
        });
    });

    const formulario = document.getElementById('form-registro');
    if (!formulario) return;

    // Solo letras (con acentos/ñ) y espacios, nada de números ni símbolos
    const patronSoloLetras = /^[A-Za-zÀ-ÿ\s]+$/;
    // Exactamente 8 caracteres, con al menos una letra, un número y un símbolo
    const patronContrasena = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8}$/;

    // Muestra (o quita) un mensaje de error justo debajo de un campo,
    // sin tapar el texto de ayuda que ya existe
    function marcarError(idCampo, mensaje) {
        const campo = document.getElementById(idCampo);
        let error = document.getElementById('error-' + idCampo);
        if (!error) {
            error = document.createElement('small');
            error.id = 'error-' + idCampo;
            error.className = 'texto-error-campo';
            campo.insertAdjacentElement('afterend', error);
        }
        error.textContent = mensaje;
        campo.classList.toggle('campo-invalido', Boolean(mensaje));
    }

    function validarNombre(idCampo, etiqueta) {
        const valor = document.getElementById(idCampo).value.trim();
        if (valor === '') {
            marcarError(idCampo, 'Este campo es obligatorio.');
            return false;
        }
        if (!patronSoloLetras.test(valor)) {
            marcarError(idCampo, `${etiqueta} solo puede tener letras (nada de números ni símbolos).`);
            return false;
        }
        marcarError(idCampo, '');
        return true;
    }

    function validarContrasenas() {
        const contrasena = document.getElementById('contrasena').value;
        const confirmar = document.getElementById('confirmar_contrasena').value;
        let valido = true;

        if (!patronContrasena.test(contrasena)) {
            marcarError('contrasena', 'Debe tener exactamente 8 caracteres, con letras, números y símbolos.');
            valido = false;
        } else {
            marcarError('contrasena', '');
        }

        if (contrasena !== confirmar) {
            marcarError('confirmar_contrasena', 'Las dos contraseñas no coinciden.');
            valido = false;
        } else if (patronContrasena.test(contrasena)) {
            marcarError('confirmar_contrasena', '');
        }

        return valido;
    }

    // Valida cada campo apenas la persona sale de él, no solo al final
    document.getElementById('nombres').addEventListener('blur', () => validarNombre('nombres', 'Los nombres'));
    document.getElementById('apellidos').addEventListener('blur', () => validarNombre('apellidos', 'Los apellidos'));
    document.getElementById('contrasena').addEventListener('blur', validarContrasenas);
    document.getElementById('confirmar_contrasena').addEventListener('blur', validarContrasenas);

    formulario.addEventListener('submit', function (evento) {
        const nombresOk = validarNombre('nombres', 'Los nombres');
        const apellidosOk = validarNombre('apellidos', 'Los apellidos');
        const contrasenasOk = validarContrasenas();

        if (!nombresOk || !apellidosOk || !contrasenasOk) {
            evento.preventDefault();
        }
    });
});