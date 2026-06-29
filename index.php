<?php
// index.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'inc/conexion.php';
require_once 'inc/encabezado.php';

// 1. Detectar qué filtro, búsqueda o categoría específica está activa desde la URL
$buscar = isset($_GET['txtBuscar']) ? trim($_GET['txtBuscar']) : '';
$categoria_url = isset($_GET['cat']) ? $_GET['cat'] : '';
$id_cat_filtrada = isset($_GET['id_cat']) ? (int)$_GET['id_cat'] : 0;

// CONDICIÓN CLAVE: Determinamos si debemos mostrar el Sidebar izquierdo
$mostrar_sidebar = (empty($buscar) && $categoria_url !== 'accesorios' && $categoria_url !== 'ofertas');

$params = [];

// 2. Consulta secundaria para traer las categorías de la BD para la barra lateral (EXCLUYENDO Accesorios id 5)
$sql_side = "SELECT * FROM categorias WHERE id_categoria <> 5 ORDER BY nombre ASC";
$stmt_side = $pdo->query($sql_side);
$categorias_sidebar = $stmt_side->fetchAll();


// 3. Construcción inteligente de la consulta SQL principal de productos
if (!empty($buscar)) {
    $sql = "SELECT * FROM productos WHERE nombre ILIKE :b AND stock > 0 ORDER BY id_producto ASC";
    $params = [':b' => "%$buscar%"];
} 
else if ($id_cat_filtrada > 0) {
    $sql = "SELECT * FROM productos WHERE id_categoria = :id_cat AND stock > 0 ORDER BY id_producto ASC";
    $params = [':id_cat' => $id_cat_filtrada];
}
else if ($categoria_url === 'accesorios') {
    $sql = "SELECT * FROM productos WHERE id_categoria = 5 AND stock > 0 ORDER BY id_producto ASC";
} 
else if ($categoria_url === 'ofertas') {
    $sql = "SELECT * FROM productos WHERE en_oferta = true AND stock > 0 ORDER BY id_producto ASC";
} 
else {
    $sql = "SELECT * FROM productos WHERE id_categoria <> 5 AND stock > 0 ORDER BY id_producto ASC";
}

// 4. Ejecutar la consulta de productos en Neon.tech
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll();

// === MAREO DE CONTROL: OBTENER LO QUE YA ESTÁ EN EL CARRITO PARA JAVASCRIPT ===
$cantidades_en_carrito = [];

if (isset($_SESSION['id'])) {
    // Si está logueado, traemos las piezas que ya metió al carrito_detalle
    $sql_cart_check = "SELECT id_producto, cantidad FROM carrito_detalle WHERE id_carrito = (SELECT id_carrito FROM carrito WHERE id_usuario = :u)";
    $stmt_cc = $pdo->prepare($sql_cart_check);
    $stmt_cc->execute([':u' => $_SESSION['id']]);
    while ($row = $stmt_cc->fetch()) {
        $cantidades_en_carrito[(int)$row['id_producto']] = (int)$row['cantidad'];
    }
} else if (isset($_COOKIE['bitstock_cart'])) {
    // Si es invitado, leemos directamente su cookie temporal
    $cart_dec = json_decode($_COOKIE['bitstock_cart'], true);
    if (is_array($cart_dec)) {
        foreach ($cart_dec as $id_p_cookie => $cant_cookie) {
            $cantidades_en_carrito[(int)$id_p_cookie] = (int)$cant_cookie;
        }
    }
}
?>

