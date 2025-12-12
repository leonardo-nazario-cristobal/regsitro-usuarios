// =============================================================
// Archivo: script.js — Registro de Clientes
// =============================================================
// Este archivo controla todo el funcionamiento dinámico:
// - Cargar clientes desde la API
// - Enviar nuevos registros
// - Validar fechas
// - Mostrar mensajes
// - Rellenar la tabla automáticamente
// =============================================================



// =============================================================
// 1. SELECTORES DEL DOM
// =============================================================
// Aquí conectamos JavaScript con los elementos del HTML.
// Es como “amarrar cables” entre el diseño y la lógica.

const form = document.getElementById('formRegistroUsuario');     // Formulario donde se registran los clientes
const tablaBody = document.querySelector('.tabla-clientes__cuerpo'); // Cuerpo <tbody> de la tabla
const mensajeDiv = document.getElementById('mensaje');            // Div donde mostramos mensajes de éxito/error



// URL de la API
// En este caso se usa el archivo api.php que procesa peticiones.
const API_URL = 'api.php'; 



// =============================================================
// 2. FUNCIÓN: mostrarMensaje()
// =============================================================
// Esta función sirve para mostrar mensajes estilo “toast”
// (desaparecen solos después de 5 segundos).
// Se usa para avisarle al usuario si todo salió bien o si hubo error.

/**
 * @param {string} texto - Mensaje a mostrar (texto del aviso)
 * @param {string} tipo - 'ok' o 'error'
 */
function mostrarMensaje(texto, tipo) {

   // Insertamos el texto dentro del div
   mensajeDiv.textContent = texto;

   // Le colocamos una clase que activa estilos neon
   mensajeDiv.className = `registro__mensaje ${tipo}`;

   // Temporizador para borrar el mensaje después de 5s
   setTimeout(() => {
      mensajeDiv.textContent = '';
      mensajeDiv.className = 'registro__mensaje';
   }, 5000);
}



// =============================================================
// 3. CARGAR Y RENDERIZAR CLIENTES
// =============================================================
// Esta función hace una petición a la API para obtener todos los
// clientes registrados en la base de datos y los dibuja dentro
// de la tabla.

function cargarClientes() {

   // Llamamos a la API usando action=load
   fetch(`${API_URL}?action=load`)
      .then(res => res.json())     // Convertimos la respuesta a JSON
      .then(data => {

         // Limpiamos la tabla antes de llenarla con nuevos datos
         tablaBody.innerHTML = "";

         // Validación por si la API responde vacío o con error
         if (!data.success || !Array.isArray(data.clientes)) {

            // Mostramos un mensaje dentro de la tabla
            tablaBody.innerHTML =
               '<tr class="tabla-clientes__fila">' +
               '<td colspan="8" class="tabla-clientes__dato" style="text-align: center;">' +
               'No hay clientes registrados aún.' +
               '</td></tr>';
            return;
         }

         // Si sí hay datos, recorremos cada cliente
         data.clientes.forEach((cliente, index) => {
            const fila = document.createElement("tr");
            fila.classList.add("tabla-clientes__fila");

            // Insertamos los datos dentro de la fila
            // Cada <td> coincide con una columna de la tabla HTML
            fila.innerHTML = `
               <td class="tabla-clientes__dato">${index + 1}</td>
               <td class="tabla-clientes__dato">${cliente.nombre || ''}</td>
               <td class="tabla-clientes__dato">${cliente.apellidopaterno || ''}</td>
               <td class="tabla-clientes__dato">${cliente.apellidomaterno || ''}</td>
               <td class="tabla-clientes__dato">${cliente.fechanacimiento || ''}</td>
               <td class="tabla-clientes__dato">${cliente.direccion || ''}</td>
               <td class="tabla-clientes__dato">${cliente.telefono || ''}</td>
               <td class="tabla-clientes__dato">${cliente.fecharegistro || ''}</td>
            `;

            tablaBody.appendChild(fila); // Agregamos la fila dentro del <tbody>
         });
      })
      .catch(err => {
         console.error('Error al cargar clientes:', err);
         mostrarMensaje('Error al cargar los datos.', 'error');
      });
}



// =============================================================
// 4. MANEJO DEL ENVÍO DEL FORMULARIO
// =============================================================
// Aquí definimos qué pasa cuando el usuario presiona “Guardar”.
// Validamos datos, convertimos fechas y enviamos la información a la API.

form.addEventListener("submit", function (event) {

   // Evitamos que el formulario recargue la página
   event.preventDefault();

   // Limpiamos mensajes viejos
   mensajeDiv.textContent = "";
   mensajeDiv.className = "registro__mensaje";

   // Capturamos todos los campos del formulario
   const formData = new FormData(form);



   // =============================================================
   // VALIDACIÓN Y REFORMATEO DE LA FECHA
   // =============================================================
   const fechaInput = formData.get('fechaNacimiento');

   if (fechaInput) {

      // Validamos que siga el formato dd/mm/aaaa
      const formatoCorrecto = /^\d{2}\/\d{2}\/\d{4}$/;

      if (!formatoCorrecto.test(fechaInput)) {
         mostrarMensaje('El formato debe ser dd/mm/aaaa.', 'error');
         return; // Se detiene el proceso
      }

      // Convertimos dd/mm/aaaa → aaaa-mm-dd (formato SQL)
      const partes = fechaInput.split('/');
      const fechaSQL = `${partes[2]}-${partes[1]}-${partes[0]}`;

      // Reemplazamos la fecha por la versión correcta
      formData.set('fechaNacimiento', fechaSQL);
   }

   // Extra: si el usuario lo deja vacío, PHP lo convierte a NULL



   // =============================================================
   // AGREGAMOS LA ACCIÓN Y FECHA DE REGISTRO
   // =============================================================
   formData.append('action', 'save');
   formData.append('fechaRegistro', new Date().toISOString().slice(0, 10)); 
   // → genera aaaa-mm-dd automático



   // =============================================================
   // PETICIÓN POST A LA API PARA GUARDAR EL CLIENTE
   // =============================================================
   fetch(API_URL, {
      method: "POST",
      body: formData
   })
   .then(res => res.json())
   .then(data => {

      // Validamos respuesta de la API
      if (!data.success) {
         mostrarMensaje(data.message || "Error al registrar cliente.", "error");
         return;
      }

      // Si todo salió bien
      mostrarMensaje(data.message, "ok");
      form.reset();     // Limpiamos formulario
      cargarClientes(); // Refrescamos tabla
   })
   .catch(err => {
      console.error(err);
      mostrarMensaje("Error al comunicar con la API.", "error");
   });
});



// =============================================================
// 5. INICIALIZACIÓN AL CARGAR LA PÁGINA
// =============================================================
// Cuando el documento ya está cargado, mostramos la tabla.

document.addEventListener("DOMContentLoaded", cargarClientes);