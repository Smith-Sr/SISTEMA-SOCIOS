<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
require_once __DIR__ . '/../includes/config_session.php';
verificarSesion();
$usuario = getUsuarioActual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Escanear QR - Club Lawn Tennis</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Librería QR Scanner -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary: #00D9FF;
            --primary-dark: #0099CC;
            --bg: #0B0E1D;
            --bg-card: rgba(21, 24, 41, 0.9);
            --success: #00E396;
            --danger: #FF4560;
            --warning: #FEB019;
            --info: #00D9FF;
            --text: #FFFFFF;
            --text-muted: #A8B3CF;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* === TOPBAR === */
        .topbar {
            background: rgba(21, 24, 41, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .topbar-logo {
            font-size: 1.5rem;
        }

        .topbar-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary);
        }

        .topbar-sub {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .btn-back {
            padding: 0.5rem 1rem;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: var(--text);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .btn-back:hover {
            background: rgba(255,255,255,0.1);
            border-color: var(--primary);
            color: var(--primary);
        }

        /* === CONTENIDO PRINCIPAL === */
        .main {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1.5rem;
            gap: 1.5rem;
            max-width: 500px;
            margin: 0 auto;
            width: 100%;
        }

        /* === ESTADO DEL ESCÁNER === */
        .scanner-status {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.25rem;
            background: var(--bg-card);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            width: 100%;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
            animation: pulse 1.5s ease infinite;
        }

        .status-dot.esperando { background: var(--warning); }
        .status-dot.activo { background: var(--success); }
        .status-dot.error { background: var(--danger); animation: none; }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.8); }
        }

        /* === CÁMARA === */
        .camera-container {
            width: 100%;
            background: var(--bg-card);
            border: 2px solid rgba(0, 217, 255, 0.3);
            border-radius: 20px;
            overflow: hidden;
            position: relative;
        }

        #qr-reader {
            width: 100% !important;
        }

        /* Personalizar el botón de la librería */
        #qr-reader__dashboard_section_csr button {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark)) !important;
            color: #000 !important;
            border: none !important;
            border-radius: 10px !important;
            padding: 0.75rem 1.5rem !important;
            font-weight: 700 !important;
            font-size: 0.95rem !important;
            cursor: pointer !important;
            font-family: 'Inter', sans-serif !important;
            width: 100% !important;
            margin-top: 0.5rem !important;
        }

        #qr-reader__dashboard_section_fsr button {
            background: rgba(255,255,255,0.1) !important;
            color: #fff !important;
            border: 1px solid rgba(255,255,255,0.2) !important;
            border-radius: 10px !important;
            padding: 0.5rem 1rem !important;
            font-family: 'Inter', sans-serif !important;
        }

        #qr-reader__status_span {
            color: var(--text-muted) !important;
            font-family: 'Inter', sans-serif !important;
            font-size: 0.85rem !important;
        }

        #qr-reader__header_message {
            display: none !important;
        }

        /* === OVERLAY DE ESCANEO === */
        .scan-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            z-index: 10;
        }

        .scan-frame {
            width: 200px;
            height: 200px;
            position: relative;
        }

        .scan-frame::before,
        .scan-frame::after {
            content: '';
            position: absolute;
            width: 40px;
            height: 40px;
            border-color: var(--primary);
            border-style: solid;
        }

        .scan-frame::before {
            top: 0; left: 0;
            border-width: 3px 0 0 3px;
            border-radius: 4px 0 0 0;
        }

        .scan-frame::after {
            bottom: 0; right: 0;
            border-width: 0 3px 3px 0;
            border-radius: 0 0 4px 0;
        }

        .scan-line {
            position: absolute;
            left: 10px;
            right: 10px;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--primary), transparent);
            animation: scanLine 2s ease-in-out infinite;
            top: 10px;
            box-shadow: 0 0 8px var(--primary);
        }

        @keyframes scanLine {
            0%, 100% { top: 10px; opacity: 1; }
            50% { top: calc(100% - 10px); opacity: 0.7; }
        }

        /* === RESULTADO === */
        .resultado {
            width: 100%;
            border-radius: 16px;
            overflow: hidden;
            display: none;
            animation: slideUp 0.4s ease;
        }

        .resultado.visible {
            display: block;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .resultado-header {
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .resultado-icon {
            font-size: 2.5rem;
            flex-shrink: 0;
        }

        .resultado-titulo {
            font-size: 1.2rem;
            font-weight: 700;
        }

        .resultado-sub {
            font-size: 0.85rem;
            opacity: 0.8;
            margin-top: 0.2rem;
        }

        /* Colores según resultado */
        .resultado.success .resultado-header { background: rgba(0, 227, 150, 0.15); border: 1px solid rgba(0, 227, 150, 0.3); }
        .resultado.success .resultado-titulo { color: var(--success); }

        .resultado.warning .resultado-header { background: rgba(254, 176, 25, 0.15); border: 1px solid rgba(254, 176, 25, 0.3); border-bottom: none; }
        .resultado.warning .resultado-titulo { color: var(--warning); }

        .resultado.danger .resultado-header { background: rgba(255, 69, 96, 0.15); border: 1px solid rgba(255, 69, 96, 0.3); }
        .resultado.danger .resultado-titulo { color: var(--danger); }

        .resultado.info .resultado-header { background: rgba(0, 217, 255, 0.15); border: 1px solid rgba(0, 217, 255, 0.3); border-bottom: none; }
        .resultado.info .resultado-titulo { color: var(--info); }

        /* Datos del socio en resultado */
        .resultado-body {
            padding: 1.25rem 1.5rem;
            background: var(--bg-card);
            border: 1px solid rgba(255,255,255,0.08);
            border-top: none;
            border-radius: 0 0 16px 16px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .dato-item { display: flex; flex-direction: column; gap: 0.2rem; }
        .dato-label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); }
        .dato-valor { font-size: 0.95rem; font-weight: 600; color: var(--text); }
        .dato-valor.dni { font-family: 'Courier New', monospace; color: var(--primary); }

        .estado-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .pill-activo { background: rgba(0,227,150,0.15); color: var(--success); border: 1px solid rgba(0,227,150,0.3); }
        .pill-inactivo { background: rgba(255,69,96,0.15); color: var(--danger); border: 1px solid rgba(255,69,96,0.3); }
        .pill-vitalicio { background: rgba(0,217,255,0.15); color: var(--info); border: 1px solid rgba(0,217,255,0.3); }
        .pill-transeunte { background: rgba(254,176,25,0.15); color: var(--warning); border: 1px solid rgba(254,176,25,0.3); }
        .pill-suspendido { background: rgba(119,54,116,0.15); color: #b86db4; border: 1px solid rgba(119,54,116,0.3); }

        /* === CONTROLES === */
        .controles {
            width: 100%;
            display: flex;
            gap: 0.75rem;
        }

        .btn-control {
            flex: 1;
            padding: 0.875rem;
            border: none;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-nuevo {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #000;
        }

        .btn-nuevo:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,217,255,0.4);
        }

        .btn-manual {
            background: rgba(255,255,255,0.05);
            color: var(--text);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .btn-manual:hover {
            background: rgba(255,255,255,0.1);
            border-color: var(--primary);
        }

        /* === INGRESO MANUAL === */
        .manual-container {
            width: 100%;
            background: var(--bg-card);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 1.25rem;
            display: none;
        }

        .manual-container.visible {
            display: block;
            animation: slideUp 0.3s ease;
        }

        .manual-title {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-muted);
        }

        .manual-form {
            display: flex;
            gap: 0.75rem;
        }

        .manual-input {
            flex: 1;
            padding: 0.875rem 1rem;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            color: var(--text);
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
        }

        .manual-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0,217,255,0.1);
        }

        .manual-input::placeholder { color: var(--text-muted); }

        .btn-buscar {
            padding: 0.875rem 1.25rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #000;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .btn-buscar:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,217,255,0.4);
        }

        /* === HISTORIAL RÁPIDO === */
        .historial-section {
            width: 100%;
        }

        .historial-title {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .historial-lista {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            max-height: 200px;
            overflow-y: auto;
        }

        .historial-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1rem;
            background: var(--bg-card);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 10px;
            font-size: 0.85rem;
        }

        .historial-nombre {
            font-weight: 600;
            color: var(--text);
        }

        .historial-hora {
            color: var(--text-muted);
            font-size: 0.75rem;
        }

        /* === LOADING === */
        .loading-overlay {
            position: fixed;
            inset: 0;
            background: rgba(11, 14, 29, 0.8);
            backdrop-filter: blur(5px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 999;
            flex-direction: column;
            gap: 1rem;
        }

        .loading-overlay.visible {
            display: flex;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 3px solid rgba(0,217,255,0.2);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* === SONIDO VISUAL (FLASH) === */
        .flash-overlay {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.1s ease;
        }

        .flash-overlay.success { background: rgba(0, 227, 150, 0.3); }
        .flash-overlay.danger { background: rgba(255, 69, 96, 0.3); }

        /* === RESPONSIVE === */
        @media (max-width: 480px) {
            .main { padding: 1rem; gap: 1rem; }
            .resultado-body { grid-template-columns: 1fr; }
            .controles { flex-direction: column; }
        }
    </style>
</head>
<body>

<!-- FLASH OVERLAY -->
<div class="flash-overlay" id="flashOverlay"></div>

<!-- LOADING -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="spinner"></div>
    <span style="color: var(--text-muted); font-size: 0.9rem;">Verificando socio...</span>
</div>

<!-- TOPBAR -->
<div class="topbar">
    <div class="topbar-left">
        <div class="topbar-logo">🎾</div>
        <div>
            <div class="topbar-title">Escáner QR</div>
            <div class="topbar-sub">Club Lawn Tennis</div>
        </div>
    </div>
    <a href="../../index.php" class="btn-back">← Inicio</a>
</div>

<!-- CONTENIDO -->
<div class="main">

    <!-- ESTADO -->
    <div class="scanner-status">
        <div class="status-dot esperando" id="statusDot"></div>
        <span id="statusText">Iniciando cámara...</span>
    </div>

    <!-- CÁMARA -->
    <div class="camera-container">
        <div id="qr-reader"></div>
    </div>

    <!-- CONTROLES -->
    <div class="controles">
        <button class="btn-control btn-nuevo" onclick="reiniciarEscaner()" id="btnNuevo" style="display:none;">
            📷 Nuevo Escaneo
        </button>
        <button class="btn-control btn-manual" onclick="toggleManual()">
            ⌨️ Ingresar DNI Manual
        </button>
    </div>

    <!-- INGRESO MANUAL -->
    <div class="manual-container" id="manualContainer">
        <div class="manual-title">📝 Ingresar DNI manualmente</div>
        <div class="manual-form">
            <input type="number" 
                   class="manual-input" 
                   id="dniManual" 
                   placeholder="Ingrese DNI (8 dígitos)"
                   maxlength="8"
                   onkeypress="if(event.key==='Enter') verificarDNIManual()">
            <button class="btn-buscar" onclick="verificarDNIManual()">
                🔍 Verificar
            </button>
        </div>
    </div>

    <!-- RESULTADO -->
    <div class="resultado" id="resultado">
        <div class="resultado-header">
            <div class="resultado-icon" id="resultadoIcon">✅</div>
            <div>
                <div class="resultado-titulo" id="resultadoTitulo">Acceso Permitido</div>
                <div class="resultado-sub" id="resultadoSub">Asistencia registrada correctamente</div>
            </div>
        </div>
        <div class="resultado-body" id="resultadoBody"></div>
    </div>

    <!-- HISTORIAL RÁPIDO -->
    <div class="historial-section">
        <div class="historial-title">⏱️ Últimos escaneados hoy</div>
        <div class="historial-lista" id="historialLista">
            <div style="text-align:center; padding: 1rem; color: var(--text-muted); font-size: 0.85rem;">
                Aún no hay escaneos en esta sesión
            </div>
        </div>
    </div>

</div>

<script>
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
        { facingMode: "environment" }, // Cámara trasera
        config,
        onQRDetectado,
        onQRError
    ).then(() => {
        actualizarStatus('activo', '📷 Cámara activa — Apunta al código QR');
        escaneando = true;
    }).catch((err) => {
        // Si falla cámara trasera, intentar cualquier cámara
        html5QrCode.start(
            { facingMode: "user" },
            config,
            onQRDetectado,
            onQRError
        ).then(() => {
            actualizarStatus('activo', '📷 Cámara activa — Apunta al código QR');
            escaneando = true;
        }).catch((err2) => {
            actualizarStatus('error', '❌ No se pudo acceder a la cámara');
            console.error('Error cámara:', err2);
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

    // Pausar escáner y verificar
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
            body: JSON.stringify({ dni: dni })
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

    // Determinar tipo visual según estado del socio
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

    document.getElementById('resultadoIcon').textContent = icon;
    document.getElementById('resultadoTitulo').textContent = titulo;
    document.getElementById('resultadoSub').textContent = sub;

    // Limpiar clases anteriores
    resultado.className = 'resultado visible ' + clase;

    // Estado pill
    const estadoPills = {
        activo: 'pill-activo', inactivo: 'pill-inactivo',
        vitalicio: 'pill-vitalicio', transeunte: 'pill-transeunte',
        suspendido: 'pill-suspendido'
    };
    const pillClass = estadoPills[socio.estado] || 'pill-activo';

    // Contenido
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

    // Mostrar botón nuevo escaneo
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

    document.getElementById('resultadoIcon').textContent = '❌';
    document.getElementById('resultadoTitulo').textContent = titulo;
    document.getElementById('resultadoSub').textContent = mensaje;
    document.getElementById('resultadoBody').innerHTML = `
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
        dni: socio.dni,
        hora: socio.hora_registro,
        estado: socio.estado
    });

    const lista = document.getElementById('historialLista');

    if (historial.length === 1) {
        lista.innerHTML = '';
    }

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
    if (lista.children.length > 10) {
        lista.removeChild(lista.lastChild);
    }
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
    // Ocultar resultado
    document.getElementById('resultado').className = 'resultado';
    document.getElementById('btnNuevo').style.display = 'none';
    ultimoDniEscaneado = '';

    // Reanudar
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
    const dot = document.getElementById('statusDot');
    const span = document.getElementById('statusText');
    dot.className = 'status-dot ' + tipo;
    span.textContent = texto;
}

function mostrarLoading(mostrar) {
    document.getElementById('loadingOverlay').className =
        'loading-overlay' + (mostrar ? ' visible' : '');
}

function flashPantalla(tipo) {
    const flash = document.getElementById('flashOverlay');
    flash.className = 'flash-overlay ' + tipo;
    flash.style.opacity = '1';
    setTimeout(() => { flash.style.opacity = '0'; }, 300);
}

// ============================================
// INICIAR AL CARGAR
// ============================================
document.addEventListener('DOMContentLoaded', () => {
    iniciarEscaner();
});
</script>

</body>
</html>