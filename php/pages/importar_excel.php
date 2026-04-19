<?php
require_once __DIR__ . '/../includes/config_session.php';
verificarAdmin();
$usuario = getUsuarioActual();

require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

$mensaje = '';
$tipo_mensaje = '';

// Función mejorada para convertir fechas
function convertirFechaExcel($valor) {
    // Si es vacío o NULL
    if (empty($valor) || $valor === '' || $valor === null) {
        return null;
    }
    
    // Si es número (formato Excel)
    if (is_numeric($valor)) {
        try {
            $fecha = Date::excelToDateTimeObject($valor);
            $año = (int)$fecha->format('Y');
            
            // Validar que sea una fecha razonable
            if ($año < 1900 || $año > 2100) {
                return null;
            }
            
            return $fecha->format('Y-m-d');
        } catch (Exception $e) {
            return null;
        }
    }
    
    // Si es texto tipo dd/mm/yyyy
    if (is_string($valor)) {
        $partes = explode('/', $valor);
        if (count($partes) == 3) {
            $dia = (int)$partes[0];
            $mes = (int)$partes[1];
            $año = (int)$partes[2];
            
            // Validar fecha
            if (checkdate($mes, $dia, $año) && $año >= 1900 && $año <= 2100) {
                return sprintf('%04d-%02d-%02d', $año, $mes, $dia);
            }
        }
    }
    
    return null;
}

$preview_data = [];
$mostrar_preview = false;

// PASO 1: Subir y leer Excel
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['archivo_excel'])) {
    $archivo = $_FILES['archivo_excel'];
    
    if ($archivo['error'] === UPLOAD_ERR_OK) {
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        
        if ($extension === 'xlsx') {
            try {
                $spreadsheet = IOFactory::load($archivo['tmp_name']);
                $worksheet = $spreadsheet->getActiveSheet();
                $datos = $worksheet->toArray();
                
                array_shift($datos); // Eliminar encabezados
                
                $fechas_invalidas = 0;
                
                foreach ($datos as $fila) {
                    if (!empty($fila[1])) {
                        $fecha_convertida = convertirFechaExcel($fila[4]);
                        
                        if ($fecha_convertida === null) {
                            $fechas_invalidas++;
                        }
                        
                        $preview_data[] = [
                            'dni' => trim($fila[1]),
                            'apellidos' => trim($fila[2]),
                            'nombres' => trim($fila[3]),
                            'ingreso' => $fecha_convertida,
                            'tipo' => strtolower(trim($fila[5]))
                        ];
                    }
                }
                
                if (count($preview_data) > 0) {
                    $mostrar_preview = true;
                    $_SESSION['preview_data'] = $preview_data;
                    
                    $mensaje = count($preview_data) . ' socios encontrados.';
                    if ($fechas_invalidas > 0) {
                        $mensaje .= " ⚠️ $fechas_invalidas con fechas inválidas (se agregará manualmente después).";
                    }
                    $tipo_mensaje = 'info';
                } else {
                    $mensaje = 'No se encontraron datos válidos en el archivo';
                    $tipo_mensaje = 'error';
                }
                
            } catch (Exception $e) {
                $mensaje = 'Error al leer el archivo: ' . $e->getMessage();
                $tipo_mensaje = 'error';
            }
        } else {
            $mensaje = 'Solo se permiten archivos .xlsx';
            $tipo_mensaje = 'error';
        }
    } else {
        $mensaje = 'Error al subir el archivo';
        $tipo_mensaje = 'error';
    }
}

