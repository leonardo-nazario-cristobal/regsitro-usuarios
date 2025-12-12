<?php
// =============================================================
// api.php — API REST para gestionar CLIENTES usando PDO + PostgreSQL
// Devuelve SIEMPRE respuestas en formato JSON.
// =============================================================
header('Content-Type: application/json; charset=utf-8');



// =============================================================
// 1. CONFIGURACIÓN DE LA BASE DE DATOS
// =============================================================
// Ajusta estos valores según tu servidor y base de datos

$host         = 'localhost';
$port         = '5432';
$bd           = 'clientes_bd';      // Nombre de la BD
$usuario      = 'postgres';
$contrasena   = 'admin123';
$nombre_tabla = 'clientes';         // Tabla donde se guardan los clientes



// =============================================================
// 2. CONEXIÓN A POSTGRESQL USANDO PDO
// =============================================================
try {
   // DSN: Dirección de la BD (host + puerto + nombre)
   $dsn = "pgsql:host=$host;port=$port;dbname=$bd";

   // Creamos la conexión PDO
   $conexion = new PDO($dsn, $usuario, $contrasena, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // Modo estricto de errores
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC          // Devuelve arreglos asociativos
   ]);

} catch (PDOException $e) {

   // Error de conexión → se envía JSON y se detiene la ejecución
   http_response_code(500);
   echo json_encode([
         'success' => false,
         'message' => 'Error al conectar con PostgreSQL: ' . $e->getMessage()
   ], JSON_UNESCAPED_UNICODE);
   exit;
}



// =============================================================
// 3. LECTURA DE MÉTODO HTTP Y PARÁMETROS
// =============================================================
// El front puede mandar:
// - GET (cargar clientes)
// - POST (guardar clientes)
// Y además usa parámetro action=load o action=save

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';



// =============================================================
// 4. ACCIÓN: LISTAR CLIENTES (GET o action=load)
// =============================================================
// Esta parte devuelve todos los registros de la tabla.
// Se ejecuta cuando la página carga.

if ($method === 'GET' || $action === 'load') {

   // Consulta SQL con los campos EXACTOS de tu tabla
   $sql = "SELECT 
               id,
               nombre,
               apellidopaterno,
               apellidomaterno,
               fechanacimiento,
               direccion,
               telefono,
               fecharegistro
         FROM $nombre_tabla
         ORDER BY id DESC";   // Los más nuevos primero

   try {
         $stmt     = $conexion->prepare($sql);
         $stmt->execute();
         $clientes = $stmt->fetchAll();  // Devuelve un array con todos los clientes

      echo json_encode([
            'success'  => true,
            'clientes' => $clientes
      ], JSON_UNESCAPED_UNICODE);

   } catch (Exception $e) {

      // Error en la consulta SQL
      http_response_code(500);
      echo json_encode([
            'success' => false,
            'message' => 'Error al listar clientes: ' . $e->getMessage()
      ], JSON_UNESCAPED_UNICODE);
   }

   exit; // Fin de esta acción
}



// =============================================================
// 5. ACCIÓN: GUARDAR CLIENTE (POST o action=save)
// =============================================================
// Recibe datos del formulario → valida → guarda → devuelve JSON

if ($method === 'POST' || $action === 'save') {

   // ---------------------------------------------------------
   // 5.1 LECTURA Y LIMPIEZA DE DATOS
   // trim() elimina espacios al inicio y al final
   // ---------------------------------------------------------
	$nombre          = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
   $apellidoPaterno = isset($_POST['apellidoPaterno']) ? trim($_POST['apellidoPaterno']) : '';
   $apellidoMaterno = isset($_POST['apellidoMaterno']) ? trim($_POST['apellidoMaterno']) : null;
   $fechaNacimiento = isset($_POST['fechaNacimiento']) ? trim($_POST['fechaNacimiento']) : null;
   $direccion       = isset($_POST['direccion']) ? trim($_POST['direccion']) : null;
   $telefono        = isset($_POST['telefono']) ? trim($_POST['telefono']) : null;

   // Si no se envía, usamos la fecha actual
   $fechaRegistro = isset($_POST['fechaRegistro']) 
                        ? trim($_POST['fechaRegistro']) 
                        : date('Y-m-d');



   // ---------------------------------------------------------
   // 5.2 VALIDACIÓN BÁSICA
   // ---------------------------------------------------------
   if (empty($nombre) || empty($apellidoPaterno) || strlen($nombre) < 3) {

      // Error 400 = solicitud incorrecta (Bad Request)
      http_response_code(400);
      echo json_encode([
            'success' => false,
            'message' => 'Todos los campos son obligatorios.'
      ], JSON_UNESCAPED_UNICODE);
      exit;
   }



   // ---------------------------------------------------------
   // 5.3 SENTENCIA SQL PREPARADA (INSERT)
   // ---------------------------------------------------------
   // Se usan marcadores (?) para evitar inyección SQL.
   $sql = "INSERT INTO $nombre_tabla 
            (nombre, apellidoPaterno, apellidoMaterno, fechaNacimiento, direccion, telefono, fechaRegistro)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

   try {
      $stmt = $conexion->prepare($sql);

      // Ejecutamos el INSERT pasando los valores en el mismo orden
      $ok = $stmt->execute([
            $nombre,
            $apellidoPaterno,
            $apellidoMaterno,
            $fechaNacimiento,
            $direccion,
            $telefono,
            $fechaRegistro
      ]);

      if ($ok) {
            // lastInsertId obtiene el ID generado por la secuencia
            // IMPORTANTE: La secuencia debe llamarse {tabla}_id_seq
            $id_insertado = $conexion->lastInsertId($nombre_tabla . '_id_seq');

         echo json_encode([
               'success' => true,
               'message' => 'Cliente registrado correctamente.',
               'id'      => $id_insertado
         ], JSON_UNESCAPED_UNICODE);

      } else {
            http_response_code(500);
            echo json_encode([
                  'success' => false,
                  'message' => 'Error: No se pudo guardar el cliente.'
            ], JSON_UNESCAPED_UNICODE);
      }

      $stmt->closeCursor();

   } catch (Exception $e) {

      // Error general del servidor (SQL, permisos, etc.)
      http_response_code(500);
      echo json_encode([
            'success' => false,
            'message' => 'Error al ejecutar el INSERT: ' . $e->getMessage()
      ], JSON_UNESCAPED_UNICODE);
   }

   exit; // Fin de esta acción
}



// =============================================================
// 6. MÉTODOS NO PERMITIDOS
// =============================================================
http_response_code(405); // 405 = Método no permitido
echo json_encode([
      'success' => false,
      'message' => 'Método o acción no permitida.'
], JSON_UNESCAPED_UNICODE);

?>