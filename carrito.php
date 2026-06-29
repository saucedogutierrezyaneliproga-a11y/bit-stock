<?php
// carrito.php
require_once 'inc/conexion.php'; //conexion con neondb

// NOTA: Para evitar duplicación o conflictos de session_start, 
// dejamos que inc/encabezado.php maneje las sesiones e inicializaciones.
require_once 'inc/encabezado.php'; 

$items = [];

// CASO 1: El usuario inició sesión (Consulta SQL original con INNER JOIN)
if (isset($_SESSION['id'])) {
    $id_usr = $_SESSION['id'];

    $sql = "SELECT p.id_producto, p.nombre, p.precio, p.en_oferta, p.precio_oferta, p.imagen, cd.cantidad 
            FROM carrito_detalle cd
            INNER JOIN carrito c ON cd.id_carrito = c.id_carrito
            INNER JOIN productos p ON cd.id_producto = p.id_producto
            WHERE c.id_usuario = :u";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':u' => $id_usr]);
    $items = $stmt->fetchAll();
} 
// CASO 2: El usuario es un invitado (Leemos los datos de la cookie temporal)
else if (isset($_COOKIE['bitstock_cart'])) {
    $carrito_temporal = json_decode($_COOKIE['bitstock_cart'], true);
    
    if (is_array($carrito_temporal) && count($carrito_temporal) > 0) {
        // Extraemos las llaves (que son los IDs de los productos)
        $ids = array_keys($carrito_temporal);
        
        // Creamos un string de marcadores para PDO: ?,?,? según la cantidad de IDs
        $in_query = implode(',', array_fill(0, count($ids), '?'));
        
        // Buscamos directamente la información de esos productos en la Base de Datos
        $sql = "SELECT id_producto, nombre, precio, en_oferta, precio_oferta, imagen 
                FROM productos 
                WHERE id_producto IN ($in_query)";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute($ids);
        $productos_db = $stmt->fetchAll();
        
        // Mapeamos las cantidades guardadas en la cookie al arreglo final de items
        foreach ($productos_db as $prod) {
            $prod['cantidad'] = $carrito_temporal[$prod['id_producto']];
            $items[] = $prod;
        }
    }
}

// Inicialización de variables para el cálculo del resumen de compra
$subtotal = 0.00;

// 🔥 CORRECCIÓN DE LÓGICA: El envío vale 15.00 solo si hay productos en el carrito, si no, vale 0.00
$envio = (is_array($items) && count($items) > 0) ? 15.00 : 0.00;
?>

<main class="cnt-carrito" style="max-width: 1200px; margin: 30px auto; padding: 0 20px;">
    <h2>Tu Carrito de Compras</h2>
    
    <?php if (isset($_SESSION['error_compra'])): ?>
        <div style="background-color: #3a1616; border: 1px solid #ff3838; color: #ff8888; padding: 15px; border-radius: 6px; margin-bottom: 20px; font-size: 14px;">
            ⚠️ <?php echo $_SESSION['error_compra']; unset($_SESSION['error_compra']); ?>
        </div>
    <?php endif; ?>
    
    <div class="carrito-layout-global">
        
        <div class="lista-carrito">
            <?php if (is_array($items) && count($items) > 0): ?>
                <?php foreach ($items as $item): ?>
                    
                    <div class="item-car">
                        <div class="item-info">
                            <div class="item-img-wrap">
                                <img src="<?php echo htmlspecialchars($item['imagen']); ?>" alt="<?php echo htmlspecialchars($item['nombre']); ?>">
                            </div>
                            <div class="item-det">
                                <h4><?php echo htmlspecialchars($item['nombre']); ?></h4>
                                <?php 
                                    $precio_final = ($item['en_oferta'] && $item['precio_oferta'] > 0) ? $item['precio_oferta'] : $item['precio']; 
                                    // Sumamos al subtotal de forma dinámica en cada ciclo
                                    $subtotal += ($precio_final * $item['cantidad']);
                                ?>
                                <p class="item-precio">$<?php echo number_format($precio_final, 2); ?> MXN</p>
                            </div>
                        </div>
                        
                        <div class="item-ctrls">
                            <div class="control-cantidad">
                                <button class="btn-cant" 
                                        onclick="actualizarCantidad(<?php echo $item['id_producto']; ?>, 'sub')" 
                                        <?php echo ($item['cantidad'] <= 1) ? 'disabled style="opacity: 0.4; cursor: not-allowed;"' : ''; ?>>
                                    -
                                </button>
                                
                                <span class="num-cant" style="font-weight: bold; margin: 0 10px; color: white;">
                                    <?php echo $item['cantidad']; ?>
                                </span>
                                
                                <button class="btn-cant" 
                                        onclick="actualizarCantidad(<?php echo $item['id_producto']; ?>, 'add')">
                                    +
                                </button>
                            </div>
                            
                            <button class="btn-elim" onclick="eliminarDelCarrito(<?php echo $item['id_producto']; ?>)">
                                Eliminar
                            </button>
                        </div>
                    </div>
                    
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: #8888aa; text-align: center; padding-top: 20px;">Tu carrito está vacío.</p>
            <?php endif; ?>
        </div>

        <div class="resumen-compra-tarjeta">
            <h3>Resumen de Compra</h3>
            <div class="resumen-linea">
                <span>Subtotal:</span>
                <span style="color: white;">$<?php echo number_format($subtotal, 2); ?> MXN</span>
            </div>
            
            <div class="resumen-linea">
                <span>Costo de envío:</span>
                <?php if (is_array($items) && count($items) > 0): ?>
                    <strong style="color: #00ff88;">$<?php echo number_format($envio, 2); ?> MXN</strong>
                <?php else: ?>
                    <span style="color: #8888aa;">$0.00 MXN</span>
                <?php endif; ?>
            </div>
            
            <hr style="border-color: #22223b; margin: 15px 0;">
            
            <div class="resumen-linea total">
                <span>Total:</span>
                <?php if (is_array($items) && count($items) > 0): ?>
                    <span class="precio-total" style="color: #00ff88; font-size: 18px; font-weight: bold;">
                        $<?php echo number_format($subtotal + $envio, 2); ?> MXN
                    </span>
                <?php else: ?>
                    <span style="color: #8888aa; font-size: 18px; font-weight: bold;">
                        $0.00 MXN
                    </span>
                <?php endif; ?>
            </div>
            
            <?php if (isset($_SESSION['id'])): ?>
                <button class="btn-pagar" onclick="window.location='verificar_direccion.php'" <?php echo (count($items) == 0) ? 'disabled style="opacity:0.3; cursor:not-allowed; background-color: #333344;"' : ''; ?>>
                    Proceder al Pago
                </button>
            <?php else: ?>
                <button class="btn-pagar" onclick="alert('Para proceder con el pago y capturar tus datos de envío, por favor inicia sesión o crea una cuenta. ¡Tus componentes se guardarán en el carrito!'); window.location='login.php'" <?php echo (count($items) == 0) ? 'disabled style="opacity:0.3; cursor:not-allowed; background-color: #333344;"' : ''; ?>>
                    Proceder al Pago
                </button>
            <?php endif; ?>
            
        </div>

    </div>
</main>

<script src="js/carrito.js"></script>

<?php require_once 'inc/pie.php'; ?>