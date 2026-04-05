# Multi SMS — Laravel 11 + Redis + Patrón Strategy

Sistema de envío distribuido de SMS que soporta múltiples proveedores mediante el patrón Strategy y procesamiento asíncrono con Redis.

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

## Arquitectura — Patrón Strategy

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

## Levantar 3 workers en paralelo (arquitectura distribuida)

Abre **4 terminales** y ejecuta lo siguiente:

```bash
# Terminal 1 — Servidor HTTP
php artisan serve

# Terminal 2 — Worker #1
php artisan queue:work redis --sleep=3 --tries=3

# Terminal 3 — Worker #2
php artisan queue:work redis --sleep=3 --tries=3

# Terminal 4 — Worker #3
php artisan queue:work redis --sleep=3 --tries=3
```

Cada worker es un proceso independiente con su propio PID. Los logs confirman cuál procesó cada Job.

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

### PowerShell (Windows)

```powershell
$body = @{ phone = "+573001234567"; message = "Hola desde Multi SMS" } | ConvertTo-Json
Invoke-RestMethod -Method POST -Uri "http://localhost:8000/api/send-sms" -ContentType "application/json" -Body $body
```

### curl (Linux / Mac / Git Bash)

```bash
curl -X POST http://localhost:8000/api/send-sms \
  -H "Content-Type: application/json" \
  -d '{"phone":"+573001234567","message":"Hola desde Multi SMS"}'
```

### Carga masiva — 10 mensajes simultáneos (PowerShell)

```powershell
1..10 | ForEach-Object -Parallel {
    $body = @{ phone = "+57300000000$_"; message = "Mensaje $_" } | ConvertTo-Json
    Invoke-RestMethod -Method POST -Uri "http://localhost:8000/api/send-sms" -ContentType "application/json" -Body $body
} -ThrottleLimit 10
```

---

## Ver logs en tiempo real

```bash
# PowerShell
Get-Content storage/logs/laravel.log -Wait -Tail 20

# Linux/Mac
tail -f storage/logs/laravel.log
```

### Ejemplo de salida con 3 workers

```
local.INFO: Worker procesando SMS {"worker_pid":14201,"provider":"App\Services\Sms\LogSmsProvider","phone":"+573001"}
local.INFO: [LogSmsProvider] SMS simulado {"phone":"+573001","message":"Mensaje 1"}
local.INFO: Worker procesando SMS {"worker_pid":14203,"provider":"App\Services\Sms\LogSmsProvider","phone":"+573002"}
local.INFO: Worker procesando SMS {"worker_pid":14202,"provider":"App\Services\Sms\LogSmsProvider","phone":"+573003"}
```

Los **PIDs distintos** (14201, 14202, 14203) demuestran que los 3 workers procesaron Jobs en paralelo.

---

## Estructura del proyecto

```
app/
├── Contracts/
│   └── SmsProviderInterface.php          ← Interfaz Strategy
├── Services/Sms/
│   ├── LogSmsProvider.php                ← Implementación: log
│   └── VonageSmsProvider.php             ← Implementación: Vonage
├── Jobs/
│   └── SendSmsJob.php                    ← Job con DI del proveedor
├── Http/Controllers/
│   └── SmsController.php                 ← Endpoint POST /api/send-sms
└── Providers/
    └── SmsServiceProvider.php            ← Resolución del proveedor

routes/
└── api.php                               ← Ruta POST /api/send-sms

config/
└── services.php                          ← Configuración Vonage + SMS_PROVIDER

bootstrap/
├── app.php                               ← Registro de rutas API
└── providers.php                         ← Registro de SmsServiceProvider
```
