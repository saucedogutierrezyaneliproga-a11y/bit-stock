<?php
// verificar_direccion.php
session_start();
require_once 'inc/conexion.php';

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

$id_usr = $_SESSION['id'];

// Consultamos si el usuario ya tiene el campo dirección lleno
$sql = "SELECT direccion FROM usuario WHERE id_usuario = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $id_usr]);
$usuario = $stmt->fetch();

// Si el campo está vacío, es NULL o solo tiene espacios, lo mandamos a registrarla
if (!$usuario || empty(trim($usuario['direccion']))) {
    header("Location: direccion.php");
    exit;
} else {
    // Si ya la tiene registrada, pasa directo a la pasarela de pago
    header("Location: pago.php");
    exit;
}