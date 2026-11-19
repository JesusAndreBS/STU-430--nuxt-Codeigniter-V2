# STU-430 · Sistema de Firmas Digitales

Aplicación para capturar la firma de un empleado con una tableta **Wacom STU-430**. El formulario pide nombre y DNI, el adaptador Wacom genera un único `wacom_blob` y la base de datos lo guarda tal cual.

Ni el frontend ni el backend cifran, descifran ni hashean datos. El backend nunca interpreta el contenido del blob; el adaptador solo lo empaqueta en base64.

## Stack

| Capa | Tecnología |
|---|---|
| Frontend | Nuxt 4, Vue 3, TypeScript |
| Backend | CodeIgniter 4.7, PHP 8.3, Apache |
| Base de datos | MySQL 8 |
| Tableta | Wacom STU-430 + Signature SDK + SigCaptX |

## Estructura

```text
STU-430/
├── backend/                    API CodeIgniter 4
│   ├── app/Controllers/Api/    Endpoints REST
│   ├── app/Database/Migrations/
│   └── tests/                  PHPUnit
├── frontend-nuxt/              Aplicación Nuxt
│   ├── app/components/         Captura, RegistroEmpleados
│   ├── app/composables/        Cliente API
│   ├── app/wacom.js            Adaptador del SDK Wacom
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

2. Levanta los servicios:

   ```bash
   docker compose up -d --build
   docker compose logs -f backend
   
   ```

   El contenedor aplica las migraciones al arrancar.

3. Inicia el frontend:

   ```bash
   cd frontend-nuxt
   npm install
   npm run dev
   ```

4. Abre `http://localhost:3000`.

| Servicio | URL |
|---|---|
| Frontend | http://localhost:3000 |
| API | http://localhost:8090/api |
| phpMyAdmin | http://127.0.0.1:8081 |
| MySQL | 127.0.0.1:3307 |

## Modelo de datos

### `firmas`

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | INT AUTO_INCREMENT | Clave primaria |
| `wacom_blob` | TEXT NOT NULL | Blob opaco generado por el adaptador Wacom |
| `fecha_creacion` | DATETIME | Fecha de creación (`CURRENT_TIMESTAMP`) |

No se guardan nombre, DNI, trazo ni hash por separado. Todo el paquete vive dentro de `wacom_blob`.

La firma no se puede editar. Para reemplazarla se crea un registro nuevo y el anterior queda como historial.

## Flujo

1. El operador escribe nombre y DNI.
2. El empleado firma en la tableta STU-430.
3. Al pulsar **Guardar**, el frontend llama a `wacom.capturarFirma({ nombre, dni, trazo, serial })`.
4. El frontend envía únicamente `{ "wacom_blob": "..." }` al backend.
5. El backend valida que no venga vacío y hace `INSERT` en `firmas`.
6. Para consultar, el backend devuelve el `wacom_blob` tal cual; la aplicación no lo descifra. La verificación en el Wacom no está implementada.

> Limitación: el STU-430 no cifra nombre y DNI por sí solo con esta API. El adaptador construye un contenedor `WACOM-STU430-` + base64(JSON). No es cifrado de hardware.

## API

Todas las rutas devuelven JSON.

| Método | Ruta | Descripción |
|---|---|---|
| `GET` | `/api/firmas` | Lista `id`, `wacom_blob`, `fecha_creacion` |
| `GET` | `/api/firmas/:id` | Devuelve el blob tal cual está en la BD |
| `POST` | `/api/firmas` | Crea una firma con `{ "wacom_blob": "..." }` |
| `DELETE` | `/api/firmas/:id` | Elimina una firma |

No existe ruta de actualización ni edición.

Ejemplo de entrada:

```json
{
  "wacom_blob": "WACOM-STU430-..."
}
```

## Pruebas

```bash
docker compose exec backend composer install
docker compose exec backend vendor/bin/phpunit
```

## Seguridad y operación

- `docker-compose.yml` usa `CI_ENVIRONMENT=production` para no mostrar secretos en errores.
- MySQL y phpMyAdmin están publicados solo en `127.0.0.1`.
- `backend/.env` se monta como solo lectura y está excluido de Git.
- La API no tiene autenticación: no la expongas fuera de la red local sin añadir un proxy autenticado.
- Respaldos:

  ```bash
  docker compose exec db mysqldump -ustu430 -pstu430pass stu430 > respaldo.sql
  ```

## Problemas frecuentes

| Síntoma | Causa probable |
|---|---|
| La tableta no conecta | SigCaptX detenido u ocupado por otra pestaña |
| Listado vacío tras migrar | Ejecuta `docker compose logs backend` y revisa las migraciones |
| Error de CORS | El frontend debe correr en `localhost:3000` o `localhost:5173` |
