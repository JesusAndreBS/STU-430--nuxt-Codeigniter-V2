# STU-430 · Sistema de Firmas Digitales

Aplicación para registrar empleados y capturar su firma con una tableta **Wacom STU-430**. La identidad se guarda cifrada, la firma se almacena como PNG dentro de un contenedor AES-256-GCM y la base de datos solo conserva hashes para búsquedas.

## Stack

| Capa | Tecnología |
|---|---|
| Frontend | Nuxt 4, Vue 3, TypeScript |
| Backend | CodeIgniter 4.7, PHP 8.3, Apache |
| Base de datos | MySQL 8 |
| Tableta | Wacom STU-430 + Signature SDK + SigCaptX |
| Cifrado | AES-256-GCM (OpenSSL) + HMAC-SHA256 |

## Estructura

```text
STU-430/
├── backend/                    API CodeIgniter 4
│   ├── app/Controllers/Api/    Endpoints REST
│   ├── app/Libraries/          FirmaDigital, Dni
│   ├── app/Models/             EmpleadoModel, FirmaDigitalModel
│   ├── app/Database/Migrations/
│   └── tests/                  PHPUnit
├── frontend-nuxt/              Aplicación Nuxt
│   ├── app/components/         Captura, RegistroEmpleados
│   ├── app/composables/        Cliente API
│   └── public/documentacion/   Documentación estática
├── Dependencias-install/       Instaladores de Wacom
└── docker-compose.yml
```

## Requisitos

- Windows con Docker Desktop.
- Node.js 20 o superior y npm.
- Tableta Wacom STU-430.
- Instaladores de `Dependencias-install/`:
  - `SignatureSDK/Wacom-Signature-SDK-x64-4.10.7.msi`
  - `SigCaptX/Wacom-SigCaptX-1.27.3.exe`

Instala el SDK y SigCaptX como administrador, conecta la tableta y comprueba que el servicio `WacomSTUSigCaptX` esté en ejecución.

## Puesta en marcha

1. Crea el archivo de entorno:

   ```bash
   cp backend/.env.example backend/.env
   ```

2. Genera las claves y edítalas en `backend/.env`:

   ```bash
   # FIRMA_AES_KEY: 32 bytes en base64
   openssl rand -base64 32

   # FIRMA_HASH_SALT: cadena larga y aleatoria
   openssl rand -hex 32
   ```

   `FIRMA_HASH_SALT` no debe cambiarse después de registrar empleados sin volver a ejecutar el rehash de la migración. `HASH_SALT` solo lo usan migraciones históricas.

3. Levanta los servicios:

   ```bash
   docker compose up -d --build
   docker compose logs -f backend
   ```

   El contenedor aplica las migraciones al arrancar.

4. Inicia el frontend:

   ```bash
   cd frontend-nuxt
   npm install
   npm run dev
   ```

5. Abre `http://localhost:3000`.

| Servicio | URL |
|---|---|
| Frontend | http://localhost:3000 |
| API | http://localhost:8090/api |
| phpMyAdmin | http://127.0.0.1:8081 |
| MySQL | 127.0.0.1:3307 |

## Modelo de datos

### `empleados`

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | INT UNSIGNED | Clave primaria |
| `dni_hash` | CHAR(64) único | HMAC-SHA256 del DNI |
| `nombre_dni_hash` | CHAR(64) único | HMAC-SHA256 de nombre + DNI |
| `identidad_cifrada` | LONGTEXT NOT NULL | JSON `{nombre, dni, fecha_firma, mime, firma_base64}` cifrado |
| `created_at`, `updated_at` | DATETIME | Auditoría |

### `firmas_digitales`

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | INT UNSIGNED | Clave primaria |
| `empleado_id` | INT UNSIGNED único | FK a `empleados`, `ON DELETE CASCADE` |
| `firma_hash` | CHAR(64) único | SHA-256 de identidad + contenedor cifrado |
| `firma_cifrada` | LONGTEXT | Contenedor AES-256-GCM |
| `created_at` | DATETIME NOT NULL | Fecha de la firma |

No se guardan nombre, DNI ni PNG en claro. La identidad se conserva aunque el empleado esté pendiente de firma; el listado descifra solo la identidad y el detalle descifra además el PNG.

## Cifrado

- Clave: `FIRMA_AES_KEY` (32 bytes en base64).
- Algoritmo: AES-256-GCM.
- AAD: `stu430-firma-digital-v1`.
- Contenedor: `{version, algorithm, iv, tag, ciphertext}`.
- Hash de identidad: `HMAC-SHA256(nombre_normalizado + "|" + dni_normalizado, FIRMA_HASH_SALT)`.
- Hash de firma: `SHA-256(hash_identidad + "|" + contenedor)`.

## API

Todas las rutas devuelven JSON.

| Método | Ruta | Descripción |
|---|---|---|
| `GET` | `/api/empleados?q=` | Listado o búsqueda por nombre/DNI |
| `GET` | `/api/empleados/:id` | Detalle con `firma_base64` |
| `POST` | `/api/empleados` | Crear empleado; firma opcional |
| `PUT` | `/api/empleados/:id` | Actualizar identidad; descarta la firma anterior |
| `DELETE` | `/api/empleados/:id` | Eliminar empleado y su firma |
| `PUT` | `/api/empleados/:id/firma` | Crear o reemplazar la firma |
| `POST` | `/api/firmas/iniciar` | Generar JSON de entrada |

Ejemplo de entrada:

```json
{
  "tipo": "entrada",
  "nombre": "Ana Torres",
  "dni_hash": "a1b2...",
  "fecha_inicio": "2026-11-12T15:04:05+00:00",
  "estado": "Esperando firma"
}
```

La respuesta final incluye `tipo: "final"`, `fecha_firma`, `firma_digital` (hash) y `estado`.

## Recuperar una firma

```bash
docker compose exec backend php spark obtener-firma \
  --hash HASH_DE_LA_FIRMA --formato png --salida /tmp/firma.png

docker cp stu430_backend:/tmp/firma.png ./firma.png
```

El hash se obtiene de `firmas_digitales.firma_hash`, no del contenedor cifrado.

## Pruebas

```bash
docker compose exec backend composer install
docker compose exec backend vendor/bin/phpunit
```

Resultado esperado: 8 pruebas y 22 aserciones.

## Seguridad y operación

- `docker-compose.yml` usa `CI_ENVIRONMENT=production` para no mostrar secretos en errores.
- MySQL y phpMyAdmin están publicados solo en `127.0.0.1`.
- `backend/.env` se monta como solo lectura y está excluido de Git.
- La API no tiene autenticación: no la expongas fuera de la red local sin añadir un proxy autenticado.
- Respaldos:

  ```bash
  docker compose exec db mysqldump -ustu430 -pstu430pass stu430 > respaldo.sql
  ```

- Los archivos extraídos de firmas (`firma*.png`) están ignorados por Git.

## Problemas frecuentes

| Síntoma | Causa probable |
|---|---|
| La tableta no conecta | SigCaptX detenido u ocupado por otra pestaña |
| `FIRMA_AES_KEY` inválida | No son 32 bytes en base64 |
| No se encuentra un DNI | Se cambió `FIRMA_HASH_SALT` sin rehash |
| Listado vacío tras migrar | Ejecuta `docker compose logs backend` y revisa las migraciones |
| Error de CORS | El frontend debe correr en `localhost:3000` o `localhost:5173` |
