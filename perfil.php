<?php
// perfil.php
require_once 'inc/conexion.php';

// Iniciamos sesión o verificamos estado antes de cargar cabeceras para evitar problemas de redirección
if (session_status() === PHP_SESSION_NONE) { 
    session_start();
}

// Redirección de seguridad si intentan meterse al perfil siendo invitados
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

$id_usr = $_SESSION['id'];
$mensaje_exito = "";
$mensaje_error = "";

// === PROCESAR ACTUALIZACIÓN DE DATOS POR POST ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevo_nom = trim($_POST['nombre_usr']);
    
    // Capturamos los campos desglosados del domicilio de manera independiente
    $calle  = isset($_POST['calle']) ? trim($_POST['calle']) : '';
    $ciudad = isset($_POST['ciudad']) ? trim($_POST['ciudad']) : '';
    $estado = isset($_POST['estado']) ? trim($_POST['estado']) : '';
    $cp     = isset($_POST['cp']) ? trim($_POST['cp']) : '';

    if (!empty($nuevo_nom)) {
        if (!empty($calle) && !empty($ciudad) && !empty($estado) && !empty($cp)) {
            try {
                // Concatenamos la dirección en el formato limpio estándar de tu base de datos
                $direccion_completa = "$calle, CP $cp. $ciudad, $estado.";

                // Actualizamos el nombre y la dirección en la BD (el correo no se incluye por seguridad)
                $sql_update = "UPDATE usuario SET nombre = :nom, direccion = :dir WHERE id_usuario = :id";
                $stmt_update = $pdo->prepare($sql_update);
                $stmt_update->execute([
                    ':nom' => $nuevo_nom,
                    ':dir' => $direccion_completa,
                    ':id'  => $id_usr
                ]);

                // Actualizamos la variable de sesión del nombre para que el encabezado cambie al instante
                $_SESSION['nom'] = $nuevo_nom;
                $mensaje_exito = "¡Datos actualizados con éxito en Bit-Stock!";
                
            } catch (PDOException $e) {
                $mensaje_error = "Error al actualizar los datos: " . $e->getMessage();
            }
        } else {
            $mensaje_error = "Por favor, completa todos los campos de la dirección de envío.";
        }
    } else {
        $mensaje_error = "El nombre no puede quedar vacío.";
    }
}

// Consultamos toda la información actualizada del usuario en Neon.tech
$sql = "SELECT nombre, correo, direccion, fecha_registro, rol FROM usuario WHERE id_usuario = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $id_usr]);
$usr = $stmt->fetch();

if (!$usr) {
    header("Location: auth.php?action=logout");
    exit;
}

// === DECONSTRUCCIÓN ROBUSTA DE LA DIRECCIÓN PARA EL FORMULARIO ===
$val_calle = "";
$val_cp = "";
$val_ciudad = "";
$val_estado = "";

if (!empty($usr['direccion'])) {
    try {
        $partes_calle = explode(', CP ', $usr['direccion']);
        if (count($partes_calle) >= 2) {
            $val_calle = trim($partes_calle[0]);
            $resto = $partes_calle[1];
            
            $val_cp = substr($resto, 0, 5);
            $resto_ubicacion = trim(substr($resto, 6)); 
            
            $partes_ubicacion = explode(', ', $resto_ubicacion);
            if (count($partes_ubicacion) >= 2) {
                $val_ciudad = trim($partes_ubicacion[0]);
                $val_estado = trim(str_replace('.', '', $partes_ubicacion[1]));
            } else {
                $val_ciudad = trim(str_replace('.', '', $resto_ubicacion));
            }
        } else {
            $val_calle = $usr['direccion'];
        }
    } catch (Exception $e) {
        $val_calle = $usr['direccion'];
    }
}

// Limpieza automática preventiva de bugs acumulados por el fallo anterior
if (strpos($val_ciudad, 'CP ') !== false) {
    $val_ciudad = trim(preg_replace('/CP \d{5}\.?/', '', $val_ciudad));
}

require_once 'inc/encabezado.php'; // Carga el menú superior modificado
?>

