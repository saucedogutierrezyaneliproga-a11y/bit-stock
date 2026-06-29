<?php
// auth.php

// iniciamos la sesion para poder guardar datos del usuario logueado
session_start();

// importamos la conexion a la base de datos 
require_once 'inc/conexion.php';

// verificamos que lleguen datos por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // capturamos la accion (registro o login)
    $acc = $_POST['accion'];

    if ($acc === 'registro') {
        $nom = trim($_POST['nom']);
        $eml = trim($_POST['eml']);
        $pwd = $_POST['pwd'];

        // encriptamos la contrasena
        $hash = password_hash($pwd, PASSWORD_DEFAULT);

        try {
            $sql = "INSERT INTO usuario (nombre, correo, contrasena) VALUES (:n, :e, :c)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':n' => $nom,
                ':e' => $eml,
                ':c' => $hash
            ]);

            echo "<script>alert('Registro exitoso'); window.location='login.php';</script>";

        } catch (PDOException $e) {
            echo "<script>alert('Error: El correo ya existe'); window.history.back();</script>";
        }
    }

    // === FLUJO DE LOGIN (CON MIGRACIÓN DE COOKIES) ===
    else if ($acc === 'login') {
        $eml = trim($_POST['eml']);
        $pwd = $_POST['pwd'];

        $sql = "SELECT id_usuario, nombre, contrasena, rol FROM usuario WHERE correo = :e";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':e' => $eml]);
        $usr = $stmt->fetch();

        if ($usr && password_verify($pwd, $usr['contrasena'])) {
            
            // Creamos las variables de sesion correspondientes
            $_SESSION['id'] = $usr['id_usuario'];
            $_SESSION['nom'] = $usr['nombre'];
            $_SESSION['rol'] = $usr['rol'];
            
            $id_usr = $usr['id_usuario'];

            // MIGRACIÓN: Validamos si el usuario traía productos acumulados en cookies como invitado
            if (isset($_COOKIE['bitstock_cart'])) {
                $carrito_temporal = json_decode($_COOKIE['bitstock_cart'], true);

                if (is_array($carrito_temporal) && count($carrito_temporal) > 0) {
                    try {
                        // 1. Asegurar la existencia del registro en la tabla 'carrito'
                        $sql_c = "INSERT INTO carrito (id_usuario) VALUES (:u) ON CONFLICT (id_usuario) DO NOTHING";
                        $pdo->prepare($sql_c)->execute([':u' => $id_usr]);

                        // 2. Traer el id_carrito asignado
                        $sql_id = "SELECT id_carrito FROM carrito WHERE id_usuario = :u";
                        $stmt_id = $pdo->prepare($sql_id);
                        $stmt_id->execute([':u' => $id_usr]);
                        $res_car = $stmt_id->fetch();

                        if ($res_car) {
                            $id_car = $res_car['id_carrito'];

                            // 3. Volcar cada producto de la cookie a la tabla intermedia en Neon.tech
                            $sql_migrar = "INSERT INTO carrito_detalle (id_carrito, id_producto, cantidad) 
                                           VALUES (:c, :p, :q)
                                           ON CONFLICT (id_carrito, id_producto) 
                                           DO UPDATE SET cantidad = carrito_detalle.cantidad + :q";
                            
                            $stmt_migrar = $pdo->prepare($sql_migrar);

                            foreach ($carrito_temporal as $id_prd => $cant) {
                                $stmt_migrar->execute([
                                    ':c' => $id_car,
                                    ':p' => (int)$id_prd,
                                    ':q' => (int)$cant
                                ]);
                            }
                        }

                        // Destruimos la cookie del navegador asignándole una fecha en el pasado
                        setcookie('bitstock_cart', '', time() - 3600, "/");

                    } catch (PDOException $e) {
                        // Omitimos errores silenciosamente en producción para no frenar el inicio de sesión
                    }
                }
            }

            header("Location: index.php");
            exit;

        } else {
            echo "<script>alert('Correo o contraseña incorrectos'); window.location='login.php';</script>";
        }
    }
}

// condición para cierre de sesión
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = array(); 
    
    if (ini_get("session_use_cookies")) { 
        $params = session_get_cookie_params(); 
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy(); 
    header("Location: index.php"); 
    exit; 
}
?>