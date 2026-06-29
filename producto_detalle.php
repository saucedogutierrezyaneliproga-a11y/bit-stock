<?php
// producto_detalle.php

require_once 'inc/conexion.php'; // conexión con neondb
require_once 'inc/encabezado.php'; // conexión con el encabezado dinámico

// Capturamos el ID del componente que viaja por la URL (?id=X) de forma entera
$id_prd = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Si no se envió un ID válido o es cero, redirige de inmediato al catálogo principal
if ($id_prd <= 0) {
    header("Location: index.php");
    exit;
}

// Consulta estructurada con un INNER JOIN para traer los datos del producto y su categoría
$sql = "SELECT p.*, c.nombre AS categoria_nombre 
        FROM productos p 
        INNER JOIN categorias c ON p.id_categoria = c.id_categoria 
        WHERE p.id_producto = :id";

$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $id_prd]);
$prod = $stmt->fetch(); // extraemos la información del componente

// Si el producto no existe en la BD, redirige limpiamente usando PHP nativo
if (!$prod) {
    header("Location: index.php?error=no_disponible");
    exit;
}

// Determinamos el precio real a cobrar basándonos en si tiene oferta activa o no
$precio_real = $prod['en_oferta'] ? $prod['precio_oferta'] : $prod['precio'];

// === LÓGICA DE MIGAJAS INTELIGENTES ===
$es_accesorio = ((int)$prod['id_categoria'] === 5);
$url_categoria_migaja = $es_accesorio 
    ? "index.php?cat=accesorios" 
    : "index.php?id_cat=" . $prod['id_categoria'];
?>

<main class="cnt-detalle">

    <div class="det-izq">
        <div class="imagen-principal">
            <img src="<?php echo htmlspecialchars($prod['imagen']); ?>" alt="<?php echo htmlspecialchars($prod['nombre']); ?>">
        </div>
    </div>

    <div class="det-centro">
        <span class="ruta-migas">
            <a href="index.php" style="color: inherit; text-decoration: none;">Inicio</a> > 
            <?php if (!$es_accesorio): ?>
                <a href="index.php" style="color: inherit; text-decoration: none;">Productos</a> > 
            <?php endif; ?>
            <a href="<?php echo $url_categoria_migaja; ?>" style="color: #a29bfe; text-decoration: underline; font-weight: bold;">
                <?php echo htmlspecialchars($prod['categoria_nombre']); ?>
            </a> > 
            <span style="color: #8888aa;"><?php echo htmlspecialchars($prod['nombre']); ?></span>
        </span>
        
        <h1 class="det-titulo" style="margin-top: 15px;"><?php echo htmlspecialchars($prod['nombre']); ?></h1>
        
        <div class="det-calificacion">
            <span>4.9 ⭐⭐⭐⭐⭐</span>
        </div>

        <div class="det-precios">
            <?php if ($prod['en_oferta']): ?>
                <span class="tag-oferta-det">OFERTA DEL DÍA</span>
                <p class="precio-ant-det">$<?php echo number_format($prod['precio'], 2); ?></p>
                <p class="precio-act-det">
                    $<?php echo number_format($prod['precio_oferta'], 2); ?> 
                    <span class="det-moneda">MXN</span>
                    <span class="descuento-det">Por tiempo limitado</span>
                </p>
            <?php else: ?>
                <p class="precio-act-det">$<?php echo number_format($prod['precio'], 2); ?> <span class="det-moneda">MXN</span></p>
            <?php endif; ?>
            <span class="iva-nota">IVA incluido</span>
        </div>

        <div class="det-caracteristicas">
            <h3>Información del producto</h3>
            <ul>
                <?php 
                $lineas = explode("\n", $prod['descripcion']);
                foreach ($lineas as $linea): 
                    if (trim($linea) !== ''):
                ?>
                    <li><?php echo htmlspecialchars(trim($linea)); ?></li>
                <?php 
                    endif;
                endforeach; 
                ?>
            </ul>
        </div>
    </div>

    <div class="det-der">
        <div class="det-compra">
            
            <?php if ($prod['stock'] > 0): ?>
                <p class="estado-stock">Stock disponible (queden <?php echo $prod['stock']; ?> uds)</p>
                
                <div class="selector-cantidad">
                    <label for="cant-select">Cantidad:</label>
                    <select id="cant-select"> 
                        <?php for($i = 1; $i <= min($prod['stock'], 5); $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?> unidad<?php echo $i > 1 ? 'es' : ''; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <button class="btn-m" id="btn-carrito-det" onclick="agregarDesdeDetalle(<?php echo $prod['id_producto']; ?>)" style="width: 100%; background-color: #6a11cb; color: white; border: none; padding: 12px; border-radius: 50px; cursor: pointer; font-weight: bold; margin-bottom: 10px;">
                    Agregar al carrito
                </button>
            <?php else: ?>
                <p class="estado-stock" style="color: #ff4d4d; font-weight: bold;">❌ Sin stock disponible</p>
                
                <div class="selector-cantidad" style="opacity: 0.4; pointer-events: none;">
                    <label>Cantidad:</label>
                    <select disabled><option>0 unidades</option></select>
                </div>

                <button class="btn-m" disabled style="width: 100%; background-color: #333344; color: #8888aa; border: none; padding: 12px; border-radius: 50px; cursor: not-allowed; font-weight: bold; margin-bottom: 10px;">
                    Agotado por el momento
                </button>
            <?php endif; ?>
            
            <p class="nota-agregar" style="text-align: center; font-size: 12px; color: #8888aa;">Agrega este producto a tu orden de compra</p>

            <div class="tienda-oficial" style="margin-top: 20px;">
                <img src="img/icono_usuario.jpg" alt="Tienda" class="img-tienda">
                <div>
                    <strong>Tienda oficial</strong><br>
                    <small>PC Builder Store</small>
                </div>
            </div>
        </div>
    </div>

</main>

<script src="js/carrito.js"></script>

<script>
function agregarDesdeDetalle(idProducto) {
    const selector = document.getElementById('cant-select');
    const boton = document.getElementById('btn-carrito-det');
    
    if (!selector || !boton) return;

    const cantidadAComprar = parseInt(selector.value, 10);

    // 1. Ejecutamos tu llamada original asíncrona hacia tu JS del carrito
    agregarAlCarrito(idProducto, cantidadAComprar);

    // 2. DESHABILITACIÓN INMEDIATA CONTROLADA:
    // Congelamos el botón y el select para que el usuario no sature el contador de la burbuja
    selector.disabled = true;
    selector.parentElement.style.opacity = "0.4";
    selector.parentElement.style.pointerEvents = "none";

    boton.disabled = true;
    boton.innerText = "Agregado al carrito";
    boton.style.background = "#22223b";
    boton.style.color = "#00ff88";
    boton.style.border = "1px solid rgba(0, 255, 136, 0.3)";
    boton.style.cursor = "not-allowed";
}
</script>

<?php require_once 'inc/pie.php'; // carga el cierre de la página común ?>