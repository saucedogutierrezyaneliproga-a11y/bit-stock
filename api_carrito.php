<?php
// api_carrito.php
session_start();
require_once 'conexion.php';

// configuramos la respuesta para que sea interpretada como JSON por JavaScript
header('Content-Type: application/json');

// si el usuario no ha iniciado sesion, no puede usar el carrito
if (!isset($_SESSION['id'])) {
    echo json_code(['status' => 'error', 'msg' => 'Debes iniciar sesión para añadir productos']);
    exit;
}

$id_usr = $_SESSION['id'];
$acc = isset($_POST['acc']) ? $_POST['acc'] : '';

try {
    // A. Asegurar que el usuario tenga un carrito activo asignado en la BD
    $sql_c = "INSERT INTO carrito (id_usuario) VALUES (:u) ON CONFLICT (id_usuario) DO NOTHING";
    $stmt_c = $pdo->prepare($sql_c);
    $stmt_c->execute([':u' => $id_usr]);

    // obtenemos el id_carrito real de este usuario
    $sql_id = "SELECT id_carrito FROM carrito WHERE id_usuario = :u";
    $stmt_id = $pdo->prepare($sql_id);
    $stmt_id->execute([':u' => $id_usr]);
    $id_car = $stmt_id->fetch()['id_carrito'];

    // === ACCION: AGREGAR O ACTUALIZAR PRODUCTO ===
    if ($acc === 'add' || $acc === 'update') {
        $id_prd = (int)$_POST['id_prd'];
        $cant = (int)$_POST['cant'];

        if ($cant <= 0) { exit; }

        if ($acc === 'add') {
            // si ya existe, suma la cantidad; si no, la inserta (Manejo de altas en relacion N:N)
            $sql = "INSERT INTO carrito_detail (id_carrito, id_producto, cantidad) 
                    VALUES (:c, :p, :q)
                    ON CONFLICT (id_carrito, id_producto) 
                    DO UPDATE SET cantidad = carrito_detail.cantidad + :q";
        } else {
            // sobreescribe la cantidad exacta (Modificaciones funcionando en BD)
            $sql = "UPDATE carrito_detail SET cantidad = :q WHERE id_carrito = :c AND id_producto = :p";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':c' => $id_car, ':p' => $id_prd, ':q' => $cant]);
        echo json_encode(['status' => 'success', 'msg' => 'Carrito actualizado']);
    }

    // === ACCION: ELIMINAR PRODUCTO (Bajas funcionando en BD) ===
    else if ($acc === 'del') {
        $id_prd = (int)$_POST['id_prd'];

        $sql = "DELETE FROM carrito_detail WHERE id_carrito = :c AND id_producto = :p";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':c' => $id_car, ':p' => $id_prd]);
        echo json_encode(['status' => 'success', 'msg' => 'Producto eliminado']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
?>