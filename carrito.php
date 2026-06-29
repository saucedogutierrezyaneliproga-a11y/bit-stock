<?php
// carrito.php
require_once 'inc/conexion.php';
require_once 'inc/encabezado.php';

// Redirección de seguridad si intentan entrar al carrito sin loguearse
if (!isset($_SESSION['id'])) {
    echo "<script>alert('Por favor, inicia sesión para ver tu carrito'); window.location='login.php';</script>";
    exit;
}

$id_usr = $_SESSION['id'];

// Consulta avanzada con INNER JOIN para traer los datos del producto que estan en el carrito del usuario
$sql = "SELECT p.id_producto, p.nombre, p.precio, p.en_oferta, p.precio_oferta, p.imagen, cd.cantidad 
        FROM carrito_detalle cd
        INNER JOIN carrito c ON cd.id_carrito = c.id_carrito
        INNER JOIN productos p ON cd.id_producto = p.id_producto
        WHERE c.id_usuario = :u";

$stmt = $pdo->prepare($sql);
$stmt->execute([':u' => $id_usr]);
$items = $stmt->fetchAll();

// Inicialización de variables para el cálculo del resumen de compra
$subtotal = 0.00;
$envio = 15.00; // Costo estático indicado en el mockup de la página 5
?>

<main class="cnt-car">
    
    <section class="car-lista">
        <h2 class="tit-seccion" style="margin-bottom: 20px;">Tu Carrito de Compras</h2>
        
        <?php if (count($items) > 0): ?>
            <?php foreach ($items as $item): ?>
                <?php 
                    // Determinamos si el precio a cobrar es el normal o el precio de oferta
                    $precio_final = $item['en_oferta'] ? $item['precio_oferta'] : $item['precio'];
                    $total_item = $precio_final * $item['cantidad'];
                    $subtotal += $total_item; // Acumulamos en el subtotal global
                ?>
                
                <div class="item-car">
                    <div class="item-info">
                        <img src="<?php echo htmlspecialchars($item['imagen_url']); ?>" alt="<?php echo htmlspecialchars($item['nombre']); ?>">
                        
                        <div class="item-det">
                            <h4><?php echo htmlspecialchars($item['nombre']); ?></h4>
                            <p>$<?php echo number_format($precio_final, 2); ?> MXN</p>
                        </div>
                    </div>

                    <div class="item-ctrls">
                        <button onclick="actualizarCantidad(<?php echo $item['id_producto']; ?>, <?php echo $item['cantidad'] - 1; ?>)">-</button>
                        <span><?php echo $item['cantidad']; ?></span>
                        <button onclick="actualizarCantidad(<?php echo $item['id_producto']; ?>, <?php echo $item['cantidad'] + 1; ?>)">+</button>
                    </div>

                    <button class="btn-elim" onclick="eliminarDelCarrito(<?php echo $item['id_producto']; ?>)">
                        Eliminar
                    </button>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color: #8888aa; margin-top: 20px;">Tu carrito está vacío. ¡Ve al catálogo a buscar componentes!</p>
        <?php endif; ?>
    </section>

    <section class="car-resumen">
        <h3>Resumen de compra</h3>
        
        <div class="res-fila">
            <span>Subtotal productos:</span>
            <span>$<?php echo number_format($subtotal, 2); ?></span>
        </div>
        
        <div class="res-fila">
            <span>Envío a domicilio:</span>
            <span>$<?php echo number_format($subtotal > 0 ? $envio : 0, 2); ?></span>
        </div>

        <div class="res-total">
            <span>Total:</span>
            <span>$<?php echo number_format($subtotal > 0 ? ($subtotal + $envio) : 0, 2); ?> MXN</span>
        </div>

        <?php if ($subtotal > 0): ?>
            <button class="btn-m" onclick="window.location='pago.php'">Continuar compra</button>
        <?php else: ?>
            <button class="btn-m" style="background: #444; cursor: not-allowed;" disabled>Continuar compra</button>
        <?php endif; ?>
    </section>

</main>

<script src="js/carrito.js"></script>

<?php require_once 'inc/pie.php'; ?>