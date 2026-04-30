// ============================================
// VARIABLES GLOBALES
// ============================================
let html5QrCode = null;
let escaneando = false;
let ultimoDniEscaneado = '';
let tiempoUltimoEscaneo = 0;
const COOLDOWN_MS = 3000; // 3 segundos entre escaneos del mismo QR
const historial = [];

// ============================================
// INICIALIZAR ESCÁNER
// ============================================
function iniciarEscaner() {
    html5QrCode = new Html5Qrcode("qr-reader");

    const config = {
        fps: 10,
        qrbox: { width: 250, height: 250 },
        aspectRatio: 1.0,
        showTorchButtonIfSupported: true,
        showZoomSliderIfSupported: true,
        defaultZoomValueIfSupported: 2
    };

    html5QrCode.start(
        { facingMode: "environment" },
        config,
        onQRDetectado,
        onQRError
    ).then(() => {
        actualizarStatus('activo', '📷 Cámara activa — Apunta al código QR');
        escaneando = true;
    }).catch(() => {
        // Si falla cámara trasera, intentar cámara frontal
        html5QrCode.start(
            { facingMode: "user" },
            config,
            onQRDetectado,
            onQRError
        ).then(() => {
            actualizarStatus('activo', '📷 Cámara activa — Apunta al código QR');
            escaneando = true;
        }).catch((err) => {
            actualizarStatus('error', '❌ No se pudo acceder a la cámara');
            console.error('Error cámara:', err);
        });
    });
}

// ============================================
// CUANDO SE DETECTA UN QR
// ============================================
function onQRDetectado(decodedText) {
    const ahora = Date.now();

    // Cooldown: evitar escaneos repetidos
    if (decodedText === ultimoDniEscaneado && (ahora - tiempoUltimoEscaneo) < COOLDOWN_MS) {
        return;
    }

    ultimoDniEscaneado = decodedText;
    tiempoUltimoEscaneo = ahora;

    // Extraer solo números (el DNI)
    const dni = decodedText.replace(/\D/g, '').slice(0, 8);

    if (dni.length !== 8) {
        mostrarError('QR inválido', 'El código QR no contiene un DNI válido.');
        return;
    }

    pausarEscaner();
    verificarSocio(dni);
}

function onQRError(err) {
    // Silenciar errores de no-detección (son normales)
}

// ============================================
// VERIFICAR SOCIO VIA AJAX
// ============================================
async function verificarSocio(dni) {
    mostrarLoading(true);

    try {
        const response = await fetch('../actions/registrar_qr.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ dni })
        });

        const data = await response.json();
        mostrarLoading(false);

        if (data.success) {
            mostrarResultadoExito(data);
            agregarHistorial(data.socio);
            flashPantalla('success');
        } else {
            mostrarError(
                data.tipo === 'no_encontrado' ? 'Socio no encontrado' : 'Error',
                data.mensaje
            );
            flashPantalla('danger');
        }

    } catch (err) {
        mostrarLoading(false);
        mostrarError('Error de conexión', 'No se pudo conectar con el servidor.');
        console.error(err);
    }
}

// ============================================
// MOSTRAR RESULTADO EXITOSO
// ============================================
function mostrarResultadoExito(data) {
    const socio = data.socio;
    const resultado = document.getElementById('resultado');
    const yaRegistro = data.ya_registro;

    let clase, icon, titulo, sub;

    if (socio.estado === 'suspendido' || socio.estado === 'inactivo') {
        clase = 'danger';
        icon = '⛔';
        titulo = 'Acceso Denegado';
        sub = `Socio ${socio.estado.toUpperCase()} — Consultar administración`;
    } else if (yaRegistro) {
        clase = 'warning';
        icon = '⚠️';
        titulo = 'Ya registró hoy';
        sub = 'Asistencia duplicada — se registró igualmente';
    } else {
        clase = 'success';
        icon = '✅';
        titulo = 'Acceso Permitido';
        sub = 'Asistencia registrada correctamente';
    }

    document.getElementById('resultadoIcon').textContent    = icon;
    document.getElementById('resultadoTitulo').textContent  = titulo;
    document.getElementById('resultadoSub').textContent     = sub;

    resultado.className = 'resultado visible ' + clase;

    const estadoPills = {
        activo:     'pill-activo',
        inactivo:   'pill-inactivo',
        vitalicio:  'pill-vitalicio',
        transeunte: 'pill-transeunte',
        suspendido: 'pill-suspendido'
    };
    const pillClass = estadoPills[socio.estado] || 'pill-activo';

    document.getElementById('resultadoBody').innerHTML = `
        <div class="dato-item" style="grid-column: span 2;">
            <span class="dato-label">Nombre completo</span>
            <span class="dato-valor">${socio.apellidos}, ${socio.nombres}</span>
        </div>
        <div class="dato-item">
            <span class="dato-label">DNI</span>
            <span class="dato-valor dni">${socio.dni}</span>
        </div>
        <div class="dato-item">
            <span class="dato-label">Estado</span>
            <span class="estado-pill ${pillClass}">${socio.estado.toUpperCase()}</span>
        </div>
        <div class="dato-item">
            <span class="dato-label">Hora registro</span>
            <span class="dato-valor">${socio.hora_registro}</span>
        </div>
        <div class="dato-item">
            <span class="dato-label">Total visitas</span>
            <span class="dato-valor">${socio.total_visitas}</span>
        </div>
    `;

    document.getElementById('btnNuevo').style.display = 'flex';

    // Auto-reiniciar después de 5 segundos si el acceso fue exitoso
    if (clase === 'success') {
        setTimeout(() => reiniciarEscaner(), 5000);
    }
}