<main class="cnt-carrito" style="max-width: 800px; margin: 40px auto; padding: 0 20px;">
    
    <h2 class="tit-seccion" style="margin-bottom: 25px; font-size: 24px;">Mi cuenta de Bit-Stock</h2>

    <?php if (!empty($mensaje_exito)): ?>
        <p style="color: #00ff88; background-color: rgba(0,255,136,0.1); padding: 12px; border-radius: 6px; font-size: 14px; margin-bottom: 20px; border: 1px solid rgba(0,255,136,0.2);">
            <?php echo $mensaje_exito; ?>
        </p>
    <?php endif; ?>
    <?php if (!empty($mensaje_error)): ?>
        <p style="color: #ff4d4d; background-color: rgba(255,77,77,0.1); padding: 12px; border-radius: 6px; font-size: 14px; margin-bottom: 20px; border: 1px solid rgba(255,77,77,0.2);">
            ⚠️ <?php echo $mensaje_error; ?>
        </p>
    <?php endif; ?>

    <form action="perfil.php" method="POST" style="display: flex; gap: 30px; background-color: #161625; border: 1px solid #333355; border-radius: 12px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.3);">
        
        <div style="flex: 0 0 150px; text-align: center; border-right: 1px solid #22223b; padding-right: 25px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <img src="img/icono_usuario.jpg" alt="Avatar Usuario" class="imgusuario">
            <span style="display: inline-block; background-color: #6a11cb; color: white; font-size: 11px; font-weight: bold; padding: 4px 10px; border-radius: 20px; text-transform: uppercase; margin-top: 15px; letter-spacing: 1px;">
                <?php echo htmlspecialchars($usr['rol']); ?>
            </span>
        </div>

        <div style="flex: 1; display: flex; flex-direction: column; gap: 20px;">
            
            <div style="display: flex; flex-direction: column; gap: 5px;">
                <label style="color: #a29bfe; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: bold;">Nombre Completo</label>
                <p class="modo-lectura" style="color: white; font-size: 17px; font-weight: bold; margin: 5px 0;"><?php echo htmlspecialchars($usr['nombre']); ?></p>
                <input type="text" name="nombre_usr" class="modo-edicion" value="<?php echo htmlspecialchars($usr['nombre']); ?>" required
                       style="display: none; background-color: #0b0b14; border: 1px solid #333355; color: white; padding: 10px; border-radius: 6px; font-size: 15px; font-weight: bold; outline: none; width: 100%; box-sizing: border-box;">
            </div>

            <div style="display: flex; flex-direction: column; gap: 5px;">
                <label style="color: #8888aa; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: bold;">Correo Electrónico (No modificable)</label>
                <p style="color: white; font-size: 16px; margin: 5px 0;"><?php echo htmlspecialchars($usr['correo']); ?></p>
            </div>

            <div style="display: flex; flex-direction: column; gap: 5px;">
                <label style="color: #a29bfe; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: bold;">Dirección de Envío Registrada</label>
                
                <div class="modo-lectura">
                    <?php if (!empty($usr['direccion'])): ?>
                        <p style="color: #00ff88; font-size: 15px; line-height: 1.4; font-weight: 500; margin: 5px 0;">
                            📍 <?php echo htmlspecialchars($usr['direccion']); ?>
                        </p>
                    <?php else: ?>
                        <p style="color: #ff9f43; font-size: 14px; font-style: italic; margin: 5px 0;">
                            ⚠️ Ninguna dirección registrada aún.
                        </p>
                    <?php endif; ?>
                </div>
                
                <div class="modo-edicion" style="display: none;">
                    <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 5px;">
                        
                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <span style="color: #aaa; font-size: 12px;">Calle y Número / Colonia</span>
                            <input type="text" name="calle" value="<?php echo htmlspecialchars($val_calle); ?>" placeholder="Ej. Av. Tecnológico #123, Col. Centro"
                                   style="background-color: #0b0b14; border: 1px solid #333355; color: white; padding: 10px; border-radius: 6px; font-size: 14px; width: 100%; box-sizing: border-box;">
                        </div>

                        <div style="display: flex; gap: 15px;">
                            <div style="display: flex; flex-direction: column; gap: 4px; flex: 1;">
                                <span style="color: #aaa; font-size: 12px;">Municipio / Ciudad</span>
                                <input type="text" name="ciudad" value="<?php echo htmlspecialchars($val_ciudad); ?>" placeholder="Ej. Pátzcuaro"
                                       style="background-color: #0b0b14; border: 1px solid #333355; color: white; padding: 10px; border-radius: 6px; font-size: 14px; width: 100%; box-sizing: border-box;">
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 4px; flex: 1;">
                                <span style="color: #aaa; font-size: 12px;">Estado</span>
                                <input type="text" name="estado" value="<?php echo htmlspecialchars($val_estado); ?>" placeholder="Ej. Michoacán"
                                       style="background-color: #0b0b14; border: 1px solid #333355; color: white; padding: 10px; border-radius: 6px; font-size: 14px; width: 100%; box-sizing: border-box;">
                            </div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 4px; width: 50%;">
                            <span style="color: #aaa; font-size: 12px;">Código Postal (CP)</span>
                            <input type="text" name="cp" value="<?php echo htmlspecialchars($val_cp); ?>" placeholder="12345" maxlength="5"
                                   style="background-color: #0b0b14; border: 1px solid #333355; color: white; padding: 10px; border-radius: 6px; font-size: 14px; width: 100%; box-sizing: border-box;">
                        </div>

                    </div>
                </div>
            </div>

            <hr style="border: 0; border-top: 1px solid #22223b; margin: 10px 0;">

            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="color: #8888aa; font-size: 12px;">
                    Cliente desde el: <?php echo date('d/m/Y', strtotime($usr['fecha_registro'])); ?>
                </span>
                
                <div style="display: flex; gap: 10px;">
                    <button type="button" id="btn-editar" onclick="activarEdicion()" style="background: linear-gradient(90deg, #6a11cb 0%, #2575fc 100%); color: white; border: none; padding: 8px 20px; border-radius: 20px; cursor: pointer; font-size: 13px; font-weight: bold; transition: opacity 0.2s;">
                        Editar Perfil
                    </button>

                    <button type="submit" id="btn-guardar" style="display: none; background: linear-gradient(90deg, #00ff88 0%, #00a8ff 100%); color: #161625; border: none; padding: 8px 20px; border-radius: 20px; cursor: pointer; font-size: 13px; font-weight: bold; transition: opacity 0.2s;">
                        Guardar Cambios
                    </button>

                    <button type="button" id="btn-cancelar" onclick="cancelarEdicion()" style="display: none; background: #333355; color: white; border: 1px solid #444466; padding: 8px 20px; border-radius: 20px; cursor: pointer; font-size: 13px; font-weight: bold; transition: opacity 0.2s;">
                        Cancelar
                    </button>
                    
                    <button type="button" id="btn-volver" onclick="window.location='index.php'" style="background: linear-gradient(90deg, #22223b 0%, #333355 100%); color: white; border: 1px solid #444466; padding: 8px 20px; border-radius: 20px; cursor: pointer; font-size: 13px; font-weight: bold; transition: opacity 0.2s;">
                        Volver
                    </button>
                </div>
            </div>

        </div>

    </form>

</main>

<script>
function activarEdicion() {
    // 1. Ocultar todos los elementos de texto plano
    document.querySelectorAll('.modo-lectura').forEach(el => el.style.display = 'none');
    document.getElementById('btn-editar').style.display = 'none';
    document.getElementById('btn-volver').style.display = 'none';

    // 2. Mostrar las cajas de texto editables e inputs
    document.querySelectorAll('.modo-edicion').forEach(el => el.style.display = 'block');
    document.getElementById('btn-guardar').style.display = 'block';
    document.getElementById('btn-cancelar').style.display = 'block';
}

function cancelarEdicion() {
    // 1. Mostrar de nuevo el texto plano original
    document.querySelectorAll('.modo-lectura').forEach(el => el.style.display = 'block');
    document.getElementById('btn-editar').style.display = 'block';
    document.getElementById('btn-volver').style.display = 'block';

    // 2. Ocultar el formulario de edicion
    document.querySelectorAll('.modo-edicion').forEach(el => el.style.display = 'none');
    document.getElementById('btn-guardar').style.display = 'none';
    document.getElementById('btn-cancelar').style.display = 'none';
}
</script>

<?php require_once 'inc/pie.php'; ?>