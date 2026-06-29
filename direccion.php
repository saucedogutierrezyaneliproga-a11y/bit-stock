<?php
// direccion.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'inc/conexion.php';

// 1. Bloque de seguridad: Si no hay sesión, al login de inmediato
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

$id_usr = $_SESSION['id'];
$error = "";

// 2. Comprobación de seguridad para romper bucles: Si YA tiene dirección, ¿qué hace aquí? ¡A pagar!
$sql_check = "SELECT direccion FROM usuario WHERE id_usuario = :id";
$stmt_check = $pdo->prepare($sql_check);
$stmt_check->execute([':id' => $id_usr]);
$user_check = $stmt_check->fetch();

if ($user_check && isset($user_check['direccion']) && !empty(trim($user_check['direccion']))) {
    header("Location: pago.php");
    exit;
}

// 3. Procesar el formulario cuando se envía por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $calle = isset($_POST['calle']) ? trim($_POST['calle']) : '';
    $ciudad = isset($_POST['ciudad']) ? trim($_POST['ciudad']) : '';
    $estado = isset($_POST['estado']) ? trim($_POST['estado']) : '';
    $cp = isset($_POST['cp']) ? trim($_POST['cp']) : '';

    if (!empty($calle) && !empty($ciudad) && !empty($estado) && !empty($cp)) {
        // Concatenamos la dirección en un formato limpio para tu Neon.tech
        $direccion_completa = "$calle, CP $cp. $ciudad, $estado.";

        $sql_update = "UPDATE usuario SET direccion = :dir WHERE id_usuario = :id";
        $stmt_update = $pdo->prepare($sql_update);
        
        if ($stmt_update->execute([':dir' => $direccion_completa, ':id' => $id_usr])) {
            header("Location: pago.php");
            exit;
        } else {
            $error = "Hubo un problema al guardar en la base de datos. Inténtalo de nuevo.";
        }
    } else {
        $error = "Por favor, completa todos los campos del domicilio de envío.";
    }
}

// 4. Una vez hechas las validaciones de cabeceras, ahora sí cargamos el encabezado de forma segura
require_once 'inc/encabezado.php';
?>

<main class="cnt-carrito" style="max-width: 600px; margin: 40px auto; padding: 0 20px;">
    <h2 style="margin-bottom: 10px;">Dirección de Envío</h2>
    <p style="color: #8888aa; font-size: 14px; margin-bottom: 25px;">Detectamos que no tienes un domicilio asignado. Por favor, regístralo para continuar con tu orden en Bit-Stock.</p>

    <div style="background-color: #161625; border: 1px solid #333355; border-radius: 8px; padding: 25px;">
        
        <?php if (!empty($error)): ?>
            <p style="color: #ff4d4d; background-color: rgba(255,77,77,0.1); padding: 10px; border-radius: 4px; font-size: 14px; margin-bottom: 15px;">
                ⚠️ <?php echo $error; ?>
            </p>
        <?php endif; ?>

        <form action="direccion.php" method="POST" style="display: flex; flex-direction: column; gap: 18px;">
            
            <div style="display: flex; flex-direction: column; gap: 5px;">
                <label style="color: #a29bfe; font-size: 14px; font-weight: bold;">Calle y Número / Colonia</label>
                <input type="text" name="calle" placeholder="Ej. Av. Tecnológico #123, Col. Centro" required
                       style="background-color: #0b0b14; border: 1px solid #333355; color: white; padding: 12px; border-radius: 6px; font-size: 14px;">
            </div>

            <div style="display: flex; gap: 15px;">
                <div style="display: flex; flex-direction: column; gap: 5px; flex: 1;">
                    <label style="color: #a29bfe; font-size: 14px; font-weight: bold;">Municipio / Ciudad</label>
                    <input type="text" name="ciudad" placeholder="Ej. Pátzcuaro" required
                           style="background-color: #0b0b14; border: 1px solid #333355; color: white; padding: 12px; border-radius: 6px; font-size: 14px; width: 100%; box-sizing: border-box;">
                </div>
                
                <div style="display: flex; flex-direction: column; gap: 5px; flex: 1;">
                    <label style="color: #a29bfe; font-size: 14px; font-weight: bold;">Estado</label>
                    <input type="text" name="estado" placeholder="Ej. Michoacán" required
                           style="background-color: #0b0b14; border: 1px solid #333355; color: white; padding: 12px; border-radius: 6px; font-size: 14px; width: 100%; box-sizing: border-box;">
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 5px; width: 50%;">
                <label style="color: #a29bfe; font-size: 14px; font-weight: bold;">Código Postal (CP)</label>
                <input type="text" name="cp" placeholder="12345" maxlength="5" required
                       style="background-color: #0b0b14; border: 1px solid #333355; color: white; padding: 12px; border-radius: 6px; font-size: 14px;">
            </div>

            <button type="submit" style="width: 100%; margin-top: 10px; background-color: #6a11cb; color: white; border: none; padding: 12px; border-radius: 50px; cursor: pointer; font-weight: bold; font-size: 15px;">
                Guardar dirección y continuar
            </button>
        </form>
    </div>
</main>

<?php require_once 'inc/pie.php'; ?>