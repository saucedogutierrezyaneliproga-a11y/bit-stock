<?php
// auth.php

// iniciamos la sesion para poder guardar datos del usuario logueado
session_start();

// importamos la conexion a la base de datos (asegurate de tener tu conexion.php listo)
require_once 'inc/conexion.php';

// verificamos que lleguen datos por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // capturamos la accion (registro o login)
    $acc = $_POST['accion'];

    // === FLUJO DE REGISTRO ===
    if ($acc === 'registro') {
        // capturamos variables limpiando espacios extra
        $nom = trim($_POST['nom']);
        $eml = trim($_POST['eml']);
        $pwd = $_POST['pwd'];

        // encriptamos la contrasena
        $hash = password_hash($pwd, PASSWORD_DEFAULT);

        try {
            // preparamos insercion (Alta en BD)
            $sql = "INSERT INTO usuario (nombre, correo, contrasena) VALUES (:n, :e, :c)";
            $stmt = $pdo->prepare($sql);
            
            // ejecutamos pasando los datos
            $stmt->execute([
                ':n' => $nom,
                ':e' => $eml,
                ':c' => $hash
            ]);

            // redirigimos al login con exito
            echo "<script>alert('Registro exitoso'); window.location='login.php';</script>";

        } catch (PDOException $e) {
            // si el correo ya existe, mostrara error
            echo "<script>alert('Error: El correo ya existe'); window.history.back();</script>";
        }
    }

    // === FLUJO DE LOGIN ===
    else if ($acc === 'login') {
        $eml = trim($_POST['eml']);
        $pwd = $_POST['pwd'];

        // buscamos al usuario por correo (Consulta a BD)
        $sql = "SELECT id_usuario, nombre, contrasena, rol FROM usuario WHERE correo = :e";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':e' => $eml]);
        
        // extraemos el registro
        $usr = $stmt->fetch();

        // si existe el usuario y la contrasena coincide con el hash
        if ($usr && password_verify($pwd, $usr['contrasena'])) {
            
            // guardamos datos en la sesion global
            $_SESSION['id'] = $usr['id_usuario'];
            $_SESSION['nom'] = $usr['nombre'];
            $_SESSION['rol'] = $usr['rol'];

            // redirigimos a la pagina principal (catalogo)
            header("Location: index.php");
            exit;

        } else {
            // credenciales invalidas
            echo "<script>alert('Correo o contraseña incorrectos'); window.location='login.php';</script>";
        }
    }
}
?>