# Ejemplos para Postman - API Clientes

## Configuración Base
- **Base URL**: `http://localhost:8000/api`
- **Content-Type**: `application/json`

## 1. Endpoints Disponibles

### 1.1. Crear Cliente (POST)
```
POST /api/client/new
```

**Headers:**
```
Content-Type: application/json
```

**Body (JSON):**
```json
{
    "name": "Juan",
    "last_name": "Pérez",
    "email": "juan.perez@ejemplo.com",
    "phone": "+1 809-555-1234",
    "card_number_id": "12345678901"
}
```

**Response esperado:**
```json
{
    "ok": true,
    "message": "Cliente creado exitosamente",
    "data": {
        "id": 1,
        "name": "Juan",
        "last_name": "Pérez",
        "email": "juan.perez@ejemplo.com",
        "phone": "+1 809-555-1234",
        "uuid": "f47ac10b-58cc-4372-a567-0e02b2c3d479",
        "status": "active",
        "card_number_id": "12345678901",
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-15T10:30:00.000000Z"
    }
}
```

### 1.2. Obtener Todos los Clientes (GET)
```
GET /api/client/all
```

**Headers:**
```
Content-Type: application/json
```

**Response esperado:**
```json
{
    "ok": true,
    "data": [
        {
            "id": 1,
            "name": "Dariel",
            "last_name": "Abreu",
            "email": "luisdanielcurso@gmail.com",
            "phone": "+1 829-873-6708",
            "uuid": "R3kAJiMQZagWmQPIAkdsaG5stME2",
            "status": "active",
            "card_number_id": "40227520364",
            "created_at": "2024-01-15T10:30:00.000000Z",
            "updated_at": "2024-01-15T10:30:00.000000Z"
        },
        {
            "id": 2,
            "name": "Juan",
            "last_name": "Pérez",
            "email": "juan.perez@ejemplo.com",
            "phone": "+1 809-555-1234",
            "uuid": "abc123-def456-ghi789",
            "status": "active",
            "card_number_id": "12345678901",
            "created_at": "2024-01-15T10:35:00.000000Z",
            "updated_at": "2024-01-15T10:35:00.000000Z"
        }
    ]
}
```

### 1.3. Obtener Cliente por UUID (GET)
```
GET /api/client/uuid/R3kAJiMQZagWmQPIAkdsaG5stME2
```

**Headers:**
```
Content-Type: application/json
```

**Response esperado:**
```json
{
    "ok": true,
    "data": {
        "id": 1,
        "name": "Dariel",
        "last_name": "Abreu",
        "email": "luisdanielcurso@gmail.com",
        "phone": "+1 829-873-6708",
        "uuid": "R3kAJiMQZagWmQPIAkdsaG5stME2",
        "status": "active",
        "card_number_id": "40227520364",
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-15T10:30:00.000000Z"
    }
}
```

### 1.4. Obtener Cliente por Teléfono (GET)
```
GET /api/client/+1 829-873-6708
```

**Headers:**
```
Content-Type: application/json
```

**Response esperado:**
```json
{
    "ok": true,
    "data": {
        "id": 1,
        "name": "Dariel",
        "last_name": "Abreu",
        "email": "luisdanielcurso@gmail.com",
        "phone": "+1 829-873-6708",
        "uuid": "R3kAJiMQZagWmQPIAkdsaG5stME2",
        "status": "active",
        "card_number_id": "40227520364",
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-15T10:30:00.000000Z"
    }
}
```

## 2. Ejemplos de Prueba

### Ejemplo 1: Crear Cliente Básico
```json
{
    "name": "María",
    "last_name": "González",
    "email": "maria.gonzalez@ejemplo.com",
    "phone": "+1 809-555-5678"
}
```

### Ejemplo 2: Crear Cliente Completo
```json
{
    "name": "Carlos",
    "last_name": "Rodríguez",
    "email": "carlos.rodriguez@ejemplo.com",
    "phone": "+1 809-555-9012",
    "card_number_id": "98765432109"
}
```

### Ejemplo 3: Crear Cliente Mínimo
```json
{
    "name": "Ana",
    "last_name": "Martínez",
    "email": "ana.martinez@ejemplo.com",
    "phone": "+1 809-555-3456"
}
```

## 3. Casos de Error

### Error: Campos requeridos faltantes
```json
{
    "name": "Test",
    "email": "test@ejemplo.com"
}
```

**Response:**
```json
{
    "message": "The phone field is required.",
    "errors": {
        "phone": [
            "The phone field is required."
        ]
    }
}
```

### Error: Cliente no encontrado por teléfono
```
GET /api/client/+1 809-999-9999
```

**Response:**
```json
null
```

## 4. Configuración de Postman

### Variables de Entorno
Crea un environment en Postman con estas variables:

```
BASE_URL: http://localhost:8000/api
TEST_PHONE: +1 829-873-6708
```

### Collection
Crea una collection llamada "Client API" con estos requests:

#### 1. **Create Client**
- Method: POST
- URL: `{{BASE_URL}}/client/new`
- Body (raw JSON):
```json
{
    "name": "Test",
    "last_name": "User",
    "email": "test@ejemplo.com",
    "phone": "+1 809-555-0000"
}
```

#### 2. **Get All Clients**
- Method: GET
- URL: `{{BASE_URL}}/client/all`

#### 3. **Get Client by UUID**
- Method: GET
- URL: `{{BASE_URL}}/client/uuid/R3kAJiMQZagWmQPIAkdsaG5stME2`

#### 4. **Get Client by Phone**
- Method: GET
- URL: `{{BASE_URL}}/client/{{TEST_PHONE}}`

## 5. Estructura del Modelo Client

### Campos disponibles:
- `name` (string) - Nombre del cliente
- `last_name` (string) - Apellido del cliente
- `email` (string) - Email del cliente
- `phone` (string) - Teléfono del cliente
- `uuid` (string) - UUID único del cliente
- `status` (string) - Estado del cliente (active/inactive)
- `card_number_id` (string) - Número de tarjeta/identificación

### Campos automáticos:
- `id` (integer) - ID autoincremental
- `created_at` (timestamp) - Fecha de creación
- `updated_at` (timestamp) - Fecha de actualización

## 6. Ejemplos de Uso con Sirena API

### Crear cliente y luego usar en request-bonus:

#### Paso 1: Crear cliente
```json
POST /api/client/new
{
    "name": "Pedro",
    "last_name": "López",
    "email": "pedro.lopez@ejemplo.com",
    "phone": "+1 809-555-1111",
    "card_number_id": "11122233344"
}
```

#### Paso 2: Usar el cliente en request-bonus
```json
POST /api/sirena/request-bonus
{
    "user_id": "UUID_GENERADO_AUTOMATICAMENTE",
    "amount": 500,
    "note": "Pago de Pedro López"
}
```

## 7. Notas Importantes

- El campo `uuid` se genera automáticamente y es único para cada cliente
- **IMPORTANTE**: El UUID generado automáticamente se debe usar como `user_id` en el API de Sirena
- El campo `card_number_id` es opcional y se usa como `receiver_reference` en el API de Sirena
- El campo `status` se establece automáticamente como "active"
- La búsqueda por teléfono es exacta, incluye el código de país
- Los clientes creados con el seeder ya están disponibles para pruebas

## 8. Comandos Útiles

### Ejecutar migraciones y seeders:
```bash
php artisan migrate
php artisan db:seed
```

### Ver clientes en la base de datos:
```bash
php artisan tinker
>>> App\Models\Client::all();
``` 