
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"> <title>Registro - Bit-Stock</title>
    <link rel="stylesheet" href="css/estilos.css"> </head>
<body>

    <div class="cnt-centro">
        <div class="caja-frm">
            
            <div style="text-align: center; margin-bottom: 10px;">
                <img src="img/icono_usuario.jpg" alt="Usuario" width="30" class="imgusuario"> 
            </div>

            <h2>Registrarse</h2>

            <form id="frm-reg" action="auth.php" method="POST">
                
                <input type="hidden" name="accion" value="registro">

                <div class="grp">
                    <label>Nombre</label>
                    <input type="text" name="nom" id="nom" required>
                </div>

                <div class="grp">
                    <label>Email</label>
                    <input type="email" name="eml" id="eml" required>
                </div>

                <div class="grp">
                    <label>Contraseña</label>
                    <input type="password" name="pwd" id="pwd" required>
                </div>

                <div class="grp">
                    <label>Confirmar contraseña</label>
                    <input type="password" id="pwd2" required>
                </div>

                <button type="submit" class="btn-m">Registrarse</button>

                <a href="login.php" class="lnk">Ya tienes una cuenta? Inicia sesión aquí</a>
                
                </form>
        </div>
    </div>

    <script>
        // capturamos el formulario por su ID
        const frm = document.getElementById('frm-reg');
        
        // interceptamos el evento de envio (submit)
        frm.addEventListener('submit', function(e) {
            // capturamos el valor de las contraseñas
            let p1 = document.getElementById('pwd').value;
            let p2 = document.getElementById('pwd2').value;

            // si no son iguales
            if (p1 !== p2) {
                e.preventDefault(); // detenemos el envio del formulario
                alert("Las contraseñas no coinciden. Intenta de nuevo."); // mostramos error
            }
        });
    </script>

</body>
</html>