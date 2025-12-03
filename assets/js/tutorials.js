const TUTORIAL_DASHBOARD = {
  steps: [
    { selector: '.topbar h1', title: '¡Bienvenido al Dashboard!', description: 'Este es el panel principal de administración del parqueadero inteligente. Aquí podrás ver un resumen completo de todas las operaciones, estadísticas en tiempo real, ocupación de puestos e ingresos.' },
    { selector: '.stats-grid', title: 'Tarjetas de Estadísticas Principales', description: 'En esta sección encontrarás los indicadores clave en tarjetas visuales: puestos totales, puestos libres, puestos ocupados, y movimientos del día.' },
    { selector: '.stat-card-value', title: 'Valores de Estadísticas', description: 'Cada número en grande muestra un indicador importante. Debajo encontrarás información complementaria como porcentajes y detalles adicionales.' },
    { selector: 'table thead', title: 'Tabla de Movimientos', description: 'La tabla muestra los últimos movimientos registrados con columnas: Puesto, Tipo de vehículo, Placa, Entrada, Salida, Total, Empleado y Estado.' },
    { selector: 'table tbody', title: 'Historial de Entradas y Salidas', description: 'Cada fila representa un movimiento: la información del vehículo, puesto ocupado, horarios de entrada/salida, y estado actual de la operación.' },
    { selector: '.card', title: 'Tarjetas de Información', description: 'Todas las secciones están organizadas en tarjetas: distribución por tipo de vehículo, últimos movimientos, y accesos rápidos a otras secciones.' },
    { selector: '.btn.btn-primary', title: 'Botones de Acceso Rápido', description: 'En la parte inferior hay botones para navegar rápidamente a Mapa, Gestionar Puestos, Ver Reportes y Configuraciones del sistema.' }
  ]
};

const TUTORIAL_MAPA = {
  steps: [
    { selector: '.topbar h1', title: 'Visualización del Mapa del Parqueadero', description: 'Esta es la vista interactiva que te muestra la distribución física exacta de todos los puestos de estacionamiento disponibles en tu parqueadero.' },
    { selector: '.card', title: 'Área del Mapa', description: 'El área principal muestra una representación visual de todos los puestos. Cada celda representa un puesto: verde = disponible, rojo = ocupado, gris = mantenimiento.' },
    { selector: '.card:first-of-type', title: 'Visualización de Puestos', description: 'Aquí está el layout completo del parqueadero. Puedes ver el estado de cada puesto en tiempo real. Haz clic en un puesto para ver detalles del vehículo estacionado.' },
    { selector: '.btn-primary, .btn-secondary', title: 'Controles de Navegación', description: 'Usa estos botones para ampliar (zoom in), reducir (zoom out), o actualizar la vista en tiempo real del estado de todos los puestos.' },
    { selector: 'table', title: 'Tabla de Detalles', description: 'Aquí aparece información adicional sobre puestos específicos o búsqueda de vehículos en el parqueadero.' },
    { selector: '.card-body', title: 'Leyenda y Controles', description: 'En esta sección encontrarás la leyenda de colores, filtros para mostrar solo ciertos tipos de puestos, y opciones de búsqueda.' }
  ]
};

const TUTORIAL_PUESTOS = {
  steps: [
    { selector: '.topbar h1', title: 'Gestión Completa de Puestos de Estacionamiento', description: 'Desde esta sección administras todos los puestos disponibles en tu parqueadero. Puedes crear nuevos puestos, editar existentes, eliminarlos, y cambiar sus estados.' },
    { selector: '.btn.btn-success', title: 'Botón Crear Nuevo Puesto', description: 'Haz clic en este botón para abrir un formulario donde podrás registrar un nuevo puesto con su código, sector, dimensiones y características específicas.' },
    { selector: '.form-control', title: 'Barra de Búsqueda Rápida', description: 'Usa esta barra para buscar puestos específicos ingresando su número, código o sector. La búsqueda se realiza en tiempo real mientras escribes.' },
    { selector: 'table thead', title: 'Encabezados de la Tabla', description: 'Las columnas muestran: ID del Puesto, Código, Sector, Tipo de Vehículo, Dimensiones, Estado Actual, y Acciones disponibles.' },
    { selector: 'table tbody', title: 'Listado Completo de Puestos', description: 'Cada fila representa un puesto. Aquí visualizas toda la información relevante de cada puesto: su ubicación, tipo permitido, estado y opciones de edición.' },
    { selector: '.btn-sm.btn-secondary', title: 'Botón Ver Detalles', description: 'Haz clic en este botón con el icono de ojo para ver la información completa y detallada de un puesto específico en una ventana emergente.' }
  ]
};

