# Integración con Short.io

Este documento describe la integración del servicio de acortamiento de URLs de Short.io en la aplicación Laravel.

## Configuración

### Variables de Entorno

Agrega las siguientes variables a tu archivo `.env`:

```env
SHORTIO_API_KEY=sk_OHtFayXvRmQdzwS5
SHORTIO_DOMAIN=tu-dominio.short.io
```

### Configuración de Servicios

La configuración se encuentra en `config/services.php`:

```php
'shortio' => [
    'api_key' => env('SHORTIO_API_KEY', 'sk_OHtFayXvRmQdzwS5'),
    'domain' => env('SHORTIO_DOMAIN', 'example.xyz'),
],
```

## Servicios

### ShortLinkService

El servicio principal para interactuar con la API de Short.io.

#### Métodos Disponibles

1. **createShortLink($originalUrl, $ttl = null, $path = null)**
   - Crea un enlace corto
   - Parámetros:
     - `$originalUrl`: URL original a acortar
     - `$ttl`: Tiempo de vida en segundos (se convierte automáticamente a fecha futura)
     - `$path`: Slug personalizado (opcional)

2. **updateShortLink($linkId, $updateData)**
   - Actualiza un enlace corto existente

3. **deleteShortLink($linkId)**
   - Elimina un enlace corto

4. **getShortLinkInfo($linkId)**
   - Obtiene información de un enlace corto

## Uso en SirenaService

El servicio está integrado en `SirenaService` para acortar automáticamente los `transfer_url`:

```php
// En el método requestBonus()
$originalTransferUrl = "https://domipagosclient.web.app/#/pay_bonus/{$responseData['data']['id']}";

// Acortar el transfer_url usando Short.io
$shortLinkResponse = $this->shortLinkService->createShortLink(
    $originalTransferUrl,
    86400 // TTL de 24 horas
);

// Usar el enlace corto si se creó exitosamente
$transferUrl = $shortLinkResponse['ok'] 
    ? $shortLinkResponse['data']['short_url'] 
    : $originalTransferUrl;
```

## API Endpoints

### Crear Enlace Corto
```
POST /api/shortlink/create
```

**Body:**
```json
{
    "url": "https://ejemplo.com/url-muy-larga",
    "ttl": 86400,
    "path": "mi-slug-personalizado"
}
```

### Obtener Información
```
GET /api/shortlink/info/{linkId}
```

### Actualizar Enlace
```
PUT /api/shortlink/update/{linkId}
```

**Body:**
```json
{
    "originalURL": "https://nueva-url.com",
    "path": "nuevo-slug"
}
```

### Eliminar Enlace
```
DELETE /api/shortlink/delete/{linkId}
```

## Respuestas

### Respuesta Exitosa
```json
{
    "ok": true,
    "data": {
        "original_url": "https://ejemplo.com/url-muy-larga",
        "short_url": "https://tu-dominio.short.io/abc123",
        "secure_short_url": "https://tu-dominio.short.io/abc123",
        "id": "link_id_123",
        "path": "abc123",
        "created_at": "2025-01-27T10:30:00.000Z"
    }
}
```

### Respuesta de Error
```json
{
    "ok": false,
    "message": "Error al crear el enlace corto: mensaje de error",
    "data": null
}
```

## Logs

El servicio registra logs automáticamente:

- **Info**: Cuando se crea un enlace corto exitosamente
- **Error**: Cuando hay errores en la creación o excepciones

## Consideraciones

1. **TTL**: Se recomienda usar el parámetro TTL para enlaces temporales. El servicio convierte automáticamente los segundos a una fecha futura válida.
2. **Fallback**: Si falla la creación del enlace corto, se usa la URL original
3. **Dominio**: Asegúrate de configurar tu dominio en Short.io
4. **API Key**: La API key proporcionada está configurada por defecto

## Documentación de Short.io

Para más información sobre la API de Short.io, consulta:
- [Documentación oficial](https://developers.short.io/docs/creating-your-first-short-link)
- [Referencia de la API](https://developers.short.io/reference/post_links) 