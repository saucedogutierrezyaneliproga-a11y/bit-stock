<?php
// procesar_pago.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'inc/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

$id_usr = $_SESSION['id'];

try {
    // 1. Iniciamos una transacción SQL en Neon.tech para asegurar consistencia
    $pdo->beginTransaction();

    // 2. Buscamos los productos actualmente en el carrito de este usuario
    $sql_items = "SELECT cd.id_producto, cd.cantidad, p.nombre, p.stock, p.precio, p.en_oferta, p.precio_oferta, p.imagen 
                  FROM carrito_detalle cd
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
    $resumen_productos = []; // Guardará las copias para la pantalla de éxito

    // 3. Procesamos y verificamos producto por producto
    foreach ($items as $item) {
        // Validación de Stock Crítico antes de descontar
        if ($item['stock'] < $item['cantidad']) {
            throw new Exception("Lo sentimos, no hay suficiente inventario disponible de: " . $item['nombre']);
        }

        // Cálculo de la cantidad monetaria real procesada
        $precio_final = $item['en_oferta'] ? $item['precio_oferta'] : $item['precio'];
        $subtotal_item = $precio_final * $item['cantidad'];
        $total_cobrado += $subtotal_item;

        // Guardamos los datos necesarios para el recibo visual antes de borrar el carrito
        $resumen_productos[] = [
            'nombre'   => $item['nombre'],
            'imagen'   => $item['imagen'],
            'cantidad' => $item['cantidad'],
            'precio'   => $precio_final,
            'subtotal' => $subtotal_item
        ];

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
    $sql_clear = "DELETE FROM carrito_detalle WHERE id_carrito IN (SELECT id_carrito FROM carrito WHERE id_usuario = :u)";
    $stmt_clear = $pdo->prepare($sql_clear);
    $stmt_clear->execute([':u' => $id_usr]);

    // 5. Confirmamos todos los cambios en Neon.tech si no hubo contratiempos
    $pdo->commit();

    // 6. Almacenamos temporalmente los datos en la SESIÓN para consumirlos en la siguiente pantalla
    $_SESSION['ultima_compra'] = [
        'productos' => $resumen_productos,
        'subtotal'  => $total_cobrado,
        'envio'     => $envio,
        'total'     => $total_final_cobrado,
        'ticket'    => strtoupper(uniqid('BIT-')) // Genera un ID de orden único simulado
    ];

    // Redirigimos directamente a la nueva interfaz de éxito
    header("Location: compra_exitosa.php");
    exit;

} catch (Exception $e) {
    // Si saltó algún error (como falta de stock), revertimos cualquier cambio en la base de datos
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Guardamos el error en la sesión para mostrarlo de forma elegante o usar un alert controlado
    $_SESSION['error_compra'] = $e->getMessage();
    header("Location: carrito.php?error=true");
    exit;
}
?>