const TUTORIAL_EMPLEADOS = {
  steps: [
    { selector: '.topbar h1', title: 'Administración de Personal del Parqueadero', description: 'Aquí administras el registro de todos los empleados del parqueadero: crear nuevos perfiles, editar información, cambiar roles, resetear contraseñas y gestionar estados.' },
    { selector: '.stats-grid', title: 'Estadísticas de Empleados', description: 'Estas tarjetas muestran: Total de empleados registrados, Empleados activos en el sistema, y Empleados inactivos o dados de baja.' },
    { selector: '.stat-card', title: 'Indicadores de Personal', description: 'Cada tarjeta muestra un indicador importante con un icono visual para fácil identificación del estado del personal.' },
    { selector: '.btn.btn-success', title: 'Registrar Nuevo Empleado', description: 'Haz clic para abrir el formulario de creación. Allí ingresarás nombre, usuario, contraseña, correo y otros datos del nuevo empleado.' },
    { selector: 'table thead', title: 'Estructura de la Tabla', description: 'Las columnas son: ID, Nombre Completo, Usuario, Fecha de Creación, Estado (Activo/Inactivo), y Acciones disponibles.' },
    { selector: 'table tbody', title: 'Listado de Empleados', description: 'Cada fila muestra un empleado con sus datos básicos. Los empleados activos permiten todas las acciones, mientras los inactivos están limitados.' },
    { selector: '.btn-sm.btn-secondary', title: 'Botón Editar Empleado', description: 'Haz clic para modificar datos del empleado: nombre, usuario, email, teléfono, rol y información de contacto.' },
    { selector: '.btn-sm.btn-primary', title: 'Resetear Contraseña', description: 'Este botón abre una opción para que el empleado cambie su contraseña de acceso o para asignar una nueva contraseña temporal.' },
    { selector: 'button.btn-sm', title: 'Botones de Acciones', description: 'Utiliza estos botones para editar, resetear contraseña, desactivar o reactivar empleados según sea necesario.' }
  ]
};

const TUTORIAL_TARIFAS = {
  steps: [
    { selector: '.topbar h1', title: 'Gestión de Tarifas', description: 'En esta sección configuras los precios que se cobran por cada tipo de vehículo. Las tarifas se aplican automáticamente en el registro de salida.' },
    { selector: '.card:first-of-type', title: 'Configuración de Cobro', description: 'Aquí ves la configuración actual: minutos de redondeo, tarifa mínima y moneda de operación. Estos parámetros afectan cómo se calculan los cobros.' },
    { selector: '.card:nth-of-type(2)', title: 'Tarifas por Tipo de Vehículo', description: 'Configura el precio por hora para cada tipo: Motos, Carros, Camiones. Estos son los precios que se aplican cuando registras salidas.' },
    { selector: 'form input[type="number"]:first-of-type', title: 'Precio Moto por Hora', description: 'Ingresa el precio que se cobra por cada hora que una moto permanece estacionada. Ej: $5000 COP.' },
    { selector: 'form input[type="number"]:nth-of-type(2)', title: 'Precio Carro por Hora', description: 'Ingresa el precio por hora para carros. Este debe ser generalmente mayor al de motos debido a mayor espacio ocupado.' },
    { selector: 'form input[type="number"]:nth-of-type(3)', title: 'Precio Camión por Hora', description: 'Ingresa el precio por hora para camiones. Normalmente es la tarifa más alta porque ocupan mayor espacio.' },
    { selector: 'button[type="submit"]', title: 'Guardar Cambios en Tarifas', description: 'Haz clic para guardar todas las tarifas modificadas. El sistema usará estos nuevos precios inmediatamente en próximos cobros.' },
    { selector: '.card:last-of-type', title: 'Historial de Cambios', description: 'Muestra un registro de las últimas modificaciones realizadas a las tarifas: fecha, usuario que realizó el cambio y detalles de la acción.' }
  ]
};

