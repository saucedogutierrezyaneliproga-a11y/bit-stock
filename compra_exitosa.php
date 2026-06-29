<?php
// compra_exitosa.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Seguridad: Si no hay datos de una compra reciente, lo mandamos al index
if (!isset($_SESSION['ultima_compra'])) {
    header("Location: index.php");
    exit;
}

require_once 'inc/encabezado.php';

// Extraemos los datos guardados temporalmente
$compra = $_SESSION['ultima_compra'];

// Una vez leídos, los borramos de la sesión para que si refresca la página, no se quede ciclado el ticket
unset($_SESSION['ultima_compra']);
?>

<main class="cnt-exito" style="max-width: 800px; margin: 40px auto; padding: 0 20px;">
    
    <div class="tarjeta-exito-header" style="text-align: center; background-color: #161625; border: 1px solid #333355; border-radius: 8px 8px 0 0; padding: 30px; border-bottom: 2px solid #00ff88;">
        <div class="icono-check" style="font-size: 50px; color: #00ff88; margin-bottom: 10px;">✔</div>
        <h1 style="color: white; font-size: 28px; margin: 0;">¡Tu pago fue aprobado!</h1>
        <p style="color: #8888aa; font-size: 14px; margin-top: 8px;">Orden de compra: <strong style="color: #00ff88;"><?php echo $compra['ticket']; ?></strong></p>
    </div>

    <div class="status-envio-msg" style="background-color: #1a1a30; padding: 20px; border-left: 4px solid #6a11cb; margin: 20px 0; border-radius: 0 4px 4px 0;">
        <p style="color: #d1d1e0; margin: 0; font-size: 15px; line-height: 1.5;">
            Estos son tus productos adquiridos. El almacén ya está preparando tu paquete y <strong>en unos minutos te enviaremos un correo electrónico</strong> con tu guía de rastreo.
        </p>
    </div>

    <div class="detalles-recibo" style="background-color: #161625; border: 1px solid #333355; border-radius: 8px; padding: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
        <h3 style="color: white; border-bottom: 1px solid #22223b; padding-bottom: 10px; margin-top: 0;">Artículos Adquiridos</h3>
        
        <div class="recibo-lista-productos" style="display: flex; flex-direction: column; gap: 15px; margin-bottom: 20px;">
            <?php foreach ($compra['productos'] as $prod): ?>
                <div class="recibo-item" style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #1f1f33; padding-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="width: 50px; height: 50px; background-color: #1c1c2e; border-radius: 4px; overflow: hidden; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <img src="<?php echo htmlspecialchars($prod['imagen']); ?>" alt="" style="width: 100%; height: 100%; object-fit: contain; padding: 3px;">
                        </div>
                        <div>
                            <h4 style="color: white; margin: 0; font-size: 14px; max-width: 400px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <?php echo htmlspecialchars($prod['nombre']); ?>
                            </h4>
                            <p style="color: #8888aa; margin: 3px 0 0 0; font-size: 12px;">Cantidad: <?php echo $prod['cantidad']; ?> uds x $<?php echo number_format($prod['precio'], 2); ?></p>
                        </div>
                    </div>
                    <span style="color: white; font-weight: bold; font-size: 14px;">
                        $<?php echo number_format($prod['subtotal'], 2); ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="recibo-totales" style="border-top: 1px solid #22223b; padding-top: 15px; display: flex; flex-direction: column; gap: 8px; max-width: 300px; margin-left: auto;">
            <div style="display: flex; justify-content: space-between; color: #8888aa; font-size: 13px;">
                <span>Subtotal:</span>
                <span style="color: white;">$<?php echo number_format($compra['subtotal'], 2); ?> MXN</span>
            </div>
            <div style="display: flex; justify-content: space-between; color: #8888aa; font-size: 13px;">
                <span>Costo de Envío:</span>
                <span style="color: white;">$<?php echo number_format($compra['envio'], 2); ?> MXN</span>
            </div>
            <div style="display: flex; justify-content: space-between; color: white; font-weight: bold; font-size: 16px; margin-top: 5px; border-top: 1px solid #22223b; padding-top: 8px;">
                <span>Total Cargado:</span>
                <span style="color: #00ff88;">$<?php echo number_format($compra['total'], 2); ?> MXN</span>
            </div>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="index.php" style="display: inline-block; background-color: #6a11cb; color: white; text-decoration: none; padding: 12px 35px; border-radius: 50px; font-weight: bold; font-size: 15px; transition: background 0.2s;">
                Volver a la Tienda
            </a>
        </div>
    </div>
</main>

<?php require_once 'inc/pie.php'; ?>