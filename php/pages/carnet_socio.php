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
$js_nombre = htmlspecialchars($socio['NOMBRES'] . ' ' . $socio['APELLIDOS'], ENT_QUOTES);
$js_dni    = htmlspecialchars($socio['DNI'], ENT_QUOTES);
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
    <link rel="stylesheet" href="../../css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../css/carnet_socio.css?v=<?php echo time(); ?>">
</head>
<body>

    <!-- Datos para JS via atributos data-* (evita inline JS con PHP) -->
    <div id="carnet-data"
         data-nombre="<?php echo $js_nombre; ?>"
         data-dni="<?php echo $js_dni; ?>"
         data-estado="<?php echo $js_estado; ?>"
         style="display:none;">
    </div>

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

    <script src="../../js/carnet_socio.js"></script>
</body>
</html>