<?php
require '../conexion.php'; // Se asume que tu archivo de conexión configura $conn como mysqli

// Validar conexión
if (!$conn) {
    die(json_encode(['error' => 'No se pudo establecer la conexión a la base de datos']));
}

// Función para obtener datos según la opción seleccionada
function obtenerDatos($conn, $tabla, $intervalo, $frecuencia, $id_equipos, $consulta) {
    $query = "";

    // Si la consulta es 'now', obtener el último registro
    if ($frecuencia === 'minuto') {
        $query = "SELECT 
                      CONCAT(DATE_FORMAT(fecha_hora, '%Y-%m-%d %H:'), LPAD(FLOOR(MINUTE(fecha_hora) / 5) * 5, 2, '0'), ':00') as tiempo, 
                      AVG(valor) as promedio 
                  FROM $tabla 
                  WHERE fecha_hora >= NOW() - INTERVAL $intervalo 
                    AND id_equipos = ? 
                  GROUP BY tiempo 
                  ORDER BY tiempo DESC";
    } elseif ($frecuencia === 'hora') {
        $query = "SELECT DATE_FORMAT(fecha_hora, '%Y-%m-%d %H:00:00') as tiempo, AVG(valor) as promedio 
                  FROM $tabla 
                  WHERE fecha_hora >= NOW() - INTERVAL $intervalo 
                    AND id_equipos = ? 
                  GROUP BY tiempo 
                  ORDER BY tiempo DESC";
    } elseif ($frecuencia === 'dia') {
        $query = "SELECT DATE_FORMAT(fecha_hora, '%Y-%m-%d 00:00:00') as tiempo, AVG(valor) as promedio 
                  FROM $tabla 
                  WHERE fecha_hora >= NOW() - INTERVAL $intervalo 
                    AND id_equipos = ? 
                  GROUP BY tiempo 
                  ORDER BY tiempo DESC";
    }

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_equipos);  // "i" para el tipo entero
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result) {
        die(json_encode(['error' => 'Error en la consulta: ' . $conn->error]));
    }

    $datos = [];
    while ($row = $result->fetch_assoc()) {
        $datos[] = $row;
    }

    return $datos;
}

// Rango de consulta
$rangoConsulta = [
    'hor' => ['intervalo' => '1 HOUR', 'frecuencia' => 'minuto'],
    'dia' => ['intervalo' => '1 DAY', 'frecuencia' => 'hora'],
    'sem' => ['intervalo' => '7 DAY', 'frecuencia' => 'dia'],
    'qui' => ['intervalo' => '15 DAY', 'frecuencia' => 'dia'],
    'mes' => ['intervalo' => '30 DAY', 'frecuencia' => 'dia'],

];

// Leer entrada de la consulta de datos
$consulta = $_GET['consulta_de_datos'] ?? 'hor';
if (!array_key_exists($consulta, $rangoConsulta)) {
    die(json_encode(['error' => 'Consulta no válida']));
}

// Leer entrada de id_equipos desde la URL
$id_equipos = isset($_GET['id_equipos']) ? (int)$_GET['id_equipos'] : null;
if (!$id_equipos) {
    die(json_encode(['error' => 'Debe proporcionar un id_equipos válido.']));
}

// Tablas permitidas
$tablas = ['variable1', 'variable2', 'variable3', 'variable4', 'variable5', 'variable6', 'variable7', 'variable8'];

// Consultar datos
$resultados = [];
foreach ($tablas as $tabla) {
    // Para la consulta 'now', pasamos el valor 'now' en lugar de frecuencia e intervalo
    $datos = obtenerDatos($conn, $tabla, $rangoConsulta[$consulta]['intervalo'], $rangoConsulta[$consulta]['frecuencia'], $id_equipos, $consulta);
    $resultados[$tabla] = $datos;
}

// Guardar resultados en un archivo JSON
$nombreArchivo = __DIR__ . "/bitacora.json";
file_put_contents($nombreArchivo, json_encode($resultados, JSON_PRETTY_PRINT));

// Respuesta de la API
header('Content-Type: application/json');
echo json_encode([
    'mensaje' => 'Datos consultados con éxito',
    'archivo' => $nombreArchivo,
    'datos' => $resultados,
]);
?>
