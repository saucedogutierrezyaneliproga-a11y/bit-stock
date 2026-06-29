<?php
// pago.php
require_once 'inc/conexion.php';
require_once 'inc/encabezado.php';

// Validacion de seguridad basica de sesion
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

$id_usr = $_SESSION['id'];

// Consultamos nuevamente el total para asegurar que no se alteren los precios en el frontend
$sql = "SELECT p.precio, p.en_oferta, p.precio_oferta, cd.cantidad 
        FROM carrito_detalle cd
        INNER JOIN carrito c ON cd.id_carrito = c.id_carrito
        INNER JOIN productos p ON cd.id_producto = p.id_producto
        WHERE c.id_usuario = :u";

$stmt = $pdo->prepare($sql);
$stmt->execute([':u' => $id_usr]);
$items = $stmt->fetchAll();

if (count($items) === 0) {
    header("Location: index.php");
    exit;
}

$subtotal = 0.00;
$envio = 15.00; // Constante del mockup

foreach ($items as $item) {
    $precio_final = $item['en_oferta'] ? $item['precio_oferta'] : $item['precio'];
    $subtotal += ($precio_final * $item['cantidad']);
}
$total = $subtotal + $envio;
?>

<main class="cnt-carrito" style="max-width: 1200px; margin: 30px auto; padding: 0 20px;">
    <h2>Finalizar tu Compra</h2>

    <div class="carrito-layout-global" style="display: flex; gap: 30px; margin-top: 20px;">
        
        <div class="lista-carrito" style="flex: 1.5;">
            <h3 class="tit-seccion" style="font-size: 18px; margin-bottom: 20px;">Selecciona un método de pago</h3>
            
            <div class="cnt-opciones">
                <button class="btn-opc opc-activa">
                    <strong>Tarjetas</strong><br>
                    <small>Nueva tarjeta de crédito o débito</small>
                </button>
                <button class="btn-opc" type="button" onclick="alert('SPEI se activará en la versión final comercial.')">
                    <strong>Transferencia SPEI</strong><br>
                    <small>Aprobación instantánea bancaria</small>
                </button>
            </div>

            <h3 class="tit-seccion" style="font-size: 18px; margin-top: 25px; margin-bottom: 15px;">Ingresa la información de tu tarjeta</h3>
            
            <form id="frm-pago" action="procesar_pago.php" method="POST" class="frm-tarjeta">
                
                <div class="grp">
                    <label>Número de tarjeta</label>
                    <input type="text" name="num_tar" id="num_tar" placeholder="0000 0000 0000 0000" maxlength="16">
                    <span id="err-tar" class="msg-err">Completa este campo con los 16 dígitos.</span>
                </div>

                <div class="grp">
                    <label>Nombre del titular</label>
                    <input type="text" name="ttl_tar" id="ttl_tar" placeholder="Ej.: María López">
                    <span id="err-ttl" class="msg-err">Introduce el nombre del titular como aparece en la tarjeta.</span>
                </div>

                <div class="fila-doble">
                    <div class="grp">
                        <label>Vencimiento</label>
                        <input type="text" name="vnc_tar" id="vnc_tar" placeholder="MM/AA" maxlength="5">
                        <span id="err-vnc" class="msg-err">Formato MM/AA requerido.</span>
                    </div>
                    <div class="grp">
                        <label>Código de seguridad (CVC)</label>
                        <input type="password" name="cvc_tar" id="cvc_tar" placeholder="000" maxlength="3">
                        <span id="err-cvc" class="msg-err">Código inválido.</span>
                    </div>
                </div>
            </form>
        </div>

        <div class="resumen-compra-tarjeta" style="flex: 1; height: fit-content;">
            <h3>Resumen de Compra</h3>
            <div class="resumen-linea" style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                <span>Subtotal productos:</span>
                <span>$<?php echo number_format($subtotal, 2); ?> MXN</span>
            </div>
            <div class="resumen-linea" style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                <span>Envío a domicilio:</span>
                <span>$<?php echo number_format($envio, 2); ?> MXN</span>
            </div>
            <hr style="border-color: #22223b; margin: 15px 0;">
            <div class="resumen-linea total" style="display: flex; justify-content: space-between; font-weight: bold; font-size: 18px;">
                <span>Total a pagar:</span>
                <span class="precio-total" style="color: #00ff88;">$<?php echo number_format($total, 2); ?> MXN</span>
            </div>

            <button type="button" class="btn-pagar" id="btn-finalizar" style="width: 100%; margin-top: 20px; background-color: #6a11cb; color: white; border: none; padding: 12px; border-radius: 50px; cursor: pointer; font-weight: bold;">
                Finalizar Compra
            </button>
        </div>

    </div>
</main>

<script>
document.getElementById('btn-finalizar').addEventListener('click', function() {
    const tar = document.getElementById('num_tar');
    const ttl = document.getElementById('ttl_tar');
    const vnc = document.getElementById('vnc_tar');
    const cvc = document.getElementById('cvc_tar');
    const frm = document.getElementById('frm-pago');

    let valido = true;

    // Ocultar errores previos
    document.querySelectorAll('.msg-err').forEach(el => el.style.display = 'none');

    // Validar Numero de Tarjeta
    if (tar.value.trim().length !== 16 || isNaN(tar.value)) {
        document.getElementById('err-tar').style.display = 'block';
        tar.focus();
        valido = false;
    }

    // Validar Nombre del Titular
    if (ttl.value.trim() === "") {
        document.getElementById('err-ttl').style.display = 'block';
        if(valido) ttl.focus();
        valido = false;
    }

    // Validar Vencimiento
    if (vnc.value.trim().length !== 5 || !vnc.value.includes('/')) {
        document.getElementById('err-vnc').style.display = 'block';
        if(valido) vnc.focus();
        valido = false;
    }

    // Validar CVC
    if (cvc.value.trim().length !== 3 || isNaN(cvc.value)) {
        document.getElementById('err-cvc').style.display = 'block';
        if(valido) cvc.focus();
        valido = false;
    }

    // Envío seguro si pasa el JS
    if (valido) {
        frm.submit();
    }
});
</script>

<?php require_once 'inc/pie.php'; ?>