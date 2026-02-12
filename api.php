<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");


require_once __DIR__ . "/conexion.php";

$method = $_SERVER['REQUEST_METHOD'];

//METODO GET 

if ($method === "GET") {

    $sql = "SELECT * FROM product ORDER BY date_create DESC"; //Consulta en la bd
    $result = $mysqli->query($sql);

    if (!$result) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Error fetching products"
        ]);
        exit;
    }

    $products = [];//Almacena los productos

    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }

    echo json_encode([
        "success" => true,
        "data" => $products
    ]);
    exit;
}

//METODO POST

if ($method === "POST") {

    $input = json_decode(file_get_contents("php://input"), true);

    $name  = $input['name'] ?? '';
    $price = $input['price'] ?? '';

    if (empty($name) || empty($price)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Se requiere nombre y precio"
        ]);
        exit;
    }

    if (!is_numeric($price)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "El precio debe ser numerico"
        ]);
        exit;
    }

    $price = (float)$price;

    $stmt = $mysqli->prepare("INSERT INTO product (name, price) VALUES (?, ?)"); //Mandamos los datos a insertar

    if (!$stmt) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Prepare statement failed"
        ]);
        exit;
    }

    $stmt->bind_param("sd", $name, $price);

    // Ejecutar
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Fallo la Ejecucion"
        ]);
        $stmt->close();
        exit;
    }

    // Obtener ID insertado para mostrarlo en el json
    $insertId = $stmt->insert_id;

    $stmt->close();

    echo json_encode([
        "success" => true,
        "message" => "Producto insertado correctamente",
        "id" => $insertId
    ]);
}
