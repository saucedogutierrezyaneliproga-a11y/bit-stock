<?php
// inc/encabezado.php

// iniciamos sesion en el encabezado para saber si hay un usuario logueado en cualquier pagina
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bit-Stock - Componentes de Cómputo</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>

<header class="hdr">
    <div class="hdr-cont">
        <a href="index.php" class="logo">
            <img src="img/Complet-bitsotck.png" alt="Bit-Stock Logo" class="BS-logo">

        </a>

        <nav class="nav-links">
            <a href="#">Más información</a> 
            <a href="index.php">Productos</a>
            <a href="#">Accesorios</a>  
            <a href="index.php?ofertas=1">Ofertas</a> </nav>

        <form action="index.php" method="GET" class="frm-busq">
            <img src="img/lupa.png" alt="Buscar" class="ico-busq">
            <input type="text" name="q" placeholder="Buscar" value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
        </form>

        <div class="hdr-der">
            <?php if (isset($_SESSION['nom'])): ?>
                <span class="usr-nom">hola,<?php echo htmlspecialchars(strtolower($_SESSION['nom'])); ?></span>
                <a href="login.php" class="lnk-sesion">Salir</a>
            <?php else: ?>
                <a href="login.php" class="lnk-sesion">Ingresar</a>
            <?php endif; ?>

            <a href="carrito.php" class="btn-car">
                <img src="img/carrito.png" alt="Carrito">
                <span class="badge">0</span>
            </a>
        </div>
    </div>
</header>