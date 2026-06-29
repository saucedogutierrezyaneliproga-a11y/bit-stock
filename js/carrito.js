// js/carrito.js

// Funcion global para mandar productos al carrito desde index.php o cualquier otra pantalla
// Asegúrate de que tu función de agregar capture el data.msg del error
function agregarAlCarrito(idProducto, cantidad = 1) {
    let datos = new FormData();
    datos.append('id_prd', idProducto);
    datos.append('cantidad', cantidad);

    fetch('api_carrito.php', {
        method: 'POST',
        body: datos
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            alert(data.msg);
            location.reload(); // Actualiza para ver el globo del carrito incrementar
        } else {
            // Aquí pintará el mensaje exacto diciendo cuántos te quedan de stock en Neon.tech
            alert(data.msg); 
        }
    })
    .catch(err => console.error("Error:", err));
}

function actualizarCantidad(idProducto, operacion) {
    let datos = new FormData();
    datos.append('id_prd', idProducto);
    datos.append('operacion', operacion);

    // Mandamos la petición asíncrona al archivo que creamos en la raíz
    fetch('api_cantidad_carrito.php', {
        method: 'POST',
        body: datos
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            // Recargamos el carrito para que se recalculen los subtotales y totales al instante
            location.reload();
        } else {
            alert("No se pudo actualizar la cantidad: " + data.msg);
        }
    })
    .catch(err => {
        console.error("Error en Fetch:", err);
        alert("Error de conexión con el servidor.");
    });
}

// Funcion para eliminar por completo un artículo del listado (Baja)
function eliminarDelCarrito(idProducto) {
    if (confirm("¿Estás seguro de que deseas eliminar este artículo de tu carrito?")) {
        
        // Preparamos los datos en formato FormData para PHP
        let datos = new FormData();
        datos.append('id_prd', idProducto);

        // Fetch hacia la raíz del proyecto
        fetch('api_eliminar_carrito.php', {
            method: 'POST',
            body: datos
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                // Si la base de datos lo borró con éxito, recargamos la página para ver los cambios
                location.reload();
            } else {
                alert("Error al eliminar: " + data.msg);
            }
        })
        .catch(err => {
            console.error("Error en la petición:", err);
            alert("No se pudo conectar con el servidor.");
        });
    }
}