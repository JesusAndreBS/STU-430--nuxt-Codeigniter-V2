/**
 * Pantalla de la tableta Wacom STU-430 (320 x 200 px, 1 bit).
 */
export const ANCHO = 320
export const ALTO = 200

export const PASO_BIENVENIDA = 'bienvenida'
export const PASO_DATOS = 'datos'
export const PASO_FIRMA = 'firma'
export const PASO_ESPERA = 'espera'
export const PASO_CANCELADA = 'cancelada'
export const PASO_GUARDADA = 'guardada'
export const PASO_RECHAZADA = 'rechazada'

export const LINEA_SEPARADOR = 120

export const AREA_FIRMA = { x: 0, y: 0, w: ANCHO, h: LINEA_SEPARADOR }

export const BIENVENIDA = ['Bienvenido a', 'la municipalidad de JLO']

export const ACCIONES_BOTON = {
  borrar: 'limpiar',
  cancelar: 'cancelar',
  guardar: 'enviar',
}

export function accionDeBoton(clave) {
  return ACCIONES_BOTON[clave] ?? null
}

export const BOTONES = {
  [PASO_FIRMA]: [
    { clave: 'borrar', x: 14, y: 166, w: 92, h: 30, texto: 'BORRAR' },
    { clave: 'cancelar', x: 114, y: 166, w: 92, h: 30, texto: 'CANCELAR' },
    { clave: 'guardar', x: 214, y: 166, w: 92, h: 30, texto: 'GUARDAR' },
  ],
}

export function botonesDe(paso) {
  return BOTONES[paso] ?? []
}

function recortar(texto, maximo) {
  const limpio = (texto || '').trim()
  return limpio.length > maximo ? `${limpio.slice(0, maximo - 1)}…` : limpio
}

function boton(ctx, rect, texto, doble = false) {
  ctx.strokeStyle = '#000000'
  ctx.lineWidth = 2
  ctx.strokeRect(rect.x, rect.y, rect.w, rect.h)
  if (doble) {
    ctx.strokeRect(rect.x + 3, rect.y + 3, rect.w - 6, rect.h - 6)
  }
  ctx.fillStyle = '#000000'
  ctx.font = 'bold 9px system-ui, "Segoe UI", sans-serif'
  ctx.textAlign = 'center'
  ctx.fillText(texto, rect.x + rect.w / 2, rect.y + rect.h / 2 + 3)
  ctx.textAlign = 'left'
}

function datosEmpleado(ctx, nombre, dni) {
  ctx.fillStyle = '#000000'
  ctx.font = 'bold 13px system-ui, "Segoe UI", sans-serif'
  ctx.fillText(recortar(nombre, 36), 12, 141)
  ctx.font = '11px system-ui, "Segoe UI", sans-serif'
  ctx.fillText(`DNI: ${recortar(dni, 22)}`, 12, 157)
}

function aBase64(canvas) {
  return canvas.toDataURL('image/png').split(',')[1]
}

export function pintarPantalla(canvas, opciones = {}) {
  const { paso = PASO_BIENVENIDA, nombre = '', dni = '' } = opciones

  const ctx = canvas.getContext('2d')
  ctx.fillStyle = '#ffffff'
  ctx.fillRect(0, 0, ANCHO, ALTO)
  ctx.textAlign = 'left'
  ctx.textBaseline = 'alphabetic'

  if (paso === PASO_BIENVENIDA) {
    ctx.fillStyle = '#000000'
    ctx.font = 'bold 15px system-ui, "Segoe UI", sans-serif'
    ctx.textAlign = 'center'
    ctx.fillText(BIENVENIDA[0], ANCHO / 2, 92)
    ctx.fillText(BIENVENIDA[1], ANCHO / 2, 114)
    ctx.textAlign = 'left'
    return aBase64(canvas)
  }

  ctx.strokeStyle = '#000000'
  ctx.lineWidth = 2
  ctx.beginPath()
  ctx.moveTo(0, LINEA_SEPARADOR)
  ctx.lineTo(ANCHO, LINEA_SEPARADOR)
  ctx.stroke()

  if (paso === PASO_ESPERA) {
    ctx.fillStyle = '#000000'
    ctx.font = 'bold 15px system-ui, "Segoe UI", sans-serif'
    ctx.textAlign = 'center'
    ctx.fillText('Firma enviada', ANCHO / 2, 56)
    ctx.font = '12px system-ui, "Segoe UI", sans-serif'
    ctx.fillText('Espere la verificación', ANCHO / 2, 78)
    ctx.textAlign = 'left'
    datosEmpleado(ctx, nombre, dni)
    return aBase64(canvas)
  }

  if (paso === PASO_CANCELADA) {
    ctx.fillStyle = '#000000'
    ctx.font = 'bold 14px system-ui, "Segoe UI", sans-serif'
    ctx.textAlign = 'center'
    ctx.fillText('Firma cancelada', ANCHO / 2, 50)
    ctx.font = '11px system-ui, "Segoe UI", sans-serif'
    ctx.fillText('Inténtelo más tarde', ANCHO / 2, 72)
    ctx.textAlign = 'left'
    datosEmpleado(ctx, nombre, dni)
    return aBase64(canvas)
  }

  if (paso === PASO_GUARDADA) {
    ctx.fillStyle = '#000000'
    ctx.font = 'bold 14px system-ui, "Segoe UI", sans-serif'
    ctx.textAlign = 'center'
    ctx.fillText('Firma guardada', ANCHO / 2, 50)
    ctx.font = '11px system-ui, "Segoe UI", sans-serif'
    ctx.fillText('Correctamente', ANCHO / 2, 72)
    ctx.textAlign = 'left'
    datosEmpleado(ctx, nombre, dni)
    return aBase64(canvas)
  }

  if (paso === PASO_RECHAZADA) {
    ctx.fillStyle = '#000000'
    ctx.font = 'bold 14px system-ui, "Segoe UI", sans-serif'
    ctx.textAlign = 'center'
    ctx.fillText('Firma no aceptada', ANCHO / 2, 50)
    ctx.font = '11px system-ui, "Segoe UI", sans-serif'
    ctx.fillText('Vuelva a intentarlo', ANCHO / 2, 72)
    ctx.textAlign = 'left'
    datosEmpleado(ctx, nombre, dni)
    return aBase64(canvas)
  }

  ctx.setLineDash([6, 5])
  ctx.strokeRect(6, 6, ANCHO - 12, LINEA_SEPARADOR - 12)
  ctx.setLineDash([])

  // En la pantalla de firma no se escribe ninguna pista: el area queda libre
  // para el trazo y la tableta dibuja la tinta encima.
  if (paso !== PASO_FIRMA) {
    ctx.fillStyle = '#000000'
    ctx.font = '14px system-ui, "Segoe UI", sans-serif'
    ctx.textAlign = 'center'
    ctx.fillText('Revise sus datos', ANCHO / 2, 66)
    ctx.textAlign = 'left'
  }

  datosEmpleado(ctx, nombre, dni)

  for (const b of botonesDe(paso)) {
    boton(ctx, b, b.texto, b.clave === 'guardar')
  }

  return aBase64(canvas)
}

export function botonEn(x, y, botones = []) {
  for (const b of botones) {
    if (x >= b.x && x < b.x + b.w && y >= b.y && y < b.y + b.h) {
      return b.clave
    }
  }
  return null
}