/**
 * gas.js — JavaScript del Módulo GAS v1.0
 * Archivo: assets/gas/js/gas.js
 *
 * Funciones para:
 *  - Panel administrativo: copiar link, cambios de estado, confirmaciones
 *  - Formularios públicos: calificaciones, contador de chars, spinner en submit
 *  - Utilidades: toast, semáforo de promedios
 *
 * Sin dependencias externas. Requiere Bootstrap 5 (ya incluido en DPS).
 */

'use strict';

/* ============================================================
   MÓDULO GAS — namespace único para no contaminar el global
   ============================================================ */
const GAS = (() => {

    /* ──────────────────────────────────────────────────────────
       UTILIDADES GENERALES
       ────────────────────────────────────────────────────────── */

    /**
     * Copia texto al portapapeles. Usa Clipboard API si está disponible,
     * fallback a execCommand para navegadores viejos.
     *
     * @param {string} texto  Texto a copiar
     * @param {Function} [onOk]  Callback ejecutado al copiar con éxito
     * @param {Function} [onError]  Callback ejecutado si falla
     */
    function copiar(texto, onOk, onError) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(texto)
                .then(() => onOk && onOk())
                .catch(() => onError && onError());
        } else {
            // Fallback para HTTP o browsers sin Clipboard API
            const el = document.createElement('textarea');
            el.value = texto;
            el.style.position = 'fixed';
            el.style.opacity  = '0';
            document.body.appendChild(el);
            el.select();
            try {
                document.execCommand('copy');
                onOk && onOk();
            } catch (e) {
                onError && onError();
            }
            document.body.removeChild(el);
        }
    }

    /**
     * Muestra un toast Bootstrap en la esquina superior derecha.
     *
     * @param {string} mensaje  Texto del toast
     * @param {'success'|'danger'|'warning'|'info'} tipo
     * @param {number} duracion  ms antes de ocultarse (default 3000)
     */
    function toast(mensaje, tipo = 'success', duracion = 3000) {
        let contenedor = document.getElementById('gas-toast-container');
        if (!contenedor) {
            contenedor = document.createElement('div');
            contenedor.id = 'gas-toast-container';
            contenedor.style.cssText = 'position:fixed;top:16px;right:16px;z-index:9999;';
            document.body.appendChild(contenedor);
        }

        const id     = 'gas-toast-' + Date.now();
        const iconos = { success:'✅', danger:'❌', warning:'⚠️', info:'ℹ️' };

        contenedor.insertAdjacentHTML('beforeend', `
            <div id="${id}" class="toast align-items-center text-bg-${tipo} border-0 mb-2"
                 role="alert" aria-live="polite">
              <div class="d-flex">
                <div class="toast-body">
                  ${iconos[tipo] || ''} ${mensaje}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto"
                        data-bs-dismiss="toast"></button>
              </div>
            </div>
        `);

        const el = document.getElementById(id);
        if (window.bootstrap && bootstrap.Toast) {
            const t = new bootstrap.Toast(el, { delay: duracion });
            t.show();
            el.addEventListener('hidden.bs.toast', () => el.remove());
        } else {
            // Sin Bootstrap JS: mostrar y eliminar manualmente
            el.style.display = 'block';
            setTimeout(() => el.remove(), duracion);
        }
    }

    /**
     * Retorna la clase CSS de semáforo para un promedio (1-5).
     */
    function claseSemaforo(valor) {
        if (valor >= 4)  return 'gas-prom-alto';
        if (valor >= 3)  return 'gas-prom-medio';
        return 'gas-prom-bajo';
    }

    /* ──────────────────────────────────────────────────────────
       PANEL ADMINISTRATIVO
       ────────────────────────────────────────────────────────── */

    /**
     * Inicializa todos los botones de copiar link (.gas-btn-copiar-link).
     * Atributo requerido: data-link="URL_A_COPIAR"
     */
    function initBotonesCopiarLink() {
        document.querySelectorAll('.gas-btn-copiar-link, .btn-copy-link').forEach(btn => {
            btn.addEventListener('click', function () {
                const token = this.dataset.token;
                const link  = this.dataset.link || (
                    window.GAS_PUBLIC_URL
                        ? `${window.GAS_PUBLIC_URL}?t=${token}`
                        : this.closest('[data-link]')?.dataset.link || ''
                );

                if (!link) {
                    toast('No se encontró el link.', 'danger');
                    return;
                }

                const original = this.innerHTML;
                copiar(
                    link,
                    () => {
                        this.innerHTML = '<i class="fas fa-check"></i>';
                        this.classList.add('btn-success');
                        this.classList.remove('btn-outline-info', 'btn-outline-secondary');
                        toast('¡Link copiado al portapapeles!', 'success', 2500);
                        setTimeout(() => {
                            this.innerHTML = original;
                            this.classList.remove('btn-success');
                            this.classList.add('btn-outline-info');
                        }, 2000);
                    },
                    () => toast('No se pudo copiar. Cópialo manualmente.', 'warning')
                );
            });
        });
    }

    /**
     * Inicializa el formulario oculto de cambio de estado.
     * Busca #form_estado, #input_sesion_id, #input_nuevo_estado.
     * Los botones deben tener data-sesion-id y data-nuevo-estado.
     */
    function initCambioEstado() {
        document.querySelectorAll('[data-gas-cambiar-estado]').forEach(btn => {
            btn.addEventListener('click', function () {
                const sesionId   = this.dataset.sesionId;
                const nuevoEst   = this.dataset.nuevoEstado;
                const accion     = this.dataset.accion || 'cambiar';
                const confirmMsg = `¿Confirmas que deseas ${accion.toLowerCase()} esta sesión?`;

                if (!confirm(confirmMsg)) return;

                const form    = document.getElementById('form_estado');
                const idInput = document.getElementById('input_sesion_id');
                const estInput= document.getElementById('input_nuevo_estado');

                if (!form || !idInput || !estInput) {
                    // Fallback: construir y enviar formulario dinámico
                    const f = document.createElement('form');
                    f.method = 'POST';
                    f.action = 'sesion_cambiar_estado.php';
                    f.innerHTML = `
                        <input name="sesion_id"    value="${sesionId}">
                        <input name="nuevo_estado" value="${nuevoEst}">
                        <input name="redirect"     value="${window.location.href}">
                    `;
                    document.body.appendChild(f);
                    f.submit();
                    return;
                }

                idInput.value  = sesionId;
                estInput.value = nuevoEst;
                form.submit();
            });
        });
    }

    /**
     * Aplica colores semáforo a celdas con clase .gas-promedio
     * y valor numérico como textContent.
     */
    function initSemaforosPromedios() {
        document.querySelectorAll('.gas-promedio').forEach(el => {
            const val = parseFloat(el.textContent);
            if (!isNaN(val)) {
                el.classList.add(claseSemaforo(val));
            }
        });
    }

    /**
     * Confirma antes de anular un registro.
     * Botones con data-gas-confirmar="Texto de confirmación".
     */
    function initConfirmaciones() {
        document.querySelectorAll('[data-gas-confirmar]').forEach(btn => {
            btn.addEventListener('click', function (e) {
                const msg = this.dataset.gasConfirmar || '¿Confirmas esta acción?';
                if (!confirm(msg)) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });
        });
    }

    /* ──────────────────────────────────────────────────────────
       FORMULARIOS PÚBLICOS
       ────────────────────────────────────────────────────────── */

    /**
     * Inicializa las calificaciones visuales de la encuesta.
     * Actualiza el label con el texto descriptivo al seleccionar.
     */
    function initCalificaciones() {
        const descriptores = {
            1: '😞 Deficiente',
            2: '😐 Regular',
            3: '🙂 Aceptable',
            4: '😊 Bueno',
            5: '🤩 Excelente'
        };

        document.querySelectorAll('.gas-rating-group, .rating-group').forEach(grupo => {
            const radios = grupo.querySelectorAll('input[type="radio"]');
            radios.forEach(radio => {
                radio.addEventListener('change', function () {
                    const key  = this.name.replace('cal_', '');
                    const lbl  = document.getElementById(`lbl_${key}`);
                    if (lbl) lbl.textContent = descriptores[this.value] || '';

                    // Animación: resaltar brevemente la tarjeta padre
                    const card = this.closest('.gas-pregunta-card, .pregunta-card');
                    if (card) {
                        card.style.transition = 'border-color .2s';
                        card.style.borderColor = '#2e75b6';
                        setTimeout(() => { card.style.borderColor = ''; }, 600);
                    }
                });
            });
        });
    }

    /**
     * Contador de caracteres para textareas con data-max-chars o maxlength.
     * Busca un elemento hermano con clase .gas-char-counter.
     */
    function initContadoresChars() {
        document.querySelectorAll('textarea[maxlength], textarea[data-max-chars]').forEach(ta => {
            const max     = parseInt(ta.getAttribute('maxlength') || ta.dataset.maxChars);
            const counter = ta.parentElement?.querySelector('.gas-char-counter, #obs_counter');
            if (!counter || !max) return;

            function actualizar() {
                const restante = ta.value.length;
                counter.textContent = `${restante}/${max} caracteres`;
                counter.classList.toggle('near-limit', restante >= max * 0.85);
            }

            ta.addEventListener('input', actualizar);
            actualizar();
        });
    }

    /**
     * Spinner de carga al enviar formularios públicos.
     * Agrega clase .loading al botón submit para mostrar spinner CSS.
     * También deshabilita el botón para evitar doble envío.
     */
    function initSpinnerSubmit() {
        document.querySelectorAll(
            '.gas-pub-body form, form[data-gas-spinner]'
        ).forEach(form => {
            form.addEventListener('submit', function (e) {
                // Validación HTML5 nativa: si el form no es válido, no mostramos spinner
                if (!this.checkValidity()) return;

                const submitBtn = this.querySelector(
                    'button[type="submit"], .gas-btn-submit, .btn-primary'
                );
                if (!submitBtn) return;

                submitBtn.classList.add('loading', 'gas-btn-submit');
                submitBtn.disabled = true;

                const textoOriginal = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';

                // Timeout de seguridad: re-habilitar si algo falla (10s)
                setTimeout(() => {
                    submitBtn.classList.remove('loading');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = textoOriginal;
                }, 10000);
            });
        });
    }

    /**
     * Validación de calificaciones antes del envío del formulario de encuesta.
     * Muestra alerta si alguna pregunta no fue seleccionada.
     */
    function initValidacionEncuesta() {
        const formEncuesta = document.querySelector('form[data-gas-encuesta]') ||
                             document.querySelector('.gas-pub-body form');
        if (!formEncuesta) return;

        // Solo en la página de encuesta (hay .gas-rating-group)
        const grupos = formEncuesta.querySelectorAll('.gas-rating-group, .rating-group');
        if (!grupos.length) return;

        formEncuesta.addEventListener('submit', function (e) {
            const sinResponder = [];
            grupos.forEach(grupo => {
                const seleccionado = grupo.querySelector('input[type="radio"]:checked');
                if (!seleccionado) {
                    const card = grupo.closest('.gas-pregunta-card, .pregunta-card');
                    const num  = card?.querySelector('.gas-pregunta-num, .pregunta-num')?.textContent || '';
                    sinResponder.push(num || 'Una pregunta');
                    // Resaltar la tarjeta sin responder
                    if (card) {
                        card.style.borderColor = '#dc3545';
                        card.style.background  = '#fff5f5';
                    }
                }
            });

            if (sinResponder.length > 0) {
                e.preventDefault();
                const msgEl = document.getElementById('gas-encuesta-error') ||
                              (() => {
                                  const d = document.createElement('div');
                                  d.id = 'gas-encuesta-error';
                                  d.className = 'gas-alert error';
                                  formEncuesta.insertBefore(d, formEncuesta.firstChild);
                                  return d;
                              })();
                msgEl.innerHTML = `<strong>Por favor responde todas las preguntas.</strong>
                    Quedan ${sinResponder.length} sin responder.`;
                msgEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });

        // Quitar el highlight rojo cuando el usuario selecciona una opción
        grupos.forEach(grupo => {
            grupo.querySelectorAll('input[type="radio"]').forEach(radio => {
                radio.addEventListener('change', function () {
                    const card = this.closest('.gas-pregunta-card, .pregunta-card');
                    if (card) {
                        card.style.borderColor = '';
                        card.style.background  = '';
                    }
                });
            });
        });
    }

    /* ──────────────────────────────────────────────────────────
       INICIALIZACIÓN AUTOMÁTICA
       ────────────────────────────────────────────────────────── */

    function init() {
        // Panel admin
        initBotonesCopiarLink();
        initCambioEstado();
        initSemaforosPromedios();
        initConfirmaciones();

        // Formularios públicos
        initCalificaciones();
        initContadoresChars();
        initSpinnerSubmit();
        initValidacionEncuesta();
    }

    // Ejecutar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    /* ── API pública del módulo ───────────────────────────── */
    return {
        copiar,
        toast,
        claseSemaforo,
        init,   // Para reinicializar si se carga contenido dinámico
    };

})();
