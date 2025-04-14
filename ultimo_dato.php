<?php
require '../conexion.php'; // Se asume que tu archivo de conexión configura $conn como mysqli

// Validar conexión
if (!$conn) {
    die(json_encode(['error' => 'No se pudo establecer la conexión a la base de datos']));
}

// Función para obtener el último valor registrado de la tabla
function obtenerUltimoValor($conn, $tabla, $id_equipos) {
    // Consulta para obtener el último valor registrado
    $query = "SELECT * 
              FROM $tabla 
              WHERE id_equipos = ? 
              ORDER BY fecha_hora DESC 
              LIMIT 1";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_equipos);  // "i" para el tipo entero
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result) {
        die(json_encode(['error' => 'Error en la consulta: ' . $conn->error]));
    }

    // Si no hay resultados, devolver null
    if ($result->num_rows === 0) {
        return null;
    }

    return $result->fetch_assoc();
}

// Leer entrada de id_equipos desde la URL
$id_equipos = isset($_GET['id_equipos']) ? (int)$_GET['id_equipos'] : null;
if (!$id_equipos) {
    die(json_encode(['error' => 'Debe proporcionar un id_equipos válido.']));
}

// Tablas permitidas
$tablas = ['variable1', 'variable2', 'variable3', 'variable4', 'variable5', 'variable6', 'variable7', 'variable8'];

// Consultar el último valor registrado para cada tabla
$resultados = [];
foreach ($tablas as $tabla) {
    $ultimoRegistro = obtenerUltimoValor($conn, $tabla, $id_equipos);
    $resultados[$tabla] = $ultimoRegistro ? $ultimoRegistro : 'No se encontraron registros';
}

// Respuesta de la API
header('Content-Type: application/json');
echo json_encode([
    'mensaje' => 'Últimos valores consultados con éxito',
    'datos' => $resultados,
]);
?>
