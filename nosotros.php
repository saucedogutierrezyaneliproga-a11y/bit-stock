<?php
// nosotros.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'inc/encabezado.php';
?>

<link rel="stylesheet" href="css/estilos.css">

<main class="cnt-nosotros">
    <section class="nosotros-hero">
        <h1>Sobre Nosotros</h1>
        <p class="subtitulo">El origen de Bit-Stock</p>
    </section>

    <section class="nosotros-contenido">
        <div class="tarjeta-info">
            <h2>¿Quiénes somos?</h2>
            <p>
                <strong>Bit-Stock</strong> es un proyecto académico de e-commerce enfocado en la distribución y gestión de componentes de hardware y tecnología. Este sitio web fue diseñado, estructurado y programado desde cero por estudiantes de ingeniería, funcionando como una plataforma integradora para la automatización de inventarios, sesiones seguras y transacciones asíncronas.
            </p>
        </div>

        <div class="tarjeta-info">
            <h2>Nuestra Infraestructura</h2>
            <p>
                El sistema utiliza una arquitectura cliente-servidor robusta. En el backend, implementamos <strong>PHP</strong> transaccional y seguridad criptográfica para las credenciales. Los datos y el stock de componentes residen en una base de datos relacional de alta disponibilidad mediante <strong>PostgreSQL</strong> en la nube (Neon.tech), interactuando dinámicamente con el frontend a través de <strong>JavaScript (Fetch API)</strong> y un diseño responsivo unificado en <strong>CSS externo</strong>.
            </p>
        </div>
    </section>
</main>

<?php require_once 'inc/pie.php'; ?>