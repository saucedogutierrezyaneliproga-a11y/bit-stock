<?php
// api_cantidad_carrito.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'inc/conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['id']) || !isset($_POST['id_prd']) || !isset($_POST['operacion'])) {
    echo json_encode(['status' => 'error', 'msg' => 'Datos insuficientes']);
    exit;
}

$id_usr = $_SESSION['id'];
$id_prd = (int)$_POST['id_prd'];
$operacion = $_POST['operacion'];

try {
    // 1. Buscar el carrito e inventario del producto
    $sql_prod = "SELECT p.stock, cd.cantidad, cd.id_carrito 
                 FROM productos p
                 INNER JOIN carrito_detalle cd ON p.id_producto = cd.id_producto
                 INNER JOIN carrito c ON cd.id_carrito = c.id_carrito
                 WHERE c.id_usuario = :u AND p.id_producto = :p";
                 
    $stmt_prod = $pdo->prepare($sql_prod);
    $stmt_prod->execute([':u' => $id_usr, ':p' => $id_prd]);
    $data = $stmt_prod->fetch();

    if (!$data) {
        echo json_encode(['status' => 'error', 'msg' => 'Registro no encontrado']);
        exit;
    }

    $stock_max = (int)$data['stock'];
    $cantidad_actual = (int)$data['cantidad'];
    $id_car = $data['id_carrito'];

    if ($operacion === 'add') {
        // 🔥 VALIDACIÓN: Si ya llegó al tope de stock, no incrementamos nada
        if ($cantidad_actual >= $stock_max) {
            echo json_encode(['status' => 'error', 'msg' => 'No puedes agregar más unidades. Inventario máximo alcanzado.']);
            exit;
        }
        $sql_update = "UPDATE carrito_detalle SET cantidad = cantidad + 1 WHERE id_carrito = :c AND id_producto = :p";
    } else if ($operacion === 'sub') {
        $sql_update = "UPDATE carrito_detalle SET cantidad = cantidad - 1 WHERE id_carrito = :c AND id_producto = :p AND cantidad > 1";
    }

    $pdo->prepare($sql_update)->execute([':c' => $id_car, ':p' => $id_prd]);
    echo json_encode(['status' => 'success', 'msg' => 'Cantidad modificada']);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'msg' => 'Error: ' . $e->getMessage()]);
}
?>