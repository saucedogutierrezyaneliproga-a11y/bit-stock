// js/carrito.js

// Funcion global para mandar productos al carrito desde index.php o cualquier otra pantalla
function agregarAlCarrito(idProducto, cantidad) {
    let datos = new FormData();
    datos.append('acc', 'add');
    datos.append('id_prd', idProducto);
    datos.append('cant', cantidad);

    // mandamos los datos en segundo plano a la API de PHP
    fetch('inc/api_carrito.php', {
        method: 'POST',
        body: datos
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            alert("Componente añadido al carrito con éxito.");
            location.reload(); // recargamos sutilmente para actualizar contadores
        } else {
            alert(data.msg); // muestra error (ej: si no se ha logueado)
        }
    })
    .catch(err => console.error("Error:", err));
}

// Funcion para cambiar la cantidad directamente desde la pantalla del carrito
function actualizarCantidad(idProducto, nuevaCantidad) {
    if(nuevaCantidad < 1) return;

    let datos = new FormData();
    datos.append('acc', 'update');
    datos.append('id_prd', idProducto);
    datos.append('cant', nuevaCantidad);

    fetch('api_carrito.php', {
        method: 'POST',
        body: datos
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            location.reload(); // refresca los datos y recalcula totales en pantalla
        }
    });
}

// Funcion para eliminar por completo un artículo del listado (Baja)
function eliminarDelCarrito(idProducto) {
    if(!confirm("¿Estás seguro de que quieres quitar este componente de tu carrito?")) return;

    let datos = new FormData();
    datos.append('acc', 'del');
    datos.append('id_prd', idProducto);

    fetch('api_carrito.php', {
        method: 'POST',
        body: datos
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            location.reload();
        }
    });
}