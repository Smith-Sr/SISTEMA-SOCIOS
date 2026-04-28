<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/../includes/config_session.php';
verificarSesion();
$usuario  = getUsuarioActual();
$conexion = getConexion();

// Validar DNI recibido
$dni = trim($_GET['dni'] ?? '');
if (empty($dni) || !preg_match('/^\d{8}$/', $dni)) {
    header('Location: ver_socios.php');
    exit;
}

// Solo columnas necesarias
$stmt = $conexion->prepare("SELECT ID, DNI, NOMBRES, APELLIDOS, ESTADO, INGRESO FROM socios WHERE DNI = ?");
$stmt->execute([$dni]);
$socio = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$socio) {
    header('Location: ver_socios.php');
    exit;
}

// Estadísticas del socio
$stmt_stats = $conexion->prepare("SELECT COUNT(*) AS total_visitas FROM asistencias WHERE socio_id = ?");
$stmt_stats->execute([$socio['ID']]);
$total_visitas = $stmt_stats->fetchColumn();

// Iconos por estado
$iconos_estado = [
    'activo'     => '✅',
    'inactivo'   => '❌',
    'vitalicio'  => '♾️',
    'transeunte' => '🔄',
    'suspendido' => '⛔'
];

// Datos para el JS — escapados correctamente
$js_nombre = addslashes(htmlspecialchars($socio['NOMBRES'] . ' ' . $socio['APELLIDOS']));
$js_dni    = htmlspecialchars($socio['DNI']);
$js_estado = strtoupper($socio['ESTADO']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carnet - <?php echo htmlspecialchars($socio['NOMBRES'] . ' ' . $socio['APELLIDOS']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js" defer></script>
    <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: 'Inter', sans-serif;
        background: #0B0E1D;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        gap: 2rem;
    }

    /* BOTONES */
    .action-buttons {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        justify-content: center;
    }

    .btn {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 10px;
        font-size: 0.95rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
    }

    .btn-print {
        background: linear-gradient(135deg, #00D9FF, #0099CC);
        color: #000;
    }
    .btn-print:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(0, 217, 255, 0.4);
    }

    .btn-share {
        background: linear-gradient(135deg, #8B5CF6, #6D28D9);
        color: #fff;
    }
    .btn-share:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(139, 92, 246, 0.4);
    }
    .btn-share:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    .btn-back {
        background: rgba(255, 255, 255, 0.1);
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .btn-back:hover { background: rgba(255, 255, 255, 0.15); }

    /* CARNET */
    .carnet-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
    }

    .carnet-label {
        color: rgba(255, 255, 255, 0.5);
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .carnet {
        width: 380px;
        background: linear-gradient(135deg, #151829, #1e2340);
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0,0,0,0.5), 0 0 0 1px rgba(0,217,255,0.2);
        position: relative;
    }

    .carnet::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 4px;
        background: linear-gradient(90deg, #00D9FF, #0099CC, #00D9FF);
        background-size: 200%;
        animation: shimmer 3s ease infinite;
    }

    @keyframes shimmer {
        0%, 100% { background-position: 0%; }
        50%       { background-position: 100%; }
    }

    .carnet-header {
        background: linear-gradient(135deg, rgba(0,217,255,0.15), rgba(0,153,204,0.1));
        padding: 1.25rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid rgba(0,217,255,0.2);
    }

    .club-info { display: flex; align-items: center; gap: 0.75rem; }

    .club-logo {
        width: 45px; height: 45px;
        background: linear-gradient(135deg, #00D9FF, #0099CC);
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem;
    }

    .club-name { color: #fff; font-weight: 700; font-size: 0.95rem; line-height: 1.3; }
    .club-sub  { color: rgba(255,255,255,0.5); font-size: 0.75rem; }

    .carnet-badge {
        background: rgba(0,217,255,0.15);
        border: 1px solid rgba(0,217,255,0.3);
        color: #00D9FF;
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .carnet-body {
        padding: 1.5rem;
        display: flex;
        gap: 1.25rem;
        align-items: flex-start;
    }

    .qr-container {
        flex-shrink: 0;
        background: #fff;
        border-radius: 12px;
        padding: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        display: flex; justify-content: center; align-items: center;
    }

    .qr-container img { width: 110px; height: 110px; display: block; }

    .qr-label {
        text-align: center;
        color: rgba(255,255,255,0.4);
        font-size: 0.65rem;
        margin-top: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .socio-data { flex: 1; min-width: 0; }

    .socio-nombre {
        color: #fff;
        font-size: 1.1rem; font-weight: 700;
        line-height: 1.3; margin-bottom: 0.25rem;
        word-break: break-word;
    }

    .socio-apellido { color: rgba(255,255,255,0.6); font-size: 0.85rem; margin-bottom: 1rem; }

    .dato-row { display: flex; flex-direction: column; margin-bottom: 0.6rem; }

    .dato-label {
        color: rgba(255,255,255,0.4);
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .dato-value         { color: #fff; font-size: 0.9rem; font-weight: 600; }
    .dato-value.dni     { font-family: 'Courier New', monospace; color: #00D9FF; font-size: 1rem; }

    .estado-badge {
        display: inline-flex; align-items: center; gap: 0.35rem;
        padding: 0.3rem 0.75rem; border-radius: 20px;
        font-size: 0.75rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.5px;
    }

    .estado-activo     { background: rgba(0,227,150,0.15);   color: #00E396; border: 1px solid rgba(0,227,150,0.3); }
    .estado-inactivo   { background: rgba(255,69,96,0.15);   color: #FF4560; border: 1px solid rgba(255,69,96,0.3); }
    .estado-vitalicio  { background: rgba(0,217,255,0.15);   color: #00D9FF; border: 1px solid rgba(0,217,255,0.3); }
    .estado-transeunte { background: rgba(254,176,25,0.15);  color: #FEB019; border: 1px solid rgba(254,176,25,0.3); }
    .estado-suspendido { background: rgba(119,54,116,0.15);  color: #b86db4; border: 1px solid rgba(119,54,116,0.3); }

    .carnet-footer {
        padding: 0.75rem 1.5rem;
        background: rgba(0,0,0,0.2);
        border-top: 1px solid rgba(255,255,255,0.05);
        display: flex; justify-content: space-between; align-items: center;
    }

    .footer-text { color: rgba(255,255,255,0.3); font-size: 0.7rem; }
    .footer-id   { color: rgba(0,217,255,0.6); font-size: 0.75rem; font-weight: 600; font-family: 'Courier New', monospace; }

    @media print {
        body { background: #fff; padding: 0; }
        .action-buttons, .carnet-label { display: none !important; }
        .carnet { box-shadow: none; border: 2px solid #ddd; width: 350px; }
        .carnet::before { animation: none; }
    }

    @media (max-width: 480px) {
        .carnet { width: 100%; max-width: 380px; }
        .carnet-body { flex-direction: column; align-items: center; text-align: center; }
        .dato-row { align-items: center; }
    }
    </style>
</head>
<body>

    <!-- BOTONES -->
    <div class="action-buttons">
        <a href="gestionar_socios.php" class="btn btn-back">← Volver</a>
        <button onclick="window.print()" class="btn btn-print">🖨️ Imprimir Carnet</button>
        <button onclick="compartirCarnet(event)" class="btn btn-share">📤 Compartir</button>
    </div>

    <!-- CARNET -->
    <div class="carnet-wrapper">
        <div class="carnet-label">Vista previa del carnet</div>
        <div class="carnet">

            <!-- HEADER -->
            <div class="carnet-header">
                <div class="club-info">
                    <div class="club-logo">🎾</div>
                    <div>
                        <div class="club-name">Club Lawn Tennis</div>
                        <div class="club-sub">Carnet de Socio</div>
                    </div>
                </div>
                <div class="carnet-badge"><?php echo strtoupper($socio['ESTADO']); ?></div>
            </div>

            <!-- BODY -->
            <div class="carnet-body">
                <div>
                    <div class="qr-container">
                        <img src="../actions/generar_qr.php?dni=<?php echo htmlspecialchars($socio['DNI']); ?>"
                             alt="QR del socio">
                    </div>
                    <div class="qr-label">Escanear para verificar</div>
                </div>

                <div class="socio-data">
                    <div class="socio-nombre"><?php echo htmlspecialchars($socio['NOMBRES']); ?></div>
                    <div class="socio-apellido"><?php echo htmlspecialchars($socio['APELLIDOS']); ?></div>

                    <div class="dato-row">
                        <span class="dato-label">DNI</span>
                        <span class="dato-value dni"><?php echo htmlspecialchars($socio['DNI']); ?></span>
                    </div>

                    <div class="dato-row">
                        <span class="dato-label">Miembro desde</span>
                        <span class="dato-value">
                            <?php echo $socio['INGRESO'] ? date('d/m/Y', strtotime($socio['INGRESO'])) : 'N/A'; ?>
                        </span>
                    </div>

                    <div class="dato-row">
                        <span class="dato-label">Estado</span>
                        <span class="estado-badge estado-<?php echo $socio['ESTADO']; ?>">
                            <?php echo ($iconos_estado[$socio['ESTADO']] ?? '•') . ' ' . strtoupper($socio['ESTADO']); ?>
                        </span>
                    </div>

                    <div class="dato-row">
                        <span class="dato-label">Total visitas</span>
                        <span class="dato-value"><?php echo $total_visitas; ?></span>
                    </div>
                </div>
            </div>

            <!-- FOOTER -->
            <div class="carnet-footer">
                <span class="footer-text">Generado: <?php echo date('d/m/Y H:i'); ?></span>
                <span class="footer-id">ID #<?php echo $socio['ID']; ?></span>
            </div>
        </div>
    </div>

    <script>
    async function compartirCarnet(event) {
        const btn          = event.currentTarget;
        const textoOriginal = btn.innerHTML;

        btn.innerHTML = '⏳ Generando imagen...';
        btn.disabled  = true;

        try {
            const canvas = await html2canvas(document.querySelector('.carnet'), {
                scale:           3,
                backgroundColor: '#151829',
                useCORS:         true,
                logging:         false
            });

            const nombre = "<?php echo $js_nombre; ?>";
            const dni    = "<?php echo $js_dni; ?>";
            const estado = "<?php echo $js_estado; ?>";
            const texto  = `🎾 *Club Lawn Tennis*\n👤 ${nombre}\n🪪 DNI: ${dni}\n✅ Estado: ${estado}`;

            canvas.toBlob(async (blob) => {
                const archivo = new File([blob], `carnet-${dni}.png`, { type: 'image/png' });

                const puedeCompartir = navigator.share &&
                                       navigator.canShare &&
                                       navigator.canShare({ files: [archivo] });

                if (puedeCompartir) {
                    await navigator.share({
                        title: `Carnet - ${nombre}`,
                        text:  texto,
                        files: [archivo]
                    });
                } else {
                    // Fallback: descargar imagen
                    const url = URL.createObjectURL(blob);
                    const a   = document.createElement('a');
                    a.href     = url;
                    a.download = `carnet-${dni}.png`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                }

                btn.innerHTML = textoOriginal;
                btn.disabled  = false;

            }, 'image/png');

        } catch (err) {
            if (err.name !== 'AbortError') {
                alert('No se pudo compartir. Intenta de nuevo.');
            }
            btn.innerHTML = textoOriginal;
            btn.disabled  = false;
        }
    }
    </script>

</body>
</html>