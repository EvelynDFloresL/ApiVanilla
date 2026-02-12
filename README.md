# API REST Vanilla

### 1️⃣ Clonar o copiar el proyecto en:
C:\xampp\htdocs\apirestvanilla

### 2️⃣ Crear base de datos

En phpMyAdmin crear una base llamada: store

### 3️⃣ Crear tabla

```sql
CREATE TABLE product (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    date_create TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

### Configuracion a la BD conexion.php

Cambiar si es diferente o agregar contraseña si la tiene.

<?php

$host = "localhost";
$user = "root";
$pass = "";
$db   = "store";

$mysqli = new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Fallo la conexion a la BD"
    ]);
    exit;
}

### Codigo Fuente api.php

<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");


require_once __DIR__ . "/conexion.php";

$method = $_SERVER['REQUEST_METHOD'];

if ($method === "GET") {

    $sql = "SELECT * FROM product ORDER BY date_create DESC";
    $result = $mysqli->query($sql);

    if (!$result) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Error fetching products"
        ]);
        exit;
    }

    $products = [];

    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }

    echo json_encode([
        "success" => true,
        "data" => $products
    ]);
    exit;
}

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

    $stmt = $mysqli->prepare("INSERT INTO product (name, price) VALUES (?, ?)");

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

    // Obtener ID insertado
    $insertId = $stmt->insert_id;

    $stmt->close();

    echo json_encode([
        "success" => true,
        "message" => "Producto insertado correctamente",
        "id" => $insertId
    ]);
}

### CODIGO FUENTE index.html

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio</title>
</head>

<body>
    <h1>PRODUCTOS</h1>
    <form id="productForm" method="POST">
        <input type="text" id="name" placeholder="Nombre del producto" required> <br> <br>
        <input type="number" id="price" step="0.01" placeholder="Precio del producto" required> <br> <br>

        <button type="submit">Agregar</button>
    </form>

    <ul id="productLista"></ul>

    <script>
        const form = document.getElementById("productForm");
        const apiURL = "api.php";

        document.addEventListener("DOMContentLoaded", cargarProductos);

        async function cargarProductos() {

            try {

                const response = await fetch(apiURL);

                if (!response.ok) {
                    throw new Error("Error HTTP: " + response.status);
                }

                const data = await response.json();

                if (!data.success) {
                    console.error(data.message);
                    return;
                }

                mostrarProductos(data.data);

            } catch (error) {
                console.error("Error al cargar productos:", error);
            }
        }

        function mostrarProductos(products) {

            const lista = document.getElementById("productLista");

            lista.innerHTML = ""; // limpiar antes de renderizar

            products.forEach(product => {

                const li = document.createElement("li");

                li.textContent =
                    product.name + " - $" + product.price + " - " + product.date_create;

                lista.appendChild(li);
            });
        }


        form.addEventListener("submit", function (event) {
            event.preventDefault();
            insertarProducto();
        });

        async function insertarProducto() {

            const name = document.getElementById("name").value.trim();
            const price = document.getElementById("price").value.trim();

            if (name === "" || price === "") {
                alert("Todos los campos son obligatorios");
                return;
            }

            try {

                const response = await fetch(apiURL, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        name: name,
                        price: price
                    })
                });

                if (!response.ok) {
                    throw new Error("Error HTTP: " + response.status);
                }

                const data = await response.json();

                if (data.success) {
                    form.reset();
                    cargarProductos();
                } else {
                    document.getElementById("productLista").innerHTML = data.message;
                }

            } catch (error) {
                console.error("Error:", error);
                document.getElementById("productLista").innerHTML = "Error al conectar con el servidor";
            }
        }

    </script>

</body>

</html>