/**
 * Adaptador del SDK Wacom STU-430 con Signature SDK.
 *
 * El Wacom STU-430 con Signature SDK cifra la firma y devuelve una estructura
 * con los datos del firmante y la firma encriptada (firma_encriptada_wacom).
 * El backend descifra la firma y extrae la imagen PNG para visualización.
 */
const PREFIJO = 'WACOM-STU430-'

function base64Utf8(texto) {
  const bytes = new TextEncoder().encode(texto)
  let binario = ''
  for (const byte of bytes) binario += String.fromCharCode(byte)
  return btoa(binario)
}

function limpiarTrazo(trazo) {
  return String(trazo || '').replace(/^data:image\/png;base64,/, '')
}

/**
 * Captura la firma usando el Wacom Signature SDK.
 * Devuelve la estructura completa que el Wacom genera con firma encriptada.
 * 
 * Estructura devuelta:
 * {
 *   nombre: string,
 *   dni: string,
 *   firma_encriptada_wacom: string,  // Base64 con la firma encriptada (contiene PNG)
 *   serial_pad: string,
 *   fecha: string (ISO 8601)
 * }
 */
function capturarFirma({ nombre, dni, trazo, serial = '' }) {
  const nombreLimpio = String(nombre || '').trim()
  const dniLimpio = String(dni || '').trim()
  const trazoLimpio = limpiarTrazo(trazo)
  const serialLimpio = String(serial || '').trim()

  if (!nombreLimpio || !dniLimpio) throw new Error('El nombre y el DNI son obligatorios.')
  if (!trazoLimpio) throw new Error('La tableta no devolvió la firma.')

  // El PNG del trazo se guarda dentro de la firma encriptada (simulando cifrado Wacom)
  // En producción real, el Wacom Signature SDK cifra esto con AES-256/RSA
  const paqueteFirma = {
    tipo: 'firma_wacom',
    version: 1,
    firma_base64: trazoLimpio,  // PNG del trazo en base64
    timestamp_firma: new Date().toISOString(),
  }

  // La firma encriptada simula el formato Wacom (prefix + base64)
  // En producción: el SDK Wacom cifra esto con AES-256-CBC/RSA
  const firmaEncriptada = PREFIJO + btoa(JSON.stringify(paqueteFirma))

  const estructuraWacom = {
    nombre: nombre.trim(),
    dni: dni.trim(),
    firma_encriptada_wacom: firmaEncriptada,
    serial_pad: serialLimpio,
    fecha: new Date().toISOString(),
  }

  // El blob final que se envía al backend es la estructura completa en base64
  return PREFIJO + btoa(JSON.stringify(estructuraWacom))
}

/**
 * Extrae el PNG de la firma desde la estructura Wacom (para descarga/visualización)
 * Útil en frontend/backend para mostrar la imagen sin pasar por el backend.
 */
export function extraerPngDesdeBlob(blobCompleto) {
  const PREFIJO = 'WACOM-STU430-'
  
  if (!blobCompleto.startsWith(PREFIJO)) {
    throw new Error('Formato de blob no reconocido')
  }
  
  const base64Estructura = blobCompleto.slice(PREFIJO.length)
  const jsonEstructura = atob(base64Estructura)
  const estructura = JSON.parse(jsonEstructura)
  
  // Extraer la firma encriptada
  const firmaEncriptada = estructura.firma_encriptada_wacom
  if (!firmaEncriptada.startsWith(PREFIJO)) {
    throw new Error('Formato de firma encriptada no reconocido')
  }
  
  const base64PaqueteFirma = firmaEncriptada.slice(PREFIJO.length)
  const jsonPaqueteFirma = atob(base64PaqueteFirma)
  const paqueteFirma = JSON.parse(jsonPaqueteFirma)
  
  return paqueteFirma.firma_base64
}

export default { capturarFirma, extraerPngDesdeBlob }
