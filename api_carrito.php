<?php
// api_carrito.php o tu controlador de inserción
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'inc/conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['id']) || !isset($_POST['id_prd'])) {
    echo json_encode(['status' => 'error', 'msg' => 'Petición no válida']);
    exit;
}

$id_usr = $_SESSION['id'];
$id_prd = (int)$_POST['id_prd'];
$cantidad_a_añadir = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 1;

try {
    // 1. Consultar el stock disponible real del producto
    $sql_stock = "SELECT stock, nombre FROM productos WHERE id_producto = :id";
    $stmt_stock = $pdo->prepare($sql_stock);
    $stmt_stock->execute([':id' => $id_prd]);
    $producto = $stmt_stock->fetch();

    if (!$producto) {
        echo json_encode(['status' => 'error', 'msg' => 'El producto no existe.']);
        exit;
    }

    $stock_disponible = $producto['stock'];
    $nombre_prod = $producto['nombre'];

    // 2. Obtener el id_carrito del usuario
    $sql_car = "SELECT id_carrito FROM carrito WHERE id_usuario = :u";
    $stmt_car = $pdo->prepare($sql_car);
    $stmt_car->execute([':u' => $id_usr]);
    $carrito = $stmt_car->fetch();

    if (!$carrito) {
        // Si no tiene carrito, se crea uno
        $sql_new_car = "INSERT INTO carrito (id_usuario) VALUES (:u) RETURNING id_carrito";
        $stmt_new = $pdo->prepare($sql_new_car);
        $stmt_new->execute([':u' => $id_usr]);
        $id_car = $stmt_new->fetchColumn();
    } else {
        $id_car = $carrito['id_carrito'];
    }

    // 3. Consultar si el producto ya está en el carrito para saber cuántas unidades lleva acumuladas
    $sql_actual = "SELECT cantidad FROM carrito_detalle WHERE id_carrito = :c AND id_producto = :p";
    $stmt_actual = $pdo->prepare($sql_actual);
    $stmt_actual->execute([':c' => $id_car, ':p' => $id_prd]);
    $item_en_carrito = $stmt_actual->fetch();

    $cantidad_actual_carrito = $item_en_carrito ? (int)$item_en_carrito['cantidad'] : 0;
    $cantidad_total_solicitada = $cantidad_actual_carrito + $cantidad_a_añadir;

    // 🔥 LA REGLA DE ORO: Validar si lo que pide supera el inventario de la base de datos
    if ($cantidad_total_solicitada > $stock_disponible) {
        echo json_encode([
            'status' => 'error', 
            'msg' => "Límite alcanzado. Solo quedan {$stock_disponible} unidades disponibles de este artículo y ya tienes {$cantidad_actual_carrito} en tu carrito."
        ]);
        exit;
    }

    // 4. Si pasa la validación, procedemos a guardar o actualizar
    if ($item_en_carrito) {
        $sql_action = "UPDATE carrito_detalle SET cantidad = :cant WHERE id_carrito = :c AND id_producto = :p";
        $params_action = [':cant' => $cantidad_total_solicitada, ':c' => $id_car, ':p' => $id_prd];
    } else {
        $sql_action = "INSERT INTO carrito_detalle (id_carrito, id_producto, cantidad) VALUES (:c, :p, :cant)";
        $params_action = [':c' => $id_car, ':p' => $id_prd, ':cant' => $cantidad_a_añadir];
    }

    $pdo->prepare($sql_action)->execute($params_action);
    echo json_encode(['status' => 'success', 'msg' => 'Producto agregado al carrito con éxito.']);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'msg' => 'Error: ' . $e->getMessage()]);
}
?>