<main class="cnt-catalogo-layout" style="max-width: 1200px; margin: 30px auto; padding: 0 20px; display: flex; gap: 30px;">
    
    <?php if ($mostrar_sidebar): ?>
        <aside class="sidebar-filtros" style="flex: 0 0 220px; background-color: #161625; border: 1px solid #333355; border-radius: 8px; padding: 20px; height: fit-content;">
            <h3 style="font-size: 16px; margin-bottom: 15px; color: #a29bfe; border-bottom: 1px solid #333355; padding-bottom: 8px;">Categorías</h3>
            <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 12px;">
                
                <li>
                    <a href="index.php" style="color: <?php echo ($id_cat_filtrada === 0) ? '#00ff88; font-weight: bold;' : '#ffffff;'; ?> text-decoration: none; font-size: 14px;">
                        • Todos los componentes
                    </a>
                </li>

                <?php foreach ($categorias_sidebar as $cat): ?>
                    <li>
                        <a href="index.php?id_cat=<?php echo $cat['id_categoria']; ?>" style="color: <?php echo ($id_cat_filtrada === (int)$cat['id_categoria']) ? '#00ff88; font-weight: bold;' : '#ffffff;'; ?> text-decoration: none; font-size: 14px;">
                            • <?php echo htmlspecialchars($cat['nombre']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </aside>
    <?php endif; ?>

    <section style="flex: 1;">
        <h2 class="tit-seccion" style="margin-bottom: 25px; font-size: 24px;">
            <?php 
                if (!empty($buscar)) echo "Resultados para: '" . htmlspecialchars($buscar) . "'";
                else if ($categoria_url === 'accesorios') echo "Periféricos y Accesorios";
                else if ($categoria_url === 'ofertas') echo "Ofertas de verano";
                else if ($id_cat_filtrada > 0 && count($productos) > 0) echo "Filtrado por Componente";
                else echo "Componentes nuevos y seminuevos <br> <small>(Todos los productos pasaron por pruebas de calidad antes de ser publicados)</small>";
            ?>
        </h2>

        <div class="grid-productos" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px;">
            <?php if (count($productos) > 0): ?>
                <?php foreach ($productos as $p): ?>
                    <?php 
                        // Calculamos cuántas piezas le quedan disponibles al usuario para AGREGAR
                        $id_actual = (int)$p['id_producto'];
                        $ya_en_carrito = isset($cantidades_en_carrito[$id_actual]) ? $cantidades_en_carrito[$id_actual] : 0;
                        $stock_real_restante = (int)$p['stock'] - $ya_en_carrito;
                    ?>
                    <div class="tarjeta-prd" style="background-color: #161625; border: 1px solid #333355; border-radius: 8px; padding: 15px; display: flex; flex-direction: column; justify-content: space-between; transition: transform 0.2s;">
                        
                        <a href="producto_detalle.php?id=<?php echo $p['id_producto']; ?>" style="text-decoration: none; color: inherit; display: block; cursor: pointer;">
                            <img src="<?php echo htmlspecialchars($p['imagen']); ?>" alt="<?php echo htmlspecialchars($p['nombre']); ?>" style="width: 100%; height: 160px; object-fit: contain; margin-bottom: 15px;">
                            
                            <div>
                                <h3 style="font-size: 15px; margin-bottom: 10px; color: white; height: 36px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;"><?php echo htmlspecialchars($p['nombre']); ?></h3>
                                
                                <?php if ($p['en_oferta']): ?>
                                    <p style="text-decoration: line-through; color: #8888aa; font-size: 12px; margin: 0;">$<?php echo number_format($p['precio'], 2); ?></p>
                                    <p style="color: #00ff88; font-weight: bold; font-size: 17px; margin: 3px 0;">$<?php echo number_format($p['precio_oferta'], 2); ?> <span style="font-size: 11px; color:#aaa;">MXN</span></p>
                                <?php else: ?>
                                    <p style="color: white; font-weight: bold; font-size: 17px; margin: 18px 0 3px 0;">$<?php echo number_format($p['precio'], 2); ?> <span style="font-size: 11px; color:#aaa;">MXN</span></p>
                                <?php endif; ?>
                                
                                <p style="font-size: 12px; color: #aaa; margin-bottom: 15px;">
                                    Disponibles: <span id="stock-txt-<?php echo $id_actual; ?>"><?php echo $stock_real_restante; ?></span> uds
                                </p>
                            </div>
                        </a>

                        <?php if ($stock_real_restante > 0): ?>
                            <button class="btn-m" id="btn-cart-<?php echo $id_actual; ?>" 
                                    onclick="controladorStockIndex(<?php echo $id_actual; ?>, <?php echo (int)$p['stock']; ?>)" 
                                    style="width: 100%; background-color: #6a11cb; color: white; border: none; padding: 10px; border-radius: 50px; cursor: pointer; font-weight: bold;">
                                Agregar al carrito
                            </button>
                        <?php else: ?>
                            <button class="btn-m" disabled 
                                    style="width: 100%; background-color: #333344; color: #8888aa; border: none; padding: 10px; border-radius: 50px; cursor: not-allowed; font-weight: bold;">
                                Agotado en tu carrito
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: #8888aa; text-align: center; margin-top: 40px; font-size: 14px;">No se encontraron componentes disponibles en esta categoría por el momento.</p>
            <?php endif; ?>
        </div>
    </section>
</main>

<script src="js/carrito.js"></script>

<script>
// Diccionario global en JavaScript para llevar el conteo de lo que el cliente interactúa en vivo
const stockContadores = {
    <?php foreach ($productos as $p): ?>
        <?php 
            $id_act = (int)$p['id_producto'];
            $ya_en_car = isset($cantidades_en_carrito[$id_act]) ? $cantidades_en_carrito[$id_act] : 0;
            echo $id_act . ": " . ((int)$p['stock'] - $ya_en_car) . ",";
        ?>
    <?php endforeach; ?>
};

function controladorStockIndex(idProducto, stockMaxBase) {
    // Verificamos si aún queda inventario en el contador de JS
    if (stockContadores[idProducto] <= 0) return;

    // 1. Invoca tu función nativa de js/carrito.js para mandarlo al badge/cookie
    agregarAlCarrito(idProducto, 1);

    // 2. Restamos una pieza en caliente del contador visual
    stockContadores[idProducto]--;

    const textoStock = document.getElementById('stock-txt-' + idProducto);
    const boton = document.getElementById('btn-cart-' + idProducto);

    if (stockContadores[idProducto] <= 0) {
        // Si el usuario ya se va a llevar la última pieza del almacén, congelamos el botón
        if (textoStock) textoStock.innerText = "0";
        if (boton) {
            boton.disabled = true;
            boton.innerText = "Agotado en tu carrito";
            boton.style.backgroundColor = "#333344";
            boton.style.color = "#8888aa";
            boton.style.cursor = "not-allowed";
        }
    } else {
        // Si aún quedan piezas, solo actualizamos el número en la tarjeta
        if (textoStock) textoStock.innerText = stockContadores[idProducto];
    }
}
</script>

<?php require_once 'inc/pie.php'; ?>

