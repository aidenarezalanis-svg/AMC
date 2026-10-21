# App Médica — Sistema Clínico Integral (versión PHP)

Conversión a **PHP 8 + MySQL/MariaDB (PDO)** del proyecto React "MedicaCloud", conectada a la base
real **`app_medica_citas`** (la misma que usa `PacienteDAO.java`). Conserva el diseño original:
Tailwind, modo oscuro, paletas, tipografías, búsqueda Ctrl+K y las 7 vistas.

**No se modificó la estructura de la base**: el PHP usa sus tablas, procedimientos y triggers tal cual.

## Requisitos

- XAMPP (o Laragon/WAMP) con PHP 8.0+ y MariaDB/MySQL
- Conexión a internet en el navegador (Tailwind, íconos Lucide y Google Fonts se cargan por CDN)

## Instalación (XAMPP)

1. Copia la carpeta `app_medica` dentro de `C:\xampp\htdocs\`.
2. Enciende **Apache** y **MySQL**.
3. Si todavía no tienes la base, en phpMyAdmin crea `app_medica_citas` e importa
   `database/app_medica_citas.sql`. Si ya la tienes (la usa tu proyecto Java), no hagas nada.
4. Ajusta usuario/contraseña en `config/config.php` si no usas `root` sin contraseña.
5. Entra a `http://localhost/app_medica/`.

> Los procedimientos y triggers del volcado escriben los nombres de tabla con mayúscula
> (`Pacientes`, `Citas`). En Windows funciona porque MySQL ignora mayúsculas en nombres de tabla;
> en Linux habría que activar `lower_case_table_names=1`.

## Inicio de sesión

Todo el panel exige iniciar sesión. Entra por `http://localhost/app_medica/` y te llevará a `login.php`.

| Quién | Con qué entra | A dónde llega |
|---|---|---|
| Personal (rol Medico, Recepcionista o Administrador en `usuarios`) | Correo de la tabla `medicos` o `personal` | Panel completo (`index.php`) |
| Paciente | Correo o CURP de la tabla `pacientes` | `mi_espacio.php`: sus datos y sus citas |

- La contraseña se valida contra `usuarios.contrasena_hash` con `password_verify` (bcrypt).
- Las contraseñas del volcado (`HASH$demo$...`) están en texto plano: funcionan una vez y al entrar
  se convierten solas a bcrypt. Para tener cuentas de prueba conocidas, ejecuta
  **`database/contrasenas_demo.sql`** (contraseña `Apex2026`):
  - Administrador: `h.villareal@clinica.com`
  - Recepcionista: `d.cruz@clinica.com`
  - Médico: `r.solis@clinica.com`
  - Paciente: `alberto.sanchez@correo.com` o CURP `SALA781210HDFM01`
- Solo entran cuentas con estatus Activo (en `usuarios` y en `pacientes`).
- Tras 5 intentos fallidos se bloquea el login 2 minutos (por sesión del navegador).
- `registro.php`: alta pública de pacientes con `sp_registrar_paciente`. La contraseña pide
  mínimo 8 caracteres con letras y números.
- Cerrar sesión es un botón (POST con token), en el menú del perfil o en `mi_espacio.php`.
- Pendiente: a los pacientes dados de alta desde recepción (Agendar) se les crea una contraseña
  aleatoria, así que no pueden entrar hasta que se implemente "restablecer contraseña".

## Foto de perfil (AMC-16 y AMC-17)

1. Ejecuta una vez **`database/amc16_foto_perfil.sql`**: agrega la columna `pacientes.foto_perfil`.
   Mientras no se ejecute, la app funciona igual y al subir una foto avisa que falta este paso.
2. **El paciente** sube o cambia su foto en `mi_espacio.php` (la historia AMC-5 es "Como paciente…").
   El id sale de su sesión, así que no puede cambiar la foto de otra persona.
3. **El personal** también puede hacerlo desde la ficha del paciente en **Pacientes**.

- **AMC-16 (almacenamiento):** la imagen se guarda en `uploads/fotos/` con un nombre aleatorio
  y la base guarda solo ese nombre. La carpeta se crea sola con un `.htaccess` que bloquea el acceso
  directo; las fotos se entregan con `foto.php`, que deja ver cada foto solo al personal y al propio
  paciente. La foto nueva reemplaza a la anterior (el archivo viejo se borra) y el cambio queda en
  `historial_cambios_pacientes`.
- **AMC-17 (carga):** solo JPG o PNG de máximo 2 MB. El navegador avisa al instante y muestra una
  vista previa (`assets/js/foto.js`); el servidor vuelve a validar leyendo el contenido real del
  archivo, así que un `.php` renombrado a `.jpg` se rechaza.
- Sin foto se muestran las iniciales del paciente.

## Editar datos personales (AMC-4)

En `mi_espacio.php`, el paciente pulsa **"Editar mis datos"** y puede cambiar nombre, apellidos,
fecha de nacimiento, teléfono y correo. No requiere cambios en la base.

- Obligatorios: nombre, apellido paterno, fecha de nacimiento, teléfono y correo.
- Validaciones: nombre solo con letras; teléfono de 10 dígitos; correo válido y que no use otra
  cuenta (paciente, médico o personal, porque el login busca en las tres); fecha no futura.
- Si algo falla, el formulario queda abierto con lo capturado y el error junto a cada campo.
- Al guardar se actualiza `pacientes` y el cambio queda en `historial_cambios_pacientes`
  (ej. "Teléfono: '7772221001' a '7779991234'"). Si cambia el correo, se usa el nuevo para entrar.
- El id sale de la sesión: un paciente no puede editar datos de otro. La CURP solo la cambia recepción.
- Archivos: `mi_datos.php` (guarda) e `includes/perfil.php` (validación y actualización).

## Cómo usa la base cada pantalla

| Pantalla | Tablas / objetos |
|---|---|
| Agendar cita | `horarios_disponibles` (busca el turno o lo crea) → **`sp_agendar_cita`**. Los triggers `trg_before_cita_insert` / `trg_after_cita_insert` rechazan horarios pasados u ocupados y marcan el turno como *Ocupado*. |
| Registrar paciente | **`sp_registrar_paciente`** (crea `usuarios` + `pacientes` en una transacción). La contraseña se guarda cifrada con `password_hash`. |
| Citas | Confirmar / completar con `UPDATE citas`; cancelar con **`sp_cancelar_cita`** (el trigger `trg_after_cita_cancelada` libera el horario). |
| Pacientes | Baja lógica `estatus = 'Inactivo'` (igual que `eliminarPaciente()` del DAO) y reactivación. |
| Médicos | `medicos` + `medico_especialidad` + `medico_consultorio` (días de atención) + horarios libres. |
| Inventario | `medicamentos`. "Reordenar" registra una `compra` + `detalle_compra` con el proveedor elegido y suma `stock_actual`. |
| Dashboard / Reportes | `venta` (facturación del día y por mes), `citas`, `seguimiento_clientes`. |
| Encabezado | La sede es la tabla `consultorios`; el usuario mostrado sale de `personal` + `usuarios` (rol Administrador). |

Ajustes en `config/config.php`:
- `STOCK_MINIMO` (20): la tabla `medicamentos` no tiene columna de mínimo, así que se usa este valor.
- `CANTIDAD_REORDEN` (50): unidades que pide el botón Reordenar.

## Estructura

```
app_medica/
├── index.php              Enrutador: index.php?vista=dashboard|agendar|pacientes|citas|medicos|inventario|reportes
├── acciones.php           Formularios POST (llama a los procedimientos almacenados)
├── api.php                JSON: búsqueda Ctrl+K, buscador de pacientes, turnos de un médico
├── login.php, registro.php, salir.php, mi_espacio.php   Acceso y página del paciente
├── foto.php, mi_foto.php  Entrega de fotos con permisos y carga de foto del paciente
├── mi_datos.php           Guarda los datos personales que edita el paciente (AMC-4)
├── config/config.php
├── includes/              bootstrap (PDO, CSRF, utilidades), datos.php (todas las consultas), layout y componentes
├── vistas/                Una vista por archivo
├── assets/css, assets/js  Estilos, motor de temas, interacciones y lógica de Agendar
├── uploads/fotos/         Fotos de perfil (se crea sola al subir la primera)
└── database/              app_medica_citas.sql (tu volcado), contrasenas_demo.sql y amc16_foto_perfil.sql
```

## Notas

- Las notificaciones de la campana se generan a partir de los datos (medicamentos agotados,
  stock bajo, citas del día, compras y visitas recientes) porque la base no tiene tabla para ellas.
- PHP y Java comparten la misma base, así que lo que registres en uno aparece en el otro.
