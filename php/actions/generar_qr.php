<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/../includes/config_session.php';
verificarSesion();

require_once __DIR__ . '/../../vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;

// Validar DNI recibido
$dni = trim($_GET['dni'] ?? '');

if (empty($dni) || !preg_match('/^\d{8}$/', $dni)) {
    http_response_code(400);
    die('DNI inválido o requerido');
}

// Verificar que el socio existe en la BD
$conexion = getConexion();
$stmt     = $conexion->prepare("SELECT ID FROM socios WHERE DNI = ?");
$stmt->execute([$dni]);

if (!$stmt->fetch()) {
    http_response_code(404);
    die('Socio no encontrado');
}

// Generar imagen QR con el DNI
$qrCode = new QrCode(
    data:                $dni,
    encoding:            new Encoding('UTF-8'),
    errorCorrectionLevel: ErrorCorrectionLevel::High,
    size:                300,
    margin:              10,
    roundBlockSizeMode:  RoundBlockSizeMode::Margin,
    foregroundColor:     new Color(0, 0, 0),
    backgroundColor:     new Color(255, 255, 255)
);

$result = (new PngWriter())->write($qrCode);

header('Content-Type: ' . $result->getMimeType());
echo $result->getString();