<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
require_once __DIR__ . '/../includes/config_session.php';
verificarSesion();
$usuario = getUsuarioActual();

if (!isset($_GET['dni']) || empty($_GET['dni'])) {
    header('Location: ver_socios.php');
    exit;
}

$dni = $_GET['dni'];
$conexion = getConexion();

$sql = "SELECT * FROM socios WHERE DNI = ?";
$stmt = $conexion->prepare($sql);
$stmt->execute([$dni]);
$socio = $stmt->fetch();

if (!$socio) {
    header('Location: ver_socios.php');
    exit;
}

// Estadísticas del socio
$sql_stats = "SELECT COUNT(*) as total_visitas, MAX(fecha_hora) as ultima_visita 
              FROM asistencias WHERE socio_id = ?";
$stmt_stats = $conexion->prepare($sql_stats);
$stmt_stats->execute([$socio['ID']]);
$stats = $stmt_stats->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carnet - <?php echo $socio['NOMBRES'] . ' ' . $socio['APELLIDOS']; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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

        /* === BOTONES DE ACCIÓN === */
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

        .btn-back {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        /* === CARNET === */
        .carnet-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .carnet-label {
            color: rgba(255,255,255,0.5);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .carnet {
            width: 380px;
            background: linear-gradient(135deg, #151829, #1e2340);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.5),
                0 0 0 1px rgba(0, 217, 255, 0.2);
            position: relative;
        }

        /* Línea superior */
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
            50% { background-position: 100%; }
        }

        /* Header del carnet */
        .carnet-header {
            background: linear-gradient(135deg, rgba(0, 217, 255, 0.15), rgba(0, 153, 204, 0.1));
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(0, 217, 255, 0.2);
        }

        .club-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .club-logo {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #00D9FF, #0099CC);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .club-name {
            color: #fff;
            font-weight: 700;
            font-size: 0.95rem;
            line-height: 1.3;
        }

        .club-sub {
            color: rgba(255,255,255,0.5);
            font-size: 0.75rem;
        }

        .carnet-badge {
            background: rgba(0, 217, 255, 0.15);
            border: 1px solid rgba(0, 217, 255, 0.3);
            color: #00D9FF;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        /* Body del carnet */
        .carnet-body {
            padding: 1.5rem;
            display: flex;
            gap: 1.25rem;
            align-items: flex-start;
        }

        /* QR */
        .qr-container {
            flex-shrink: 0;
            background: #fff;
            border-radius: 12px;
            padding: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .qr-container img {
            width: 110px;
            height: 110px;
            display: block;
        }

        .qr-label {
            text-align: center;
            color: rgba(255,255,255,0.4);
            font-size: 0.65rem;
            margin-top: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Datos del socio */
        .socio-data {
            flex: 1;
            min-width: 0;
        }

        .socio-nombre {
            color: #fff;
            font-size: 1.1rem;
            font-weight: 700;
            line-height: 1.3;
            margin-bottom: 0.25rem;
            word-break: break-word;
        }

        .socio-apellido {
            color: rgba(255,255,255,0.6);
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }

        .dato-row {
            display: flex;
            flex-direction: column;
            margin-bottom: 0.6rem;
        }

        .dato-label {
            color: rgba(255,255,255,0.4);
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .dato-value {
            color: #fff;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .dato-value.dni {
            font-family: 'Courier New', monospace;
            color: #00D9FF;
            font-size: 1rem;
        }

        /* Estado badge */
        .estado-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.3rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .estado-activo { background: rgba(0, 227, 150, 0.15); color: #00E396; border: 1px solid rgba(0, 227, 150, 0.3); }
        .estado-inactivo { background: rgba(255, 69, 96, 0.15); color: #FF4560; border: 1px solid rgba(255, 69, 96, 0.3); }
        .estado-vitalicio { background: rgba(0, 217, 255, 0.15); color: #00D9FF; border: 1px solid rgba(0, 217, 255, 0.3); }
        .estado-transeunte { background: rgba(254, 176, 25, 0.15); color: #FEB019; border: 1px solid rgba(254, 176, 25, 0.3); }
        .estado-suspendido { background: rgba(119, 54, 116, 0.15); color: #b86db4; border: 1px solid rgba(119, 54, 116, 0.3); }

        /* Footer del carnet */
        .carnet-footer {
            padding: 0.75rem 1.5rem;
            background: rgba(0, 0, 0, 0.2);
            border-top: 1px solid rgba(255,255,255,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-text {
            color: rgba(255,255,255,0.3);
            font-size: 0.7rem;
        }

        .footer-id {
            color: rgba(0, 217, 255, 0.6);
            font-size: 0.75rem;
            font-weight: 600;
            font-family: 'Courier New', monospace;
        }

        /* === PRINT STYLES === */
        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .action-buttons {
                display: none !important;
            }

            .carnet {
                box-shadow: none;
                border: 2px solid #ddd;
                width: 350px;
            }

            .carnet::before {
                animation: none;
            }

            .carnet-label {
                display: none;
            }
        }

        /* === RESPONSIVE === */
        @media (max-width: 480px) {
            .carnet {
                width: 100%;
                max-width: 380px;
            }

            .carnet-body {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .dato-row {
                align-items: center;
            }
        }
    </style>
</head>
<body>

    <!-- BOTONES -->
    <div class="action-buttons">
        <a href="javascript:history.back()" class="btn btn-back">
            ← Volver
        </a>
        <button onclick="window.print()" class="btn btn-print">
            🖨️ Imprimir Carnet
        </button>
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
                <div class="carnet-badge">
                    <?php echo strtoupper($socio['ESTADO']); ?>
                </div>
            </div>

            <!-- BODY -->
            <div class="carnet-body">
                <!-- QR -->
                <div>
                    <div class="qr-container">
                        <img src="../actions/generar_qr.php?dni=<?php echo $socio['DNI']; ?>" 
                             alt="QR del socio">
                    </div>
                    <div class="qr-label">Escanear para verificar</div>
                </div>

                <!-- DATOS -->
                <div class="socio-data">
                    <div class="socio-nombre"><?php echo htmlspecialchars($socio['NOMBRES']); ?></div>
                    <div class="socio-apellido"><?php echo htmlspecialchars($socio['APELLIDOS']); ?></div>

                    <div class="dato-row">
                        <span class="dato-label">DNI</span>
                        <span class="dato-value dni"><?php echo $socio['DNI']; ?></span>
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
                            <?php 
                            $iconos = [
                                'activo' => '✅',
                                'inactivo' => '❌',
                                'vitalicio' => '♾️',
                                'transeunte' => '🔄',
                                'suspendido' => '⛔'
                            ];
                            echo ($iconos[$socio['ESTADO']] ?? '•') . ' ' . strtoupper($socio['ESTADO']);
                            ?>
                        </span>
                    </div>

                    <div class="dato-row">
                        <span class="dato-label">Total visitas</span>
                        <span class="dato-value"><?php echo $stats['total_visitas'] ?? 0; ?></span>
                    </div>
                </div>
            </div>

            <!-- FOOTER -->
            <div class="carnet-footer">
                <span class="footer-text">
                    Generado: <?php echo date('d/m/Y H:i'); ?>
                </span>
                <span class="footer-id">ID #<?php echo $socio['ID']; ?></span>
            </div>
        </div>
    </div>

</body>
</html>