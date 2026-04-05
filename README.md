# Multi SMS — Laravel 11 + Redis +

Sistema de envío distribuido de SMS que soporta múltiples proveedores mediante el procesamiento asíncrono con Redis.

## Requisitos

| Herramienta | Versión mínima |
|-------------|---------------|
| PHP         | 8.2           |
| Composer    | 2.x           |
| Redis       | 6.x           |
| Laravel     | 11.x          |

---

## Instalación

```bash
composer install
cp .env.example .env
php artisan key:generate
```

---

## Variables de entorno clave

```env
# Cola Redis
QUEUE_CONNECTION=redis
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Proveedor SMS activo: log | vonage
SMS_PROVIDER=log

# Credenciales Vonage (sólo si SMS_PROVIDER=vonage)
VONAGE_API_KEY=your_api_key_here
VONAGE_API_SECRET=your_api_secret_here
VONAGE_SMS_FROM=MultiSMS
```

---

## Arquitectura

```
SmsProviderInterface
   ├── LogSmsProvider      ← Desarrollo: escribe en laravel.log
   └── VonageSmsProvider   ← Producción: envía SMS real vía API Vonage

SmsServiceProvider        ← Resuelve el proveedor según SMS_PROVIDER
SendSmsJob                ← Recibe el proveedor por DI, loguea el PID del worker
SmsController             ← Valida, encola el Job a Redis
```

Para cambiar de proveedor basta con editar `.env`:
```env
SMS_PROVIDER=vonage   # activa VonageSmsProvider
SMS_PROVIDER=log      # activa LogSmsProvider (por defecto)
```

---

## Levantar 3 workers en paralelo

Abrir **4 terminales** y ejecuta lo siguiente:

```bash
# Terminal 1 — Servidor HTTP
php artisan serve

# Terminal 2 — Worker #1
php artisan queue:work redis --sleep=3 --tries=3

---

## Uso de la API

### Endpoint

```
POST /api/send-sms
Content-Type: application/json
```

### Body

```json
{
  "phone": "+573001234567",
  "message": "Tu código de acceso es 4821"
}
```

### Respuesta `202 Accepted`

```json
{
  "status": "queued",
  "phone": "+573001234567",
  "message": "Tu código de acceso es 4821"
}
```

---

## Pruebas

### PowerShell

```powershell
$body = @{ phone = "+573164795110"; message = "Hola desde Multi SMS" } | ConvertTo-Json
Invoke-RestMethod -Method POST -Uri "http://localhost:8000/api/send-sms" -ContentType "application/json" -Body $body
```

### Carga masiva — 10 mensajes simultáneos

```powershell
$url = "http://localhost:8000/api/send-sms"
foreach ($i in 1..5) {
    $body = @{ phone = "+573164795110"; message = "Mensaje numero $i" } | ConvertTo-Json
    Invoke-RestMethod -Uri $url -Method Post -Body $body -ContentType "application/json"
    Start-Sleep -Milliseconds 200
}
```