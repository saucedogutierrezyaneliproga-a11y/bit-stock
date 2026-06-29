<?php
// procesar_pago.php
session_start();
require_once 'inc/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

$id_usr = $_SESSION['id'];

try {
    // 1. Iniciamos una transaccion SQL en Neon.tech para asegurar consistencia de datos
    // Si algo falla a la mitad, ningun dato se corrompe en la nube
    $pdo->beginTransaction();

    // 2. Buscamos los productos actualmente en el carrito de este usuario
    $sql_items = "SELECT cd.id_producto, cd.cantidad, p.nombre, p.stock, p.precio, p.en_oferta, p.precio_oferta 
                  FROM carrito_detail cd
                  INNER JOIN carrito c ON cd.id_carrito = c.id_carrito
                  INNER JOIN productos p ON cd.id_producto = p.id_producto
                  WHERE c.id_usuario = :u";
    
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute([':u' => $id_usr]);
    $items = $stmt_items->fetchAll();

    if (count($items) === 0) {
        throw new Exception("Tu carrito se encuentra vacío.");
    }

    $total_cobrado = 0;
    $envio = 15.00;

    // 3. Procesamos y verificamos producto por producto
    foreach ($items as $item) {
        // Validacion de Stock Critico antes de descontar
        if ($item['stock'] < $item['cantidad']) {
            throw new Exception("Lo sentimos, no hay suficiente inventario disponible de: " . $item['nombre']);
        }

        // Calculo de la cantidad monetaria real procesada
        $precio_final = $item['en_oferta'] ? $item['precio_oferta'] : $item['precio'];
        $total_cobrado += ($precio_final * $item['cantidad']);

        // MODIFICACIÓN EN BD: Reducimos las unidades vendidas de la tabla productos
        $sql_update_stock = "UPDATE productos SET stock = stock - :cant WHERE id_producto = :id";
        $stmt_stock = $pdo->prepare($sql_update_stock);
        $stmt_stock->execute([
            ':cant' => $item['cantidad'],
            ':id'   => $item['id_producto']
        ]);
    }

    $total_final_cobrado = $total_cobrado + $envio;

    // 4. BAJA EN BD: Limpiamos por completo el detalle del carrito del usuario
    $sql_clear = "DELETE FROM carrito_detail WHERE id_carrito = (SELECT id_carrito FROM carrito WHERE id_usuario = :u)";
    $stmt_clear = $pdo->prepare($sql_clear);
    $stmt_clear->execute([':u' => $id_usr]);

    // 5. Confirmamos todos los cambios en Neon.tech si no hubo contratiempos
    $pdo->commit();

    // Mensaje de éxito informando de forma transparente la cantidad cobrada
    echo "<script>
            alert('¡Pago aprobado con éxito! Se cargó un total de $" . number_format($total_final_cobrado, 2) . " MXN a tu tarjeta. Tu hardware está en camino.');
            window.location='index.php';
          </script>";

} catch (Exception $e) {
    // Si saltó algun error (como falta de stock), revertimos cualquier cambio en la base de datos
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<script>
            alert('Error en la transacción: " . $e->getMessage() . "');
            window.history.back();
          </script>";
}
?>