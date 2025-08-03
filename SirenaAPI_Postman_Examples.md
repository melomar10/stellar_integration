# Ejemplos para Postman - API Sirena

## Configuración Base
- **Base URL**: `http://localhost:8000/api`
- **Content-Type**: `application/json`

## 1. Ejecutar Migraciones y Seeders

Antes de probar, ejecuta estos comandos:

```bash
php artisan migrate
php artisan db:seed
```

## 2. Endpoints Disponibles

### 2.1. Obtener Tasa de Cambio (GET)
```
GET /api/sirena/recharge-resume?total=10
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
        "subtotal_usd": 10,
        "convertion_rate": 59.376338,
        "service_fee_usd": 1.12,
        "total_usd": 11.12,
        "total_pesos": 660.26,
        "service_fee_pesos": 66.5,
        "subtotal_pesos": 593.76
    }
}
```

### 2.2. Obtener Sucursales por Provincia (GET)
```
GET /api/sirena/companies/3w88aXrcoodCn8n2CR2v
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
        "Province": "Baní,Barahona,Bonao,Higüey,La Romana,La Vega,Moca,Puerto Plata,Samaná,San Cristóbal,San Francisco de Macoris,San Pedro de Macorís,Santiago,Santo Domingo",
        "IdProvinces": "TpwBGVPyllUPFUsB5EZ2,3w88aXrcoodCn8n2CR2v,70CLheBuACoctztaAnbV,aBflTOMEppWgIQkmvU14,8o1FcuiH62vWrFLG2CfA,ZjbFZOYsCQinPDQ04zJ8,de8tLAEWxB6GiQJW0N9c,5AH3NMjnnMYm1jdkQi73,XSFTN3dPhuSOx2kSdARx,TlYDPIr7Yb2aiTWLhHnf,vu8Pm4Z38Cm1CetuoHhA,Yaimk8N5lgJ3VDQkRBiA,3xkjb03PtO2rTQ4XiaKm,YzED8m8yQCeYZtHcd7nR",
        "provinces": [
            {
                "id_province": "TpwBGVPyllUPFUsB5EZ2",
                "province": "Baní"
            },
            {
                "id_province": "3w88aXrcoodCn8n2CR2v",
                "province": "Barahona"
            }
        ]
    }
}
```

### 2.3. Solicitar Bono/Pago (POST) ⭐ PRINCIPAL

```
POST /api/sirena/request-bonus
```

**Headers:**
```
Content-Type: application/json
```

**Body (JSON):**
```json
{
    "user_id": "R3kAJiMQZagWmQPIAkdsaG5stME2",
    "amount": 100,
    "note": "Pago de prueba"
}
```

**Response esperado:**
```json
{
    "ok": true,
    "data": {
        "user_data": {
            "name": "Dariel",
            "last_name": "Abreu",
            "email": "luisdanielcurso@gmail.com"
        },
        "amount_pesos": 100,
        "amount_usd": 1.68,
        "service_fee_usd": 0.87,
        "convertion_rate": 59.513734,
        "total_usd": 2.55,
        "total_pesos": 151.8,
        "transfer_url": "https://domipagosclient.web.app/#/pay_bonus/GUI_1754176044945_9524",
        "payment_id": "GUI_1754176044945_9524"
    }
}
```

## 3. Ejemplos de Prueba

### Ejemplo 1: Pago de 500 pesos
```json
{
    "user_id": "R3kAJiMQZagWmQPIAkdsaG5stME2",
    "amount": 500,
    "note": "Pago de 500 pesos"
}
```

### Ejemplo 2: Pago de 1000 pesos
```json
{
    "user_id": "R3kAJiMQZagWmQPIAkdsaG5stME2",
    "amount": 1000,
    "note": "Pago de 1000 pesos"
}
```

### Ejemplo 3: Pago sin nota
```json
{
    "user_id": "R3kAJiMQZagWmQPIAkdsaG5stME2",
    "amount": 250
}
```

## 4. Casos de Error

### Error: Cliente no encontrado
```json
{
    "user_id": "UUID_INEXISTENTE",
    "amount": 100
}
```

**Response:**
```json
{
    "ok": false,
    "message": "Cliente no encontrado",
    "data": null
}
```

### Error: Parámetros faltantes
```json
{
    "amount": 100
}
```

**Response:**
```json
{
    "ok": false,
    "message": "user_id y amount son requeridos",
    "data": null
}
```

## 5. Configuración de Postman

### Variables de Entorno
Crea un environment en Postman con estas variables:

```
BASE_URL: http://localhost:8000/api
USER_ID: R3kAJiMQZagWmQPIAkdsaG5stME2
```

### Collection
Crea una collection llamada "Sirena API" con estos requests:

1. **Get Recharge Resume**
   - Method: GET
   - URL: `{{BASE_URL}}/sirena/recharge-resume?total=10`

2. **Get Companies by Province**
   - Method: GET
   - URL: `{{BASE_URL}}/sirena/companies/3w88aXrcoodCn8n2CR2v`

3. **Request Bonus**
   - Method: POST
   - URL: `{{BASE_URL}}/sirena/request-bonus`
   - Body (raw JSON):
   ```json
   {
       "user_id": "{{USER_ID}}",
       "amount": 100,
       "note": "Pago de prueba"
   }
   ```

## 6. Notas Importantes

- El cliente de ejemplo se crea automáticamente con el seeder
- UUID del cliente: `R3kAJiMQZagWmQPIAkdsaG5stME2`
- El método `requestBonus` hace múltiples llamadas internas:
  1. Obtiene tasa de cambio con $10 USD
  2. Convierte pesos a dólares
  3. Obtiene invoice_info con el monto convertido
  4. Obtiene company_id
  5. Realiza la petición de pago
  6. Construye la URL de transferencia 