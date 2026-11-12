# Dependencias obligatorias · Wacom STU-430

> ⚠️ **REQUISITOS OBLIGATORIOS**
>
> Para que el sistema pueda comunicarse con la tableta **Wacom STU-430** es
> **obligatorio** instalar los paquetes `SignatureSDK` y `SigCaptX`. Sin estas
> dependencias el llamado de firma falla por falta de controladores y del
> servicio local.

## 1. Contenido de la carpeta

| Archivo | Instalador | Qué aporta |
|---|---|---|
| `SignatureSDK/Wacom-Signature-SDK-x64-4.10.7.msi` | Wacom Signature SDK 4.10.7 (x64) | Librerías base (`wgssSTU`), controladores USB y herramientas de la tableta |
| `SigCaptX/Wacom-SigCaptX-1.27.3.exe` | Wacom SigCaptX 1.27.3 | Servicio local en segundo plano (`WgssSTU_Server.exe`) que permite firmar desde la web |

## 2. ¿Por qué son necesarios?

- **`SignatureSDK`:** provee las librerías base (`wgssSTU`) para leer los datos
  biométricos del dispositivo USB (posición del lápiz, presión, botones).
- **`SigCaptX`:** levanta el servicio local (`WacomSTUSigCaptX`) necesario para
  que la aplicación web ejecute el llamado de firma sin bloqueos y sin que el
  navegador acceda directamente al USB.

Sin `SignatureSDK` la tableta no es enumerada por el sistema; sin `SigCaptX` el
navegador no puede detectarla aunque esté bien conectada.

## 3. Orden estricto de instalación (ejecutar como administrador)

1. **Desconectar** la tableta Wacom del puerto USB.
2. Instalar **`SignatureSDK`**
   (`Wacom-Signature-SDK-x64-4.10.7.msi`).
   - Si el instalador solicita la variante de 32 bits, elige **x86/x32**.
3. Instalar **`SigCaptX`** (`Wacom-SigCaptX-1.27.3.exe`).
   - Acepta instalar el **servicio** de Windows (`WacomSTUSigCaptX`).
4. **Conectar** la tableta por USB.
5. Verificar en **Servicios de Windows** (`services.msc`) que
   **`WacomSTUSigCaptX`** esté **En ejecución**.
   - Detenido → clic derecho → **Iniciar**.
6. Comprobar que la tableta aparece en el Administrador de dispositivos de
   Windows, sin signos de advertencia.

## 4. Archivos que usa la web

La aplicación carga el SDK desde `frontend-nuxt/public/`:

```text
frontend-nuxt/public/q.js          librería Q (dependencia del SDK)
frontend-nuxt/public/wgssStuSdk.js  SDK de Wacom
```

Si no están, cópialos de la instalación de SigCaptX
(`C:\Program Files\Wacom\SigCaptX\...`) a `frontend-nuxt/public/` y recarga la
página.

## 5. Comprobación rápida

```bash
# ¿Está el servicio arrancado? (PowerShell)
Get-Service WacomSTUSigCaptX

# ¿Responde el puerto local de SigCaptX?
netstat -ano | findstr 8080
```

Si el estado es `Stopped`, arranca el servicio antes de abrir la web.
