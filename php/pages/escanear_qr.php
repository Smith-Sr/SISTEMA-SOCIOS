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
    <link rel="stylesheet" href="../../css/escanear_qr.css?v=<?php echo time(); ?>">
    <!-- Librería QR Scanner -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
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
<script src="../../js/escanear_qr.js"></script>
</body>
</html>