# Tienda Backend

Backend de ejemplo construido con **Laravel 12** para gestionar el flujo completo de una tienda. Incluye módulos para productos, proveedores, clientes, inventario, pedidos, ventas y autenticación de usuarios.

## Requisitos
- PHP 8.2 o superior
- Composer
- Node.js (para los assets de Vite)
- Base de datos MySQL o compatible

También puede ejecutarse mediante Docker utilizando el `docker-compose.yml` incluido.

## Instalación
1. Clona este repositorio.
2. Copia el archivo `.env.example` a `.env` y configura la conexión de base de datos.
3. Ejecuta `composer install` para instalar las dependencias de PHP.
4. Ejecuta `npm install` para instalar las dependencias de Node.
5. Genera la clave de la aplicación con `php artisan key:generate`.
6. Ejecuta las migraciones con `php artisan migrate`.
7. Inicia el servidor con `php artisan serve` o levanta los contenedores con `docker-compose up`.

## Estructura del proyecto
- **app/Http/Controllers** – Controladores agrupados por dominios (Clientes, Inventario, Productos_Proveedores y Seguridad).
- **app/Models** – Modelos de Eloquent para cada módulo (Clientes, Inventario, Pedidos, Productos y Proveedores, Seguridad, ventas_Pagos).
- **database/migrations** – Migraciones que crean todas las tablas necesarias.
- **routes/api.php** – Define las rutas de la API que se describen en la siguiente sección.

## Funcionalidades principales
- Gestión de productos, categorías y proveedores.
- Administración de clientes y sus categorías.
- Manejo de inventario con movimientos, configuraciones y alertas de stock.
- Notificaciones de alertas y resolución manual.
- Autenticación con Laravel Sanctum y opción de inicio de sesión con Google (Socialite).
- Pruebas automatizadas en `tests/`.

## Rutas API destacadas
Todas las rutas están bajo el prefijo `/api`.

```
GET    /api/categorias
POST   /api/categorias
GET    /api/categorias/{id}
PUT    /api/categorias/{id}
DELETE /api/categorias/{id}

GET    /api/categorias-clientes
GET    /api/categorias-proveedores
GET    /api/proveedores

GET    /api/productos/create            # datos para formularios
GET    /api/productos/buscar-por-nombre # búsqueda por nombre
apiResource /api/productos               # CRUD completo

GET    /api/tipos-movimientos
GET    /api/tipos-movimientos/{id}

# Módulo Inventario
apiResource /api/inventario/movimientos
apiResource /api/inventario/configuracion-alertas
apiResource /api/inventario/alertas-stock (excepto store)
POST       /api/inventario/alertas-stock/manual
apiResource /api/inventario/notificaciones-alertas (excepto destroy)
GET        /api/inventario/productos/lista

# Autenticación
POST   /api/login
POST   /api/register
GET    /api/auth/google/redirect
GET    /api/auth/google/callback
POST   /api/logout       # requiere autenticación Sanctum
GET    /api/user         # requiere autenticación Sanctum
```

## Ejecución de pruebas
Para ejecutar la suite de pruebas:

```bash
php artisan test
```

## Licencia
Este proyecto se distribuye bajo la licencia MIT.
