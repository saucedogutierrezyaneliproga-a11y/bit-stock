<?php
// api_eliminar_carrito.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'inc/conexion.php';
header('Content-Type: application/json');

// Si no hay sesión activa o no mandaron datos válidos
if (!isset($_SESSION['id']) || !isset($_POST['id_prd'])) {
    echo json_encode(['status' => 'error', 'msg' => 'Petición no válida']);
    exit;
}

$id_usr = $_SESSION['id'];
$id_prd = (int)$_POST['id_prd'];

try {
    // 1. Obtener el carrito del usuario logueado
    $sql_car = "SELECT id_carrito FROM carrito WHERE id_usuario = :u";
    $stmt_car = $pdo->prepare($sql_car);
    $stmt_car->execute([':u' => $id_usr]);
    $carrito = $stmt_car->fetch();

    if ($carrito) {
        $id_car = $carrito['id_carrito'];

        // 2. Eliminar el producto específico de la tabla detalle
        $sql_del = "DELETE FROM carrito_detalle WHERE id_carrito = :c AND id_producto = :p";
        $stmt_del = $pdo->prepare($sql_del);
        $stmt_del->execute([
            ':c' => $id_car,
            ':p' => $id_prd
        ]);

        echo json_encode(['status' => 'success', 'msg' => 'Producto eliminado']);
        exit;
    }

    echo json_encode(['status' => 'error', 'msg' => 'Carrito no encontrado']);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'msg' => 'Error: ' . $e->getMessage()]);
}
?>