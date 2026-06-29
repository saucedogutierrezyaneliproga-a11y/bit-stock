<?php
// conexion.php

$host = "ep-crimson-pond-apqvmcul.c-7.us-east-1.aws.neon.tech"; 
$port = "5432";
$dbname = "neondb";
$user = "neondb_owner";
$password = "npg_F1wMoTuDWh7U"; 

try {
    // Extraemos la primera parte de tu host (el ID del endpoint)
    // En tu caso es: ep-crimson-pond-apqvmcul
    $endpoint_id = "ep-crimson-pond-apqvmcul";

    // Agregamos ';options=endpoint=' al final de la cadena de conexión
    $connection_string = "host=$host port=$port dbname=$dbname user=$user password=$password sslmode=require;options=endpoint=$endpoint_id";
    
    $pdo = new PDO("pgsql:$connection_string");
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Error real de conexión a Neon: " . $e->getMessage());
}
?>