// PASO 2: Confirmar e importar
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirmar_importacion'])) {
    if (!isset($_SESSION['preview_data']) || empty($_SESSION['preview_data'])) {
        $mensaje = 'No hay datos para importar. Sube el archivo nuevamente.';
        $tipo_mensaje = 'error';
    } else {
        $preview_data = $_SESSION['preview_data'];
        $modo = $_POST['modo_importacion'] ?? 'agregar';
        $conexion = getConexion();
        
        $importados = 0;
        $actualizados = 0;
        $errores = 0;
        $duplicados = 0;
        
        try {
            // MODO 3: REEMPLAZAR TODO
            if ($modo === 'reemplazar') {
                $conexion->exec("DELETE FROM asistencias");
                $conexion->exec("DELETE FROM socios");
                $mensaje_modo = "Base de datos limpiada. ";
            }
            
            foreach ($preview_data as $socio) {
                try {
                    // Verificar si existe
                    $sql_verificar = "SELECT COUNT(*) FROM socios WHERE DNI = ?";
                    $stmt_verificar = $conexion->prepare($sql_verificar);
                    $stmt_verificar->execute([$socio['dni']]);
                    $existe = $stmt_verificar->fetchColumn();
                    
                    if ($existe > 0) {
                        // MODO 2: ACTUALIZAR
                        if ($modo === 'actualizar' || $modo === 'reemplazar') {
                            $sql_actualizar = "UPDATE socios SET APELLIDOS = ?, NOMBRES = ?, INGRESO = ?, ESTADO = ? WHERE DNI = ?";
                            $stmt_actualizar = $conexion->prepare($sql_actualizar);
                            $resultado = $stmt_actualizar->execute([
                                $socio['apellidos'],
                                $socio['nombres'],
                                $socio['ingreso'],
                                $socio['tipo'],
                                $socio['dni']
                            ]);
                            
                            if ($resultado) {
                                $actualizados++;
                            } else {
                                $errores++;
                            }
                        } 
                        // MODO 1: SOLO AGREGAR (omitir duplicados)
                        else {
                            $duplicados++;
                        }
                    } else {
                        // Insertar nuevo
                        $sql_insertar = "INSERT INTO socios (DNI, APELLIDOS, NOMBRES, INGRESO, ESTADO) VALUES (?, ?, ?, ?, ?)";
                        $stmt_insertar = $conexion->prepare($sql_insertar);
                        $resultado = $stmt_insertar->execute([
                            $socio['dni'],
                            $socio['apellidos'],
                            $socio['nombres'],
                            $socio['ingreso'],
                            $socio['tipo']
                        ]);
                        
                        if ($resultado) {
                            $importados++;
                        } else {
                            $errores++;
                        }
                    }
                    
                } catch (PDOException $e) {
                    $errores++;
                    error_log("Error al procesar DNI " . $socio['dni'] . ": " . $e->getMessage());
                }
            }
            
            unset($_SESSION['preview_data']);
            
            // Construir mensaje según modo
            if ($modo === 'agregar') {
                $mensaje = "✅ $importados socios agregados";
                if ($duplicados > 0) $mensaje .= " | ⚠️ $duplicados duplicados omitidos";
            } elseif ($modo === 'actualizar') {
                $mensaje = "✅ $importados nuevos agregados | 🔄 $actualizados actualizados";
                if ($duplicados > 0) $mensaje .= " | ⚠️ $duplicados sin cambios";
            } else {
                $mensaje = "✅ Base de datos reemplazada: $importados socios importados";
            }
            
            if ($errores > 0) $mensaje .= " | ❌ $errores errores";
            
            $tipo_mensaje = 'success';
            $mostrar_preview = false;
            
        } catch (PDOException $e) {
            $mensaje = 'Error crítico: ' . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Excel - Sistema de Gestión</title>
    <link rel="stylesheet" href="../../css/topbar-menu.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../css/style.css?v=<?php echo time(); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

<!-- TOPBAR FIJO -->
<div class="topbar-fixed">
    <div class="topbar-logo">
        <span>🏢</span>
        <span>SociosApp</span>
    </div>
    <button class="menu-btn" onclick="toggleMenu()">
        <div class="menu-line"></div>
        <div class="menu-line"></div>
        <div class="menu-line"></div>
    </button>
</div>

<!-- OVERLAY -->
<div class="menu-overlay" onclick="toggleMenu()"></div>

<!-- MENÚ FLOTANTE -->
<div class="floating-menu">
    <div class="menu-section">
        <div class="menu-title">📊 Principal</div>
        <a href="../../index.php" class="menu-item">
            <span>📈</span>
            <span>Dashboard</span>
        </a>
        <a href="buscar_socios.php" class="menu-item">
            <span>🔍</span>
            <span>Buscar Socio</span>
        </a>
        <a href="ver_socios.php" class="menu-item">
            <span>📋</span>
            <span>Lista Completa</span>
        </a>
    </div>
    
    <div class="menu-section">
        <div class="menu-title">⚙️ Administración</div>
        <a href="agregar_socio_web.php" class="menu-item">
            <span>➕</span>
            <span>Agregar Socio</span>
        </a>
        <a href="importar_excel.php" class="menu-item active">
            <span>📤</span>
            <span>Importar Excel</span>
        </a>
        <a href="gestionar_socios.php" class="menu-item">
            <span>✏️</span>
            <span>Gestionar Socios</span>
        </a>
        <a href="gestionar_usuarios.php" class="menu-item">
            <span>👥</span>
            <span>Usuarios</span>
        </a>
    </div>
    
    <div class="menu-section">
        <div class="menu-title">👤 Usuario</div>
        <div style="padding: 12px; background: rgba(0, 217, 255, 0.1); border-radius: 10px; margin-bottom: 10px;">
            <div style="font-weight: 600;"><?php echo $usuario['nombre']; ?></div>
            <div style="font-size: 0.85rem; color: var(--text-secondary);"><?php echo $usuario['rol']; ?></div>
        </div>
        <a href="../actions/logout.php" class="menu-item" style="color: #FF4560;">
            <span>🚪</span>
            <span>Cerrar Sesión</span>
        </a>
    </div>
</div>

<!-- BOTÓN RETROCEDER -->
<a href="javascript:history.back()" class="back-btn" title="Volver">←</a>

<!-- CONTENIDO -->
<div class="container">
    <h1 style="margin-bottom: 10px;">📤 Importar Socios desde Excel</h1>
    <p style="color: var(--text-secondary); margin-bottom: 30px;">Sube tu archivo .xlsx con validación mejorada de fechas</p>
    
    <!-- MENSAJES -->
    <?php if ($mensaje): ?>
        <div class="card" style="border-left: 4px solid <?php echo $tipo_mensaje == 'success' ? '#00E396' : ($tipo_mensaje == 'error' ? '#FF4560' : '#FFA500'); ?>; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 1.5rem;">
                    <?php echo $tipo_mensaje == 'success' ? '✅' : ($tipo_mensaje == 'error' ? '❌' : 'ℹ️'); ?>
                </span>
                <span style="font-weight: 500;"><?php echo $mensaje; ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- FORMULARIO DE SUBIDA -->
    <?php if (!$mostrar_preview): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Paso 1: Selecciona tu archivo Excel</h2>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>📁 Archivo Excel (.xlsx)</label>
                <input type="file" 
                       name="archivo_excel" 
                       accept=".xlsx" 
                       required
                       style="padding: 10px; background: rgba(255, 255, 255, 0.05); border: 2px dashed rgba(0, 217, 255, 0.3); border-radius: 8px;">
                <small style="color: var(--text-secondary); margin-top: 8px; display: block;">
                    ✅ Fechas inválidas se detectarán automáticamente<br>
                    Formato: N° | DNI | APELLIDOS | NOMBRES | INGRESO | TIPO
                </small>
            </div>
            <button type="submit" class="btn">
                📂 Cargar y Vista Previa
            </button>
        </form>
    </div>
    <?php endif; ?>

    <!-- VISTA PREVIA -->
    <?php if ($mostrar_preview): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Paso 2: Selecciona el Modo de Importación</h2>
        </div>
        
        <form method="POST">
            <input type="hidden" name="confirmar_importacion" value="1">
            
            <!-- SELECTOR DE MODO -->
            <div style="margin-bottom: 30px;">
                <label style="display: block; margin-bottom: 15px; font-weight: 600; font-size: 1.1rem;">🔄 Modo de Importación:</label>
                
                <div style="display: grid; gap: 15px;">
                    <!-- Opción 1: Solo Agregar -->
                    <label class="stat-card" style="cursor: pointer; padding: 15px; display: flex; align-items: center; gap: 15px;">
                        <input type="radio" name="modo_importacion" value="agregar" checked style="width: 20px; height: 20px; cursor: pointer;">
                        <div style="flex: 1;">
                            <div style="font-weight: 700; font-size: 1.1rem; margin-bottom: 5px;">➕ Solo Agregar Nuevos</div>
                            <div style="font-size: 0.9rem; color: var(--text-secondary);">Omite los DNIs que ya existen. Solo agrega registros nuevos.</div>
                        </div>
                    </label>
                    
                    <!-- Opción 2: Actualizar -->
                    <label class="stat-card" style="cursor: pointer; padding: 15px; display: flex; align-items: center; gap: 15px;">
                        <input type="radio" name="modo_importacion" value="actualizar" style="width: 20px; height: 20px; cursor: pointer;">
                        <div style="flex: 1;">
                            <div style="font-weight: 700; font-size: 1.1rem; margin-bottom: 5px;">🔄 Actualizar Existentes</div>
                            <div style="font-size: 0.9rem; color: var(--text-secondary);">Si el DNI existe, actualiza sus datos. Si no existe, lo agrega.</div>
                        </div>
                    </label>
                    
                    <!-- Opción 3: Reemplazar -->
                    <label class="stat-card" style="cursor: pointer; padding: 15px; display: flex; align-items: center; gap: 15px; border: 1px solid #FF4560;">
                        <input type="radio" name="modo_importacion" value="reemplazar" style="width: 20px; height: 20px; cursor: pointer;">
                        <div style="flex: 1;">
                            <div style="font-weight: 700; font-size: 1.1rem; margin-bottom: 5px; color: #FF4560;">⚠️ Reemplazar Todo</div>
                            <div style="font-size: 0.9rem; color: var(--text-secondary);">ELIMINA todos los socios y asistencias, luego importa desde cero. ¡PELIGROSO!</div>
                        </div>
                    </label>
                </div>
            </div>
            
            <p style="margin-bottom: 20px;">
                Se encontraron <strong style="color: var(--primary);"><?php echo count($preview_data); ?> socios</strong>. 
            </p>
            
            <div class="table-container" style="max-height: 400px; overflow-y: auto; margin-bottom: 20px;">
                <table>
                    <thead>
                        <tr>
                            <th>DNI</th>
                            <th>Apellidos</th>
                            <th>Nombres</th>
                            <th>Ingreso</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($preview_data, 0, 10) as $socio): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($socio['dni']); ?></td>
                            <td><?php echo htmlspecialchars($socio['apellidos']); ?></td>
                            <td><?php echo htmlspecialchars($socio['nombres']); ?></td>
                            <td>
                                <?php if ($socio['ingreso']): ?>
                                    <?php echo date('d/m/Y', strtotime($socio['ingreso'])); ?>
                                <?php else: ?>
                                    <span style="color: #FFA500;">⚠️ Agregar manualmente</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $socio['tipo']; ?>">
                                    <?php echo strtoupper($socio['tipo']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (count($preview_data) > 10): ?>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 20px;">
                    Mostrando primeros 10 de <?php echo count($preview_data); ?> registros
                </p>
            <?php endif; ?>

            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <a href="?" class="btn" style="background: rgba(255, 255, 255, 0.1); color: var(--text-primary); text-decoration: none;">
                    ❌ Cancelar
                </a>
                <button type="submit" class="btn" onclick="return confirm('¿Confirmar importación con el modo seleccionado?');">
                    ✅ Confirmar Importación
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- INSTRUCCIONES -->
    <div class="card">
        <h3 style="margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
            <span>ℹ️</span>
            <span>Instrucciones y Mejoras</span>
        </h3>
        <ul style="list-style: none; padding: 0; display: grid; gap: 8px;">
            <li style="display: flex; align-items: center; gap: 8px;">
                <span>✅</span>
                <span><strong>Validación de fechas mejorada:</strong> Detecta y marca fechas inválidas automáticamente</span>
            </li>
            <li style="display: flex; align-items: center; gap: 8px;">
                <span>✅</span>
                <span><strong>Fechas inválidas:</strong> Se guardan como NULL y puedes editarlas después en "Gestionar Socios"</span>
            </li>
            <li style="display: flex; align-items: center; gap: 8px;">
                <span>✅</span>
                <span><strong>3 modos de importación:</strong> Elige según tu necesidad (agregar, actualizar o reemplazar)</span>
            </li>
            <li style="display: flex; align-items: center; gap: 8px;">
                <span>📌</span>
                <span>Formato Excel: N° | DNI | APELLIDOS | NOMBRES | INGRESO | TIPO</span>
            </li>
            <li style="display: flex; align-items: center; gap: 8px;">
                <span>📌</span>
                <span>Estados válidos: activo, inactivo, vitalicio, transeunte, suspendido</span>
            </li>
        </ul>
    </div>
</div>

<script src="../../js/menu.js"></script>
</body>
</html>