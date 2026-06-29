<?php
// index.php

// 1. Cargamos de forma obligatoria la conexion a Neon.tech
require_once 'inc/conexion.php';

// 2. Cargamos el menu superior comun
require_once 'inc/encabezado.php';

// 3. Logica del Buscador y Filtros (Procesamiento PHP y Generacion Dinamica)
$buscar = isset($_GET['q']) ? trim($_GET['q']) : '';
$solo_ofertas = isset($_GET['ofertas']) ? (int)$_GET['ofertas'] : 0;

// Construimos la consulta base estructurando que solo traiga productos activos
$sql = "SELECT p.*, c.nombre AS cat_nombre 
        FROM productos p 
        INNER JOIN categorias c ON p.id_categoria = c.id_categoria 
        WHERE en_oferta = true";

$params = [];

// si el usuario escribio algo en el buscador 'Q search'
if ($buscar !== '') {
    $sql .= " AND (p.nombre ILIKE :busq OR p.descripcion ILIKE :busq)";
    $params[':busq'] = '%' . $buscar . '%'; // ILIKE busca coincidencias sin importar mayusculas
}

// si dio clic en el enlace de "Ofertas" del menu
if ($solo_ofertas === 1) {
    $sql .= " AND p.en_oferta = true";
}

// ordenamos para que los productos mas nuevos salgan primero
$sql .= " ORDER BY p.id_producto DESC";

// Preparamos y ejecutamos la consulta de forma segura
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll();
?>

<main class="cnt-prod">
    
    <h2 class="tit-seccion">
        <?php 
            if ($solo_ofertas === 1) {
                echo "Ofertas del Día";
            } elseif ($buscar !== '') {
                echo "Resultados para: '" . htmlspecialchars($buscar) . "'";
            } else {
                echo "Catálogo de Componentes";
            }
        ?>
    </h2>

    <div class="grid-prod">
        <?php if (count($productos) > 0): ?>
            <?php foreach ($productos as $p): ?>
                
                <div class="card">
                    
                    <?php if ($p['en_oferta']): ?>
                        <span class="tag-oferta">Oferta del día</span>
                    <?php endif; ?>

                    <div class="card-img">
                        <img src="<?php echo htmlspecialchars($p['imagen']); ?>" alt="<?php echo htmlspecialchars($p['nombre']); ?>">
                    </div>

                    <div class="card-info">
                        <h3><?php echo htmlspecialchars($p['nombre']); ?></h3>
                        
                        <?php if ($p['en_oferta']): ?>
                            <p class="precio-ant">$<?php echo number_format($p['precio'], 2); ?></p>
                            <p class="precio-act">
                                $<?php echo number_format($p['precio_oferta'], 2); ?> MXN
                                <span class="descuento">¡En rebaja!</span>
                            </p>
                        <?php else: ?>
                            <p class="precio-act">$<?php echo number_format($p['precio'], 2); ?> MXN</p>
                        <?php endif; ?>

                        <p class="stock-info">
                            <?php if ($p['stock'] > 0): ?>
                                Disponibles: <span class="<?php echo ($p['stock'] <= 3) ? 'stock-critico' : ''; ?>"><?php echo $p['stock']; ?> uds</span>
                            <?php else: ?>
                                <span class="stock-critico">Agotado temporalmente</span>
                            <?php endif; ?>
                        </p>
                    </div>

                    <?php if ($p['stock'] > 0): ?>
                        <button class="btn-m" onclick="agregarAlCarrito(<?php echo $p['id_producto']; ?>, 1)">
                            Agregar al carrito
                        </button>
                    <?php else: ?>
                        <button class="btn-m" style="background: #444; cursor: not-allowed;" disabled>
                            Sin Stock
                        </button>
                    <?php endif; ?>

                </div>

            <?php endforeach; ?>
        <?php else: ?>
            <p style="grid-column: 1/-1; text-align: center; color: #8888aa; margin-top: 40px;">
                No se encontraron componentes que coincidan con tu búsqueda.
            </p>
        <?php endif; ?>
    </div>
</main>

<script src="js/carrito.js"></script>

<?php require_once 'inc/pie.php'; ?>

<?php require_once 'inc/pie.php'; ?>