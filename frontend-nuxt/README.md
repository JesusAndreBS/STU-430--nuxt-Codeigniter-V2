# Frontend STU-430 (Nuxt 4)

Interfaz de operador del sistema de firmas: captura de la firma en la tableta
Wacom STU-430 y listado/búsqueda de empleados.

## Puesta en marcha

```bash
npm install
npm run dev        # http://localhost:3000
```

El backend debe estar levantado en `http://localhost:8090` (ver el README de la
raíz). Para usar otro destino:

```bash
# frontend-nuxt/.env
NUXT_PUBLIC_API_BASE=http://localhost:8090/api
```

En desarrollo también funciona `NUXT_PUBLIC_API_BASE=/api`: Nitro hace de
proxy hacia `http://localhost:8090/api`.

## Comandos

| Comando | Qué hace |
|---|---|
| `npm run dev` | Servidor de desarrollo en `:3000` |
| `npm run build` | Compila para producción en `.output/` |
| `npm run preview` | Sirve la compilación |
| `node .output/server/index.mjs` | Ejecuta la versión compilada |

## Estructura

```
app/
├── app.vue                 pantalla principal (captura + listado)
├── components/
│   ├── Captura.vue         datos del empleado, pasos y firma
│   └── RegistroEmpleados.vue  tabla, búsqueda, edición y descarga
├── composables/api.ts      cliente de la API REST
├── assets/css/main.css     estilos globales
├── tablet.js               dibujo de la pantalla 320×200 de la tableta
├── conexion.js             conexión USB con la tableta
└── stu.js                  validación de los resultados de SigCaptX
```

> Nuxt 4 usa `app/` como directorio de código (`srcDir`). Los archivos fuera de
> esa carpeta no se compilan: la pantalla real vive en `app/app.vue`.

## Tableta Wacom

Copiar `wgssStuSdk.js` de la instalación de **Wacom SigCaptX** a
`frontend-nuxt/public/`. La aplicación lo carga automáticamente; si no está,
muestra un aviso indicando qué falta.
