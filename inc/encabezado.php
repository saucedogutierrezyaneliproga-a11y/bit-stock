<?php
// inc/encabezado.php

// iniciamos sesion en el encabezado para saber si hay un usuario logueado en cualquier pagina
if (session_status() === PHP_SESSION_NONE) { 
    session_start();
}

// Inicializamos el contador del carrito en 0 por defecto (para usuarios invitados)
$total_carrito = 0;

// CASO 1: Si el usuario ya inicio sesion, calculamos dinamicamente cuantas piezas tiene en su carrito desde la BD
if (isset($_SESSION['id'])) {
    // Incluimos de forma segura la conexion si aun no se ha cargado en el archivo principal
    require_once 'inc/conexion.php';
    
    // Consulta SQL con una subconsulta para sumar todas las cantidades de la tabla intermedia pertenecientes al usuario
    $sql_badge = "SELECT SUM(cantidad) AS total 
                  FROM carrito_detalle 
                  WHERE id_carrito = (SELECT id_carrito FROM carrito WHERE id_usuario = :u)";
                  
    $stmt_badge = $pdo->prepare($sql_badge);
    $stmt_badge->execute([':u' => $_SESSION['id']]);
    $res_badge = $stmt_badge->fetch();
    
    // Si la consulta arroja un resultado y no es nulo, asignamos el valor acumulado
    if ($res_badge && $res_badge['total'] !== null) {
        $total_carrito = (int)$res_badge['total'];
    }
} 
// CASO 2: Si el usuario es un invitado, contamos dinamicamente las unidades guardadas en las cookies
else if (isset($_COOKIE['bitstock_cart'])) {
    // Desmenuzamos el JSON guardado en el navegador para transformarlo en un arreglo de PHP
    $carrito_temporal = json_decode($_COOKIE['bitstock_cart'], true);
    
    // Validamos que sea un arreglo valido y que contenga elementos guardados
    if (is_array($carrito_temporal) && count($carrito_temporal) > 0) {
        // array_sum suma de golpe todos los valores internos del arreglo (las cantidades de cada hardware)
        $total_carrito = array_sum($carrito_temporal);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bit-Stock - Tienda de Componentes</title>
    <link rel="stylesheet" href="css/estilos.css">  </head>
<body>

<header class="hdr">
    <div class="hdr-cont">
        <a href="index.php" class="logo">
            <img src="img/Complet-bitsotck.png" alt="Bit-Stock Logo" class="BS-logo">
        </a>

      <div class="nav-links">
    <a href="index.php">Productos</a>
    
    <a href="index.php?cat=accesorios">Accesorios</a>
    
    <a href="index.php?cat=ofertas">Ofertas</a>
    
    <a href="nosotros.php">Sobre Nosotros</a>
</div>

        <form action="./index.php" method="GET" class="frm-busq">
            <img src="img/lupa.png" alt="Buscar" class="ico-busq">
            <input type="text" name="txtBuscar" placeholder="¿Qué componente buscas?" 
                   value="<?php echo isset($buscar) ? htmlspecialchars($buscar) : ''; ?>">
                   </form>

        <div class="hdr-der">

            <?php if (isset($_SESSION['nom'])): ?> 
                <a href="perfil.php" class="usr-nom" style="text-decoration: underline; cursor: pointer; margin-right: 5px;">
                    Hola, <?php echo htmlspecialchars(strtolower($_SESSION['nom'])); ?>
                </a> 
                <a href="auth.php?action=logout" class="lnk-sesion"> Salir </a> 
            <?php else: ?> 

                <a href="login.php" class="lnk-sesion"> Ingresar </a>
                
            <?php endif; ?>

            <a href="carrito.php" class="btn-car">
                <img src="img/carrito.png" alt="Carrito">
                <span class="badge"><?php echo $total_carrito; ?></span> 
            </a>
        </div>
    </div>
</header>