const TUTORIAL_CONFIGURACIONES = {
  steps: [
    { selector: '.topbar h1', title: 'Configuración General del Sistema', description: 'Aquí ajustas los parámetros principales del parqueadero: nombre, dirección, horarios, capacidad total y moneda de operación.' },
    { selector: '.card:first-of-type', title: 'Datos Generales de la Empresa', description: 'Ingresa o modifica la información básica: nombre oficial, dirección física, teléfono, email y sitio web (opcional) del parqueadero.' },
    { selector: '.card:nth-of-type(2)', title: 'Configuración de Cobro', description: 'Aquí configuras: moneda de operación, tarifa mínima en horas, y los minutos de redondeo para el cálculo de cobros.' },
    { selector: '.card:nth-of-type(3)', title: 'Capacidad y Horarios', description: 'Define la capacidad total de puestos, horarios de apertura y cierre del parqueadero. Estos valores afectan límites y restricciones del sistema.' },
    { selector: 'button[type="submit"]', title: 'Guardar Configuración', description: 'Haz clic para guardar todos los cambios realizados. El sistema se actualizará inmediatamente con la nueva configuración.' }
  ]
};

const TUTORIAL_REPORTES = {
  steps: [
    { selector: '.topbar h1', title: 'Generador de Reportes y Análisis', description: 'Aquí consultas y analizas información completa sobre operaciones del parqueadero: ingresos, ocupación, movimientos y estadísticas.' },
    { selector: '.card:first-of-type', title: 'Filtros de Período', description: 'Selecciona el mes y año para generar reportes de ese período específico. El sistema analizará todos los datos de ese rango temporal.' },
    { selector: 'select:first-of-type', title: 'Seleccionar Mes', description: 'Elige el mes (enero a diciembre) para el cual deseas generar el reporte con análisis de datos completo.' },
    { selector: 'select:nth-of-type(2)', title: 'Seleccionar Año', description: 'Elige el año (período anual) para el análisis. Puedes comparar datos de diferentes años.' },
    { selector: 'button[type="submit"]', title: 'Generar Reporte', description: 'Haz clic para procesar y mostrar todos los datos del período seleccionado en forma de tablas y estadísticas.' },
    { selector: '.card:nth-of-type(2)', title: 'Tabla de Reportes', description: 'Aquí aparecen los datos generados: información por tipo de vehículo, ingresos diarios, o movimientos según lo seleccionado.' }
  ]
};

const TUTORIAL_ENTRADA = {
  steps: [
    { selector: '.topbar h1', title: 'Registro de Entrada de Vehículos', description: 'Aquí registras cada vehículo que ingresa al parqueadero. El sistema asigna automáticamente un puesto disponible y registra la hora de entrada.' },
    { selector: '.stats-grid', title: 'Estado de Disponibilidad', description: 'Muestra en tiempo real la cantidad de puestos disponibles y ocupados. Ayuda a saber rápidamente si hay espacio en el parqueadero.' },
    { selector: '.card:nth-of-type(2)', title: 'Formulario de Registro', description: 'Aquí ingresas los datos del vehículo que entra: tipo (moto/carro/camión) y placa. El sistema completa el resto automáticamente.' },
    { selector: 'select', title: 'Tipo de Vehículo', description: 'Selecciona si es moto, carro o camión. El sistema filtrará puestos disponibles según el tipo seleccionado.' },
    { selector: 'input[type="text"]', title: 'Número de Placa', description: 'Ingresa el número de placa del vehículo sin espacios ni caracteres especiales. Máximo 8 caracteres.' },
    { selector: 'button[type="submit"]', title: 'Registrar Entrada', description: 'Haz clic para registrar la entrada. El sistema asignará automáticamente un puesto disponible y generará un recibo.' },
    { selector: '.card:last-of-type', title: 'Últimas Entradas', description: 'Aquí aparece el historial de las últimas entradas registradas para referencia rápida.' }
  ]
};

