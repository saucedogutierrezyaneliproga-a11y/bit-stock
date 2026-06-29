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
        FROM carrito_detail cd
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

<main class="cnt-car">
    
    <section class="car-lista">
        <h2 class="tit-seccion">Selecciona un método de pago</h2>
        
        <div class="cnt-opciones">
            <button class="btn-opc opc-activa">
                <strong>Tarjetas</strong><br>
                <small>Nueva tarjeta de crédito o débito</small>
            </button>
            <button class="btn-opc" onclick="alert('SPEI se activará en la versión final comercial.')">
                <strong>Transferencia SPEI</strong><br>
                <small>Aprobación instantánea bancaria</small>
            </button>
        </div>

        <h3 class="tit-seccion" style="font-size: 18px;">Ingresa la información de tu tarjeta</h3>
        
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
    </section>

    <section class="car-resumen">
        <h3>Resumen de compra</h3>
        <div class="res-fila">
            <span>Subtotal productos:</span>
            <span>$<?php echo number_format($subtotal, 2); ?></span>
        </div>
        <div class="res-fila">
            <span>Envío:</span>
            <span>$<?php echo number_format($envio, 2); ?></span>
        </div>
        <div class="res-total">
            <span>Total</span>
            <span>$<?php echo number_format($total, 2); ?> MXN</span>
        </div>

        <button type="button" class="btn-m" id="btn-finalizar" style="margin-top: 20px;">
            Finalizar Compra
        </button>
    </section>

</main>

<script>
document.getElementById('btn-finalizar').addEventListener('click', function() {
    // Captura de elementos del DOM
    const tar = document.getElementById('num_tar');
    const ttl = document.getElementById('ttl_tar');
    const vnc = document.getElementById('vnc_tar');
    const cvc = document.getElementById('cvc_tar');
    const frm = document.getElementById('frm-pago');

    let valido = true;

    // Ocultar errores previos
    document.querySelectorAll('.msg-err').forEach(el => el.style.display = 'none');

    // Validar Numero de Tarjeta (debe tener 16 caracteres numericos)
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

    // Validar formato basico de Vencimiento (MM/AA)
    if (vnc.value.trim().length !== 5 || !vnc.value.includes('/')) {
        document.getElementById('err-vnc').style.display = 'block';
        if(valido) vnc.focus();
        valido = false;
    }

    // Validar Codigo de Seguridad (3 digitos)
    if (cvc.value.trim().length !== 3 || isNaN(cvc.value)) {
        document.getElementById('err-cvc').style.display = 'block';
        if(valido) cvc.focus();
        valido = false;
    }

    // Si todo pasa las pruebas del DOM, enviamos el formulario a procesar_pago.php
    if (valido) {
        frm.submit();
    }
});
</script>

<?php require_once 'inc/pie.php'; ?>