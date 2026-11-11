# RopaLive

Tienda de ropa hecha con PHP y MySQL. Incluye catálogo con filtros por género, colecciones, galerías de imágenes, carrito y compra con datos de entrega, historial de pedidos y panel administrativo con roles.

## Carpetas principales

```text
Ropa_live/
|-- admin/
|   |-- clientes/           CRUD de clientes
|   |-- colecciones/        CRUD de colecciones y asignación de productos
|   |-- pedidos/            Pedidos y estados
|   |-- productos/          CRUD de productos
|   |-- cuenta.php          Datos y contraseña del administrador
|   `-- index.php           Resumen y accesos del panel
|-- assets/
|   |-- css/                Estilos de tienda, formularios, carrito y panel
|   |-- images/             Imágenes de portada y contenido inicial
|   `-- uploads/            Imágenes cargadas desde los formularios
|-- config/Conexion.php     Conexión con MySQL
|-- database/
|   |-- database.sql        Esquema para instalaciones nuevas
|   `-- actualizar.sql      Migración desde el esquema anterior
|-- includes/               Funciones compartidas y control de acceso
|-- colecciones.php         Listado público de colecciones
|-- coleccion.php           Prendas agrupadas por género
|-- index.php               Página principal y catálogo
|-- Login.php               Inicio de sesión
|-- registro.php            Registro de clientes
|-- carrito.php             Carrito y checkout
|-- detalle.php             Detalle y galería del producto
`-- mi_cuenta.php           Perfil e historial del cliente
```

## Requisitos

- PHP 8.0 o posterior, con `pdo_mysql` y `fileinfo`.
- MySQL 8 o MariaDB.
- Apache u otro servidor que pueda ejecutar PHP y escribir en `assets/uploads`.

## Instalación nueva con XAMPP

1. Copia esta carpeta dentro de `C:\xampp\htdocs\Ropa_live`.
2. Inicia Apache y MySQL desde XAMPP.
3. En phpMyAdmin, importa `database\database.sql`. El archivo crea la base `ropa_live` y sus tablas.
4. Abre `http://localhost/Ropa_live/`.
5. Crea el usuario administrador siguiendo la sección siguiente.

## Actualizar una instalación existente

Antes de migrar, exporta un respaldo completo de la base de datos. Ejecuta `database\actualizar.sql` **una sola vez** sobre la base `ropa_live`; este script migra las cuentas antiguas a `usuarios` y crea los campos y tablas nuevos. No importes `database.sql` encima de una instalación antigua como sustituto de la migración.

Si una migración anterior quedó aplicada parcialmente y el catálogo informa que faltan `marca` o `categoria`, ejecuta `database\actualizar_filtros.sql`. Este parche comprueba cada columna antes de añadirla y se puede ejecutar otra vez.

Los productos, cuentas y totales de pedidos anteriores se conservan. El esquema anterior no guardaba los artículos de cada pedido, así que esos detalles históricos no se pueden reconstruir.

## Crear el primer administrador

No hay credenciales de administrador predeterminadas. Genera un hash desde PowerShell:

```powershell
php -r "echo password_hash('ReemplazaEstaClave123!', PASSWORD_DEFAULT), PHP_EOL;"
```

Usa una contraseña propia. Copia el hash en la consulta de phpMyAdmin:

```sql
INSERT INTO ropa_live.usuarios (nombre, email, password, rol)
VALUES ('Administrador', 'admin@tu-tienda.com', 'PEGA_AQUI_EL_HASH', 'admin');
```

Luego inicia sesión en `http://localhost/Ropa_live/Login.php`.

Las cuentas nuevas y las contraseñas actualizadas se almacenan con `password_hash`. Las contraseñas heredadas en texto normal se convierten a hash después de un inicio de sesión correcto.

## Uso del catálogo y los pedidos

- Un producto puede pertenecer a una colección o publicarse sin ella.
- La página principal muestra los productos sin colección y permite filtrar por marca, categoría, género, rango de precio, búsqueda y orden. El enlace **Colección** lleva a las colecciones publicadas.
- Para crear una colección se debe seleccionar al menos un producto existente.
- Productos y colecciones aceptan hasta tres imágenes JPG, PNG o WEBP, máximo 5 MB por imagen.
- El cliente guarda nombre, dirección, ciudad y foto en su perfil; el email identifica la cuenta y no se duplica en tablas distintas.
- Al comprar, el pedido guarda cliente, fecha, dirección, ciudad, total y una copia de nombre, imagen, cantidad y precio de cada prenda. El descuento de inventario y la creación del pedido se hacen en una transacción.
- No se procesa pago en línea.

## Configuración de la base de datos

En local, `config\Conexion.php` usa `localhost`, base `ropa_live`, usuario `root` y contraseña vacía, como en una instalación habitual de XAMPP.

Para otro servidor, configura estas variables de entorno en PHP: `DB_HOST`, `DB_NAME`, `DB_USER` y `DB_PASSWORD`. Usa un usuario MySQL propio y no publiques credenciales.

## Despliegue

1. Usa un hosting con PHP 8 o posterior, MySQL/MariaDB y HTTPS.
2. Crea la base y un usuario MySQL desde el panel del hosting.
3. Importa `database\database.sql` para una instalación nueva. Para actualizar una tienda existente, respalda la base y ejecuta `database\actualizar.sql`.
4. Sube el proyecto y configura las variables `DB_*`.
5. Crea el administrador y prueba registro, inicio de sesión, carga de imágenes, inventario y un pedido.
6. No dejes respaldos SQL con datos reales en la carpeta pública. El servidor integrado de PHP es solo para pruebas.