const TUTORIAL_SALIDA = {
  steps: [
    { selector: '.topbar h1', title: 'Procesamiento de Salida de Vehículos', description: 'Aquí procesas la salida de vehículos: registras el número de placa, el sistema calcula el tiempo de estancia, aplica la tarifa y genera el recibo de cobro.' },
    { selector: '.stats-grid', title: 'Estado Actual del Parqueadero', description: 'Muestra en tiempo real cuántos puestos están disponibles y cuántos ocupados en el momento.' },
    { selector: '.card:nth-of-type(2)', title: 'Formulario de Procesamiento de Salida', description: 'Ingresa el número de placa del vehículo que sale. El sistema buscará automáticamente el registro de entrada correspondiente.' },
    { selector: 'input[type="text"]', title: 'Búsqueda por Placa', description: 'Escribe el número de placa del vehículo. El sistema localizará su registro de entrada y mostrará la información.' },
    { selector: 'select', title: 'Seleccionar Tarifa de Cobro', description: 'Elige la tarifa a aplicar: normal por hora, tarifa especial, o cualquier otra disponible según lo configurado.' },
    { selector: 'input[type="number"]:first-of-type', title: 'Monto Total a Cobrar', description: 'El sistema calcula automáticamente el monto según el tiempo de estancia y la tarifa seleccionada.' },
    { selector: 'input[type="number"]:nth-of-type(2)', title: 'Dinero Recibido del Cliente', description: 'Ingresa el monto de dinero que paga el cliente. Puede ser igual o mayor al monto total.' },
    { selector: 'input[type="number"]:nth-of-type(3)', title: 'Cambio a Entregar', description: 'Se calcula automáticamente el cambio que debes devolver al cliente.' },
    { selector: 'button[type="submit"]', title: 'Procesar Salida', description: 'Haz clic para confirmar la salida, registrar el pago, generar recibo y liberar el puesto para otro vehículo.' }
  ]
};

const TUTORIAL_HISTORIAL = {
  steps: [
    { selector: '.topbar h1', title: 'Historial Completo de Operaciones', description: 'Visualiza el registro detallado de TODAS las entradas y salidas de vehículos con información completa de fechas, horas y montos cobrados.' },
    { selector: '.card:first-of-type', title: 'Filtros de Búsqueda Avanzada', description: 'Usa estos campos para filtrar el historial según tus necesidades: busca por placa, propietario, fechas, tipo de vehículo, etc.' },
    { selector: 'input[type="text"]:first-of-type', title: 'Buscar por Número de Placa', description: 'Ingresa una placa para encontrar rápidamente todos los movimientos registrados de ese vehículo.' },
    { selector: 'input[type="text"]:nth-of-type(2)', title: 'Buscar por Propietario', description: 'Ingresa el nombre del cliente para ver todo su historial de estancias y gasto total acumulado.' },
    { selector: 'input[type="date"]:first-of-type', title: 'Fecha de Inicio del Filtro', description: 'Selecciona la fecha desde la cual comienza el período de búsqueda en el historial.' },
    { selector: 'input[type="date"]:last-of-type', title: 'Fecha de Fin del Filtro', description: 'Selecciona la fecha final para acotar el rango de búsqueda en el historial.' },
    { selector: 'select:first-of-type', title: 'Filtrar por Tipo de vehiculo', description: 'Muestra solo las operaciones relacionadas con el tipo de vehiculo que hay seleccionado' },
    { selector: '.btn.btn-primary', title: 'Aplicar Filtros', description: 'Haz clic para aplicar todos los criterios de búsqueda seleccionados y actualizar la tabla.' },
    { selector: 'table thead', title: 'Encabezados del Historial', description: 'Muestra las columnas: Placa, Propietario, Entrada, Salida, Puesto, Tiempo Estancia, Tarifa, Monto, Empleado, Estado.' },
    { selector: 'table tbody', title: 'Registros del Historial', description: 'Cada fila representa un ciclo completo de entrada, estancia y salida de un vehículo con todos los detalles.' }
  ]
};

function initTutorial() {
  const url = window.location.pathname;
  let config = null;

  if (url.includes('dashboard.php')) config = TUTORIAL_DASHBOARD;
  else if (url.includes('mapa.php')) config = TUTORIAL_MAPA;
  else if (url.includes('puestos.php')) config = TUTORIAL_PUESTOS;
  else if (url.includes('empleados.php')) config = TUTORIAL_EMPLEADOS;
  else if (url.includes('tarifas.php')) config = TUTORIAL_TARIFAS;
  else if (url.includes('configuraciones.php')) config = TUTORIAL_CONFIGURACIONES;
  else if (url.includes('reportes.php')) config = TUTORIAL_REPORTES;
  else if (url.includes('entrada.php')) config = TUTORIAL_ENTRADA;
  else if (url.includes('salida.php')) config = TUTORIAL_SALIDA;
  else if (url.includes('historial.php')) config = TUTORIAL_HISTORIAL;

  if (config) {
    window.currentTutorial = new InteractiveTutorial(config);
  }
}

function startTutorial() {
  if (window.currentTutorial) {
    window.currentTutorial.start();
  }
}

document.addEventListener('DOMContentLoaded', initTutorial);