// ============================================
// MOSTRAR ERROR
// ============================================
function mostrarError(titulo, mensaje) {
    const resultado = document.getElementById('resultado');
    resultado.className = 'resultado visible danger';

    document.getElementById('resultadoIcon').textContent   = '❌';
    document.getElementById('resultadoTitulo').textContent = titulo;
    document.getElementById('resultadoSub').textContent    = mensaje;
    document.getElementById('resultadoBody').innerHTML     = `
        <div style="grid-column: span 2; color: var(--text-muted); font-size: 0.85rem;">
            Verifica que el QR sea válido o intenta con DNI manual.
        </div>
    `;

    document.getElementById('btnNuevo').style.display = 'flex';
}

// ============================================
// HISTORIAL
// ============================================
function agregarHistorial(socio) {
    historial.unshift({
        nombre: `${socio.nombres} ${socio.apellidos}`,
        dni:    socio.dni,
        hora:   socio.hora_registro,
        estado: socio.estado
    });

    const lista = document.getElementById('historialLista');

    if (historial.length === 1) lista.innerHTML = '';

    const item = document.createElement('div');
    item.className = 'historial-item';
    item.innerHTML = `
        <div>
            <div class="historial-nombre">${socio.nombres} ${socio.apellidos}</div>
            <div class="historial-hora">DNI: ${socio.dni}</div>
        </div>
        <div class="historial-hora">${socio.hora_registro}</div>
    `;

    lista.insertBefore(item, lista.firstChild);

    // Máximo 10 en historial
    if (lista.children.length > 10) lista.removeChild(lista.lastChild);
}

// ============================================
// CONTROLES
// ============================================
function pausarEscaner() {
    if (html5QrCode && escaneando) {
        html5QrCode.pause(true);
        escaneando = false;
    }
}

function reiniciarEscaner() {
    document.getElementById('resultado').className = 'resultado';
    document.getElementById('btnNuevo').style.display = 'none';
    ultimoDniEscaneado = '';

    if (html5QrCode) {
        html5QrCode.resume();
        escaneando = true;
        actualizarStatus('activo', '📷 Cámara activa — Apunta al código QR');
    }
}

function toggleManual() {
    const container = document.getElementById('manualContainer');
    container.classList.toggle('visible');
    if (container.classList.contains('visible')) {
        document.getElementById('dniManual').focus();
    }
}

function verificarDNIManual() {
    const dni = document.getElementById('dniManual').value.trim();
    if (dni.length !== 8 || !/^\d{8}$/.test(dni)) {
        alert('Por favor ingresa un DNI válido de 8 dígitos');
        return;
    }
    pausarEscaner();
    verificarSocio(dni);
    document.getElementById('dniManual').value = '';
}

// ============================================
// UTILIDADES
// ============================================
function actualizarStatus(tipo, texto) {
    document.getElementById('statusDot').className  = 'status-dot ' + tipo;
    document.getElementById('statusText').textContent = texto;
}

function mostrarLoading(mostrar) {
    document.getElementById('loadingOverlay').className =
        'loading-overlay' + (mostrar ? ' visible' : '');
}

function flashPantalla(tipo) {
    const flash = document.getElementById('flashOverlay');
    flash.className    = 'flash-overlay ' + tipo;
    flash.style.opacity = '1';
    setTimeout(() => { flash.style.opacity = '0'; }, 300);
}

// ============================================
// INICIAR AL CARGAR
// ============================================
document.addEventListener('DOMContentLoaded', () => {
    iniciarEscaner();
});