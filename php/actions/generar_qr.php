<?php
require_once __DIR__ . '/../includes/config_session.php';
verificarSesion();

require_once __DIR__ . '/../../vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;

// Verificar que se pasó un DNI
if (!isset($_GET['dni']) || empty($_GET['dni'])) {
    http_response_code(400);
    die('DNI requerido');
}

$dni = $_GET['dni'];

// Validar que el DNI existe en la BD
$conexion = getConexion();
$sql = "SELECT * FROM socios WHERE DNI = ?";
$stmt = $conexion->prepare($sql);
$stmt->execute([$dni]);
$socio = $stmt->fetch();

if (!$socio) {
    http_response_code(404);
    die('Socio no encontrado');
}

// Generar QR con solo el DNI
$qrCode = new QrCode(
    data: $dni,
    encoding: new Encoding('UTF-8'),
    errorCorrectionLevel: ErrorCorrectionLevel::High,
    size: 300,
    margin: 10,
    roundBlockSizeMode: RoundBlockSizeMode::Margin,
    foregroundColor: new Color(0, 0, 0),
    backgroundColor: new Color(255, 255, 255)
);

$writer = new PngWriter();
$result = $writer->write($qrCode);

// Devolver imagen PNG
header('Content-Type: ' . $result->getMimeType());
echo $result->getString();
?>