<template>
  <section class="panel panel-captura">
    <div class="panel-head">
      <div class="titulo-panel">
        <span class="icono-panel" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 17.5 16.2 4.3a2 2 0 0 1 2.8 0l1.7 1.7a2 2 0 0 1 0 2.8L7.5 22H3z" />
            <path d="M14 6.5 17.5 10" />
          </svg>
        </span>
        <div>
          <h2>{{ empleado ? 'Actualizar firma' : 'Captura de firma' }}</h2>
          <p class="subtitulo">
            {{ empleado ? `${empleado.nombre} · DNI ${empleado.dni}` : 'Registre al empleado y capture su firma en la tableta' }}
          </p>
        </div>
      </div>
      <span :class="['badge', servicioBadge.clase]">
        <i :class="['punto', servicioBadge.clase]" aria-hidden="true" />
        {{ servicioBadge.texto }}
      </span>
    </div>

    <ol class="pasos" aria-label="Progreso de la firma">
      <li
        v-for="(titulo, i) in PASOS"
        :key="titulo"
        :class="{ hecho: i < pasoIndice, activo: i === pasoIndice }"
        :aria-current="i === pasoIndice ? 'step' : undefined"
      >
        <span class="paso-num">{{ i < pasoIndice ? '✓' : i + 1 }}</span>
        <span class="paso-txt">{{ titulo }}</span>
      </li>
    </ol>

    <div v-if="empleado" class="aviso">
      <span>Actualizando firma y datos de <strong>{{ empleado.nombre }}</strong></span>
      <button class="btn small ghost" @click="$emit('cancelar')" type="button">Quitar</button>
    </div>

    <div class="campos">
      <label class="campo">
        <span>Nombre completo</span>
        <input
          type="text"
          placeholder="Nombres y apellidos"
          v-model="nombreActual"
          maxlength="120"
          @input="limpiarSolicitudCancelada"
          :disabled="datosBloqueados"
        />
      </label>
      <label class="campo">
        <span>DNI</span>
        <input
          type="text"
          placeholder="Documento de identidad"
          v-model="dniActual"
          maxlength="12"
          inputmode="numeric"
          @input="onDniInput"
          :disabled="datosBloqueados"
        />
        <span v-if="dniStatus === 'checking'" class="dni-checking">Verificando DNI...</span>
        <div v-if="dniStatus === 'duplicate'" class="dni-duplicate">
          <span>⚠️ El DNI <strong>{{ dniExistente?.dni }}</strong> ya está registrado por <strong>{{ dniExistente?.nombre }}</strong></span>
          <button class="btn-link" @click="buscarDniExistente" type="button">Buscar</button>
        </div>
      </label>
    </div>

    <div class="firma-panel">
      <div class="firma-cabecera">
        <span class="rotulo">Firma del empleado</span>
        <span :class="['badge', hayFirma ? 'ok' : 'neutral']">
          {{ hayFirma ? 'Firma capturada' : 'Sin firma' }}
        </span>
      </div>

      <div :class="['firma-lienzo', { 'con-tinta': hayFirma }]">
        <canvas
          class="tinta"
          ref="tintaRef"
          :width="AREA_FIRMA.w"
          :height="AREA_FIRMA.h"
          @pointerdown="alEmpezarTrazo"
          @pointermove="alSeguirTrazo"
          @pointerup="soltarRaton"
          @pointerleave="soltarRaton"
        />
        <span v-if="!hayFirma" class="firma-pista">Firme en la tableta</span>
        <span class="firma-renglon" aria-hidden="true" />
      </div>

      <p :class="['estado', tonoEstado(estado)]">{{ estado }}</p>

      <details v-if="jsonEntrada || jsonSalida" class="json-firma">
        <summary>JSON profesional de firma</summary>
        <pre>{{ jsonSalida || jsonEntrada }}</pre>
      </details>
    </div>

    <div v-if="solicitud" :class="['solicitud', solicitud.estado]">
      <div class="solicitud-head">
        <div>
          <strong>
            {{ solicitud.estado === 'pendiente'
              ? 'Firma recibida · pendiente de verificación'
              : 'Proceso cancelado por el empleado' }}
          </strong>
          <p class="solicitud-datos">
            {{ solicitud.nombre || 'Sin nombre' }} · DNI {{ solicitud.dni || '—' }}
          </p>
        </div>
        <span :class="['badge', solicitud.estado === 'pendiente' ? 'wait' : 'danger']">
          {{ solicitud.estado === 'pendiente' ? 'Pendiente' : 'Cancelada' }}
        </span>
      </div>

      <div v-if="solicitud.firma_base64" class="firma-img">
        <img :src="imagenFirma(solicitud.firma_base64)" alt="Firma enviada por el empleado" />
      </div>
      <p v-else class="solicitud-nota">No se recibió ninguna firma y no se guardó ningún registro.</p>

      <div class="acciones">
        <div v-if="solicitud.estado === 'pendiente'">
          <button class="btn primary" @click="aceptarSolicitud" :disabled="guardando" type="button">
            {{ guardando ? 'Guardando...' : 'Aceptar y guardar' }}
          </button>
          <button class="btn" @click="volverASolicitar" type="button" :disabled="guardando">Volver a solicitar</button>
        </div>
        <div v-else>
          <button class="btn primary" @click="continuar" type="button">Nuevo registro</button>
          <button class="btn" @click="volverASolicitar" type="button">Reintentar firma</button>
        </div>
      </div>
    </div>

    <div class="acciones barra-acciones">
      <button
        v-if="paso === PASO_DATOS"
        class="btn primary"
        @click="continuarAFirma"
        :disabled="dniStatus === 'duplicate' && !empleado"
        type="button"
      >
        Continuar a la firma
        <span v-if="dniStatus === 'duplicate' && !empleado"> ⛔ DNI duplicado</span>
      </button>

      <div v-if="paso === PASO_FIRMA">
        <button class="btn" @click="limpiarFirma" type="button">Limpiar firma</button>
        <button class="btn" @click="cancelarPeticion" type="button">Cancelar proceso</button>
        <button
          class="btn"
          @click="omitirFirma"
          :disabled="guardando || (dniStatus === 'duplicate' && !empleado)"
          type="button"
        >
          Omitir firma
          <span v-if="dniStatus === 'duplicate' && !empleado"> ⛔</span>
        </button>
        <button
          class="btn primary"
          @click="enviarSolicitud"
          :disabled="!hayFirma || (dniStatus === 'duplicate' && !empleado)"
          type="button"
        >
          Enviar firma
          <span v-if="dniStatus === 'duplicate' && !empleado"> ⛔</span>
        </button>
      </div>

      <p v-if="paso === PASO_ESPERA && !solicitud" class="estado">Esperando la verificación del operador...</p>
      <p v-if="paso === PASO_BIENVENIDA && !solicitud" class="estado">Escriba el nombre y el DNI del empleado para empezar.</p>
    </div>

    <canvas
      ref="uiRef"
      class="pantalla-tableta"
      :width="ANCHO"
      :height="ALTO"
      aria-hidden="true"
    />

    <div v-if="dniDuplicado?.dni" class="modal-fondo" @click="cerrarDuplicado">
      <div class="modal modal-edit" @click.stop>
        <div class="modal-head">
          <h3>DNI ya registrado</h3>
        </div>
        <p class="modal-datos">
          El DNI <strong>{{ dniDuplicado.dni }}</strong> ya pertenece a:<br />
          <strong>{{ dniDuplicado.nombre }}</strong> (ID {{ dniDuplicado.id }})
          {{ dniDuplicado.tiene_firma ? ' · Ya tiene firma' : ' · Pendiente de firma' }}
        </p>
        <div class="modal-acciones">
          <button class="btn primary" @click="irAEditarDuplicado" type="button">Buscar empleado</button>
          <button class="btn ghost" @click="cerrarDuplicado" type="button">Cancelar</button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch, nextTick, shallowRef, type Ref } from 'vue'
import { listarEmpleados, registrarEmpleado, guardarFirmaEmpleado, iniciarFirma } from '~/composables/api'
import {
  ANCHO, ALTO, AREA_FIRMA,
  PASO_BIENVENIDA, PASO_DATOS, PASO_FIRMA, PASO_ESPERA,
  PASO_CANCELADA, PASO_GUARDADA, PASO_RECHAZADA,
  ACCIONES_BOTON, accionDeBoton, botonEn, botonesDe, pintarPantalla
} from '~/tablet'
import { comprobar } from '~/stu'
import { conectarTableta, soltarTableta } from '~/conexion'

const STU = () => (window as any).WacomGSS?.STU

const FASE_INICIO = 'inicio'
const PASOS = ['Datos', 'Firma', 'Verificación']

const emit = defineEmits<{
  (e: 'saved'): void
  (e: 'cancelar'): void
  (e: 'buscar-dni', dni: string): void
}>()

const props = defineProps<{
  empleado?: any
  onSaved?: () => void
  onCancelar?: () => void
  onSeleccionar?: (emp: any) => void
  onBuscarDNI?: (dni: string) => void
}>()

const uiRef = shallowRef<HTMLCanvasElement | null>(null)
const tintaRef = shallowRef<HTMLCanvasElement | null>(null)

const tabletRef = shallowRef<any>(null)
const protocolRef = shallowRef<any>(null)
const handlerRef = shallowRef<any>(null)
const usbRef = shallowRef<any>(null)
const conectadoRef = ref(false)
const dibujandoRef = ref(false)
const botonRef = ref<string | null>(null)
const lapizAbajoRef = ref(false)
const ratonRef = ref(false)
const firmandoRef = ref(false)
const avisoDatosRef = ref({ nombre: '', dni: '' })
const procesoRef = ref({ paso: PASO_BIENVENIDA })

const estado = ref('Esperando servicio SigCaptX...')
const servicioListo = ref(false)
const conectada = ref(false)
const hayFirma = ref(false)
const nombre = ref('')
const dni = ref('')
const guardando = ref(false)
const fase = ref(props.empleado ? PASO_FIRMA : FASE_INICIO)
const avisoTableta = ref<string | null>(null)
const dniStatus = ref<'checking' | 'valid' | 'duplicate' | null>(null)
const dniExistente = ref<any>(null)
const dniDuplicado = ref<any>(null)
const solicitud = ref<any>(null)
const jsonEntrada = ref('')
const jsonSalida = ref('')
const pantallaFirmaPintada = ref(false)

const actualizando = computed(() => !!props.empleado)
const nombreActual = computed({
  get: () => actualizando.value ? nombre.value : (props.empleado?.nombre || nombre.value),
  set: (v: string) => { nombre.value = v }
})
const dniActual = computed({
  get: () => actualizando.value ? dni.value : (props.empleado?.dni || dni.value),
  set: (v: string) => { dni.value = v }
})

const pasoTableta = computed(() =>
  avisoTableta.value
    ?? (dniDuplicado.value?.dni
      ? PASO_BIENVENIDA
      : (fase.value !== FASE_INICIO ? fase.value : PASO_BIENVENIDA))
)

const hayDatos = computed(() => nombreActual.value.trim() !== '' && dniActual.value.trim() !== '')
const paso = computed(() =>
  fase.value !== FASE_INICIO ? fase.value
    : (hayDatos.value && !props.empleado && !dniDuplicado.value?.dni ? PASO_DATOS : PASO_BIENVENIDA)
)

const pasoIndice = computed(() =>
  ({ [PASO_BIENVENIDA]: 0, [PASO_DATOS]: 0, [PASO_FIRMA]: 1, [PASO_ESPERA]: 2 } as Record<string, number>)[paso.value] ?? 0
)

const datosBloqueados = computed(() =>
  (paso.value === PASO_FIRMA || paso.value === PASO_ESPERA) && !props.empleado
)

const servicioBadge = computed(() =>
  servicioListo.value
    ? { texto: conectada.value ? 'STU-430 conectada' : 'Servicio activo', clase: conectada.value ? 'ok' : 'wait' }
    : { texto: 'Conectando...', clase: 'wait' }
)

function mensajeError(e: any) {
  const texto = String(e?.message || e)
  if (/utilizado por otro proceso|sharing violation|access is denied/i.test(texto)) {
    return '⚠️ La tableta está ocupada por otro programa o pestaña. Ciérralos y espera: se reconecta solo.'
  }
  return `⚠️ ${texto}`
}

function soloDigitos(valor: string) {
  return String(valor || '').replace(/\D/g, '')
}

function imagenFirma(base64: string) {
  if (!base64) return ''
  return base64.startsWith('data:') ? base64 : `data:image/png;base64,${base64}`
}

function tonoEstado(texto: string) {
  if (texto.startsWith('✅')) return 'ok'
  if (texto.startsWith('❌')) return 'error'
  if (texto.startsWith('⚠️')) return 'warn'
  if (texto.startsWith('ℹ️')) return 'info'
  return ''
}

function recortar(texto: string, maximo: number) {
  const limpio = (texto || '').trim()
  return limpio.length > maximo ? `${limpio.slice(0, maximo - 1)}…` : limpio
}

function boton(ctx: CanvasRenderingContext2D, rect: any, texto: string, doble = false) {
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

function datosEmpleado(ctx: CanvasRenderingContext2D, nombre: string, dni: string) {
  ctx.fillStyle = '#000000'
  ctx.font = 'bold 13px system-ui, "Segoe UI", sans-serif'
  ctx.fillText(recortar(nombre, 36), 12, 141)
  ctx.font = '11px system-ui, "Segoe UI", sans-serif'
  ctx.fillText(`DNI: ${recortar(dni, 22)}`, 12, 157)
}

function aBase64(canvas: HTMLCanvasElement) {
  return canvas.toDataURL('image/png').split(',')[1]
}

const enviarPantalla = async () => {
  const canvas = uiRef.value
  if (!canvas) return

  const paso = pasoTableta.value
  const nombreTabla = avisoTableta.value ? avisoDatosRef.value.nombre : nombreActual.value.trim()
  const dniTabla = avisoTableta.value ? avisoDatosRef.value.dni : dniActual.value.trim()

  // En modo tinta la tableta dibuja los trazos en su propia pantalla: escribir
  // una imagen encima borra la firma que se esta haciendo. Por eso la pantalla
  // de firma se escribe una sola vez, antes de que el lapicero toque la tableta.
  if (paso === PASO_FIRMA && (pantallaFirmaPintada.value || hayFirma.value || lapizAbajoRef.value)) return

  const b64 = pintarPantalla(canvas, {
    paso,
    nombre: nombreTabla,
    dni: dniTabla,
  })

  const tablet = tabletRef.value
  if (!tablet) return

  try {
    const modo = new (STU().Protocol)().EncodingMode.EncodingMode_1bit
    const plano = comprobar(
      await STU().ProtocolHelper.flatten(modo, b64, ANCHO, ALTO, false)
    )

    if (typeof plano !== 'string') {
      throw new Error('la tableta no devolvio la imagen')
    }

    if (paso === PASO_FIRMA && (hayFirma.value || lapizAbajoRef.value)) return

    comprobar(await tablet.writeImage(modo, plano))
    if (paso === PASO_FIRMA) pantallaFirmaPintada.value = true
  } catch (e: any) {
    estado.value = `⚠️ No se pudo actualizar la pantalla de la tableta: ${e.message}`
  }
}

const limpiarTinta = () => {
  const canvas = tintaRef.value
  if (canvas) canvas.getContext('2d')!.clearRect(0, 0, canvas.width, canvas.height)
  dibujandoRef.value = false
  botonRef.value = null
  lapizAbajoRef.value = false
  firmandoRef.value = false
  hayFirma.value = false
}

const firmaPng = () => {
  const canvas = tintaRef.value!
  const fondo = document.createElement('canvas')
  fondo.width = canvas.width
  fondo.height = canvas.height
  const ctx = fondo.getContext('2d')!
  ctx.fillStyle = '#ffffff'
  ctx.fillRect(0, 0, fondo.width, fondo.height)
  ctx.drawImage(canvas, 0, 0)
  return fondo.toDataURL('image/png')
}

const empezarTinta = () => {
  if (firmandoRef.value) return
  firmandoRef.value = true
}

const alMoverLapiz = (pen: any) => {
  const px = (pen.x * ANCHO) / 9600
  const py = (pen.y * ALTO) / 6000

  const abajo = pen.sw !== 0
  const bajada = abajo && !lapizAbajoRef.value
  lapizAbajoRef.value = abajo

  if (!abajo) {
    dibujandoRef.value = false
    const pulsado = botonRef.value
    botonRef.value = null
    if (pulsado) accionesRef.current.boton?.(pulsado)
    return
  }

  const { paso: pasoActual } = procesoRef.value

  if (bajada) {
    botonRef.value = botonEn(px, py, botonesDe(pasoActual))
    if (botonRef.value) {
      dibujandoRef.value = false
      return
    }
  }

  if (botonRef.value) return

  if (pasoActual !== PASO_FIRMA) return
  if (px < AREA_FIRMA.x || px > AREA_FIRMA.x + AREA_FIRMA.w) return
  if (py < AREA_FIRMA.y || py > AREA_FIRMA.y + AREA_FIRMA.h) return

  const canvas = tintaRef.value
  if (!canvas) return

  const ctx = canvas.getContext('2d')!
  const x = px - AREA_FIRMA.x
  const y = py - AREA_FIRMA.y

  if (!dibujandoRef.value) {
    ctx.beginPath()
    ctx.moveTo(x, y)
    ctx.lineTo(x + 0.1, y)
    ctx.stroke()
    dibujandoRef.value = true
    hayFirma.value = true
    empezarTinta()
  } else {
    ctx.lineTo(x, y)
    ctx.stroke()
  }
}

const puntoDelRaton = (e: PointerEvent) => {
  const canvas = tintaRef.value
  if (!canvas) return null

  const caja = canvas.getBoundingClientRect()
  if (!caja.width || !caja.height) return null

  return {
    x: ((e.clientX - caja.left) / caja.width) * canvas.width,
    y: ((e.clientY - caja.top) / caja.height) * canvas.height,
  }
}

const alEmpezarTrazo = (e: PointerEvent) => {
  if (procesoRef.value.paso !== PASO_FIRMA) return

  const punto = puntoDelRaton(e)
  const canvas = tintaRef.value
  if (!punto || !canvas) return

  const ctx = canvas.getContext('2d')!
  ctx.beginPath()
  ctx.moveTo(punto.x, punto.y)
  ctx.lineTo(punto.x + 0.1, punto.y)
  ctx.stroke()

  ratonRef.value = true
  hayFirma.value = true
  empezarTinta()
}

const alSeguirTrazo = (e: PointerEvent) => {
  if (!ratonRef.value) return

  const punto = puntoDelRaton(e)
  const canvas = tintaRef.value
  if (!punto || !canvas) return

  canvas.getContext('2d')!.lineTo(punto.x, punto.y)
  canvas.getContext('2d')!.stroke()
}

const soltarRaton = () => {
  ratonRef.value = false
}

const buscarPorDni = async (dniBuscado: string) => {
  const wanted = soloDigitos(dniBuscado)
  if (!wanted) return null

  const datos = await listarEmpleados(dniBuscado)
  return datos.find((e: any) => soloDigitos(e.dni) === wanted) ?? null
}

const verificarDniUnico = async (dniValor: string) => {
  const existente = await buscarPorDni(dniValor)
  if (existente && (!props.empleado || existente.id !== props.empleado.id)) {
    dniDuplicado.value = existente
  } else {
    dniDuplicado.value = null
  }
  return existente
}

const validarDniTiempoReal = (valor: string) => {
  const digits = soloDigitos(valor)
  if (digits.length < 6) {
    dniStatus.value = null
    dniExistente.value = null
    return
  }
  dniStatus.value = 'checking'
  clearTimeout(validarDniTiempoReal.timer as any)
  validarDniTiempoReal.timer = setTimeout(async () => {
    const existente = await buscarPorDni(digits)
    if (existente) {
      dniStatus.value = 'duplicate'
      dniExistente.value = existente
    } else {
      dniStatus.value = 'valid'
      dniExistente.value = null
    }
  }, 400)
}

const buscarDniExistente = () => {
  if (dniExistente.value) {
    emit('seleccionar', dniExistente.value)
    dniStatus.value = null
    dniExistente.value = null
  }
}

const continuarAFirma = async () => {
  const nombreTxt = nombreActual.value.trim()
  const dniTxt = dniActual.value.trim()

  if (!nombreTxt || !dniTxt) {
    estado.value = '⚠️ Completa el nombre y el DNI antes de continuar.'
    return
  }

  if (!props.empleado) {
    const existente = await buscarPorDni(dniTxt)
    if (existente) {
      dniDuplicado.value = existente
      return
    }
  }

  try {
    const entrada = await iniciarFirma({ nombre: nombreTxt, dni: dniTxt })
    jsonEntrada.value = JSON.stringify(entrada, null, 2)
    jsonSalida.value = ''
  } catch (e: any) {
    estado.value = `❌ ${e.message}`
    return
  }

  limpiarTinta()
  solicitud.value = null
  fase.value = PASO_FIRMA
  // La pantalla de firma se escribe antes de firmar: si se dibuja despues, la
  // tableta borra el primer trazo que este haciendo el empleado.
  pantallaFirmaPintada.value = false
  await enviarPantalla()
  estado.value = 'El empleado puede firmar en la tableta: BORRAR limpia, CANCELAR anula y GUARDAR envía.'
}

const mostrarAvisoTemporal = (pasoAviso: string, duracion = 2500) => {
  avisoDatosRef.value = { nombre: nombreActual.value.trim(), dni: dniActual.value.trim() }
  avisoTableta.value = pasoAviso
  setTimeout(() => {
    avisoTableta.value = null
  }, duracion)
}

const limpiarFirma = () => {
  limpiarTinta()
  estado.value = 'Firma borrada. El empleado puede volver a firmar.'
  pantallaFirmaPintada.value = false
  enviarPantalla()
}

const cancelarPeticion = () => {
  limpiarTinta()
  solicitud.value = {
    nombre: nombreActual.value.trim(),
    dni: dniActual.value.trim(),
    estado: 'denegada',
    fecha: new Date().toISOString(),
  }
  nombre.value = ''
  dni.value = ''
  fase.value = FASE_INICIO
  estado.value = '❌ El empleado canceló el proceso de firma. No se guardó nada.'
  mostrarAvisoTemporal(PASO_CANCELADA)
}

const omitirFirma = async () => {
  if (guardando.value) return

  const nombreTxt = nombreActual.value.trim()
  const dniTxt = dniActual.value.trim()

  if (!nombreTxt || !dniTxt) {
    estado.value = '⚠️ Completa el nombre y el DNI antes de omitir la firma.'
    return
  }

  const yaExiste = await verificarDniUnico(dniTxt)
  if (yaExiste) {
    dniDuplicado.value = yaExiste
    return
  }

  guardando.value = true
  try {
    await registrarEmpleado({ nombre: nombreTxt, dni: dniTxt })
    estado.value = `✅ ${nombreTxt} registrado SIN firma. Queda pendiente de firma en el registro.`

    limpiarTinta()
    solicitud.value = null
    nombre.value = ''
    dni.value = ''
    fase.value = FASE_INICIO
    emit('saved')
    avisoTableta.value = null
  } catch (e: any) {
    estado.value = `❌ ${e.message}`
  } finally {
    guardando.value = false
  }
}

const enviarSolicitud = () => {
  if (!hayFirma.value) {
    estado.value = '⚠️ El empleado debe firmar antes de enviar la solicitud.'
    return
  }

  const dniTxt = dniActual.value.trim()
  verificarDniUnico(dniTxt).then((yaExiste) => {
    if (yaExiste && (!props.empleado || yaExiste.id !== props.empleado.id)) {
      dniDuplicado.value = yaExiste
      return
    }

    solicitud.value = {
      nombre: nombreActual.value.trim(),
      dni: dniTxt,
      firma_base64: firmaPng(),
      estado: 'pendiente',
      fecha: new Date().toISOString(),
    }
    fase.value = PASO_ESPERA
    estado.value = 'Firma recibida. Revise la firma y pulse "Aceptar y guardar" para registrarla.'
  })
}

const aceptarSolicitud = async () => {
  if (guardando.value || !solicitud.value || solicitud.value.estado !== 'pendiente') return

  guardando.value = true
  try {
    let resultado: any
    if (props.empleado) {
      resultado = await guardarFirmaEmpleado(props.empleado.id, solicitud.value.firma_base64, {
        nombre: nombreActual.value.trim(),
        dni: dniActual.value.trim(),
      })
      estado.value = `✅ Firma verificada y guardada de ${solicitud.value.nombre}.`
    } else {
      const yaExiste = await buscarPorDni(solicitud.value.dni)

      if (yaExiste) {
        resultado = await guardarFirmaEmpleado(yaExiste.id, solicitud.value.firma_base64, {
          nombre: solicitud.value.nombre,
          dni: solicitud.value.dni,
        })
        estado.value = `✅ Firma verificada y guardada de ${solicitud.value.nombre} (ID ${yaExiste.id}).`
      } else {
        resultado = await registrarEmpleado({
          nombre: solicitud.value.nombre,
          dni: solicitud.value.dni,
          firma_base64: solicitud.value.firma_base64,
        })
        estado.value = `✅ ${solicitud.value.nombre} registrado con su firma.`
      }
    }

    jsonSalida.value = JSON.stringify(resultado, null, 2)

    limpiarTinta()
    solicitud.value = null
    nombre.value = ''
    dni.value = ''
    fase.value = FASE_INICIO
    emit('saved')
    if (props.empleado) emit('cancelar')
    mostrarAvisoTemporal(PASO_GUARDADA)
  } catch (e: any) {
    estado.value = `❌ ${e.message}`
  } finally {
    guardando.value = false
  }
}

const volverASolicitar = () => {
  if (solicitud.value) {
    nombre.value = solicitud.value.nombre ?? ''
    dni.value = solicitud.value.dni ?? ''
  }
  solicitud.value = null
  limpiarTinta()
  pantallaFirmaPintada.value = false
  fase.value = actualizando.value ? PASO_FIRMA : FASE_INICIO
  estado.value = actualizando.value
    ? 'Vuelve a solicitar la firma al empleado.'
    : 'Escriba otra vez los datos y continúe a la firma.'
  mostrarAvisoTemporal(PASO_RECHAZADA)
}

const continuar = () => {
  solicitud.value = null
  nombre.value = ''
  dni.value = ''
  limpiarTinta()
  fase.value = FASE_INICIO
  estado.value = 'Escriba el nombre y el DNI del siguiente empleado.'
  avisoTableta.value = null
}

const irAEditarDuplicado = () => {
  if (dniDuplicado.value?.dni) {
    emit('buscar-dni', dniDuplicado.value.dni)
    dniDuplicado.value = null
  }
}

const cerrarDuplicado = () => {
  dniDuplicado.value = null
}

const limpiarSolicitudCancelada = () => {
  if (solicitud.value?.estado === 'denegada') solicitud.value = null
}

const onDniInput = (e: Event) => {
  const valor = (e.target as HTMLInputElement).value
  dni.value = valor
  limpiarSolicitudCancelada()
  if (!props.empleado && valor.trim().length >= 6) {
    validarDniTiempoReal(valor)
  } else {
    dniStatus.value = null
    dniExistente.value = null
  }
}

const accionesRef = { current: { boton: (clave: string) => {
  const accion = accionDeBoton(clave)
  if (accion === 'limpiar') limpiarFirma()
  else if (accion === 'cancelar') cancelarPeticion()
  else if (accion === 'enviar') enviarSolicitud()
}, pantalla: enviarPantalla } }

// El modo tinta hace que la tableta dibuje los trazos en su pantalla. Solo se
// activa en el paso de firma: en bienvenida (o en cualquier otra pantalla) el
// lapicero no marca nada.
const modoTinta = async (activo: boolean) => {
  const protocol = protocolRef.value
  if (!protocol) return
  try {
    await protocol.setInkingMode(activo ? 1 : 0)
  } catch { /* firmware sin modo tinta */ }
}

watch(() => pasoTableta.value, (paso) => {
  procesoRef.value.paso = paso
  modoTinta(paso === PASO_FIRMA)
})

let activo = true
let reintentoTimer: any = null

const desconectar = () => {
  soltarTableta({
    handler: handlerRef.value,
    protocol: protocolRef.value,
    tablet: tabletRef.value,
    usb: conectadoRef.value ? usbRef.value : null,
  })
  handlerRef.value = null
  tabletRef.value = null
  protocolRef.value = null
  usbRef.value = null
  conectadoRef.value = false
  conectada.value = false
}

const conectar = async () => {
  if (!activo) return
  let sesion: any = null
  try {
    const devices = await STU().getUsbDevices()
    if (!activo) return
    if (!devices.length) throw new Error('El servicio SigCaptX no ve ninguna tableta.')

    sesion = await conectarTableta(STU(), devices, {
      onLapiz: alMoverLapiz,
      areaEscritura: {
        upperLeftXpixel: AREA_FIRMA.x,
        upperLeftYpixel: AREA_FIRMA.y,
        lowerRightXpixel: AREA_FIRMA.x + AREA_FIRMA.w,
        lowerRightYpixel: AREA_FIRMA.y + AREA_FIRMA.h,
      },
    })
    if (!activo) { soltarTableta(sesion); return }

    usbRef.value = sesion.usb
    conectadoRef.value = true
    protocolRef.value = sesion.protocol
    tabletRef.value = sesion.tablet
    handlerRef.value = sesion.handler

    conectada.value = true
    await modoTinta(pasoTableta.value === PASO_FIRMA)
    await enviarPantalla()
    estado.value = 'Tableta lista. Escribe el nombre y el DNI: se mostraran en su pantalla.'
  } catch (e: any) {
    desconectar()
    if (!activo) return
    const msg = mensajeError(e)
    estado.value = msg
    const ocupado = /utilizado por otro proceso|sharing violation|access is denied/i.test(String(e?.message || e))
    if (ocupado) {
      try { STU()?.Reinitialize() } catch { /* ignore */ }
      reintentoTimer = setTimeout(conectar, 1000)
    } else {
      reintentoTimer = setTimeout(conectar, 2000)
    }
  }
}

const esperarServicio = () => new Promise<void>((resolve, reject) => {
  let ultimoIntento = 0
  let terminado = false

  const limite = setTimeout(() => {
    if (terminado) return
    terminado = true
    reject(new Error('El servicio Wacom SigCaptX no responde. Inícialo en Windows y recarga la página.'))
  }, 15000)

  const revisar = () => {
    if (terminado || !activo) return

    if (!STU()) {
      terminado = true
      clearTimeout(limite)
      reject(new Error('El SDK de Wacom no está cargado.'))
      return
    }

    if (STU()?.isServiceReady()) {
      terminado = true
      clearTimeout(limite)
      resolve()
      return
    }

    const ahora = Date.now()
    if (ahora - ultimoIntento > 3000) {
      ultimoIntento = ahora
      try { STU()?.Reinitialize() } catch { /* ignore */ }
    }
    setTimeout(revisar, 500)
  }
  revisar()
})

/**
 * El SDK de Wacom (wgssStuSdk.js) viene con la instalación de SigCaptX y
 * debe copiarse a public/. Si no está, se avisa con claridad en lugar de
 * dejar la pantalla esperando indefinidamente.
 */
let sdkCargando: Promise<void> | null = null

const cargarScript = (src: string) => new Promise<void>((resolve, reject) => {
  const script = document.createElement('script')
  script.src = src
  script.async = true
  script.onload = () => resolve()
  script.onerror = () => reject(new Error(`No se pudo cargar ${src} desde public/.`))
  document.head.appendChild(script)
})

const cargarSdk = () => {
  if ((window as any).WacomGSS) return Promise.resolve()

  if (sdkCargando) return sdkCargando

  // q.js debe cargarse antes: wgssStuSdk.js depende de la librería Q.
  sdkCargando = (async () => {
    if (!(window as any).Q) await cargarScript('/q.js')
    await cargarScript('/wgssStuSdk.js')
    if (!(window as any).WacomGSS) {
      throw new Error(
        'Falta wgssStuSdk.js en public/. Copia ese archivo (y q.js) desde la instalación de Wacom SigCaptX (Development Kit).'
      )
    }
  })().catch((e) => {
    sdkCargando = null
    throw e
  })

  return sdkCargando
}

const alSalir = () => desconectar()

onMounted(async () => {
  await nextTick()
  const ctx = tintaRef.value?.getContext('2d')
  if (ctx) {
    ctx.strokeStyle = '#0f172a'
    ctx.lineWidth = 2.5
    ctx.lineCap = 'round'
    ctx.lineJoin = 'round'
  }

  window.addEventListener('beforeunload', alSalir)
  window.addEventListener('pagehide', alSalir)

  try {
    await cargarSdk()
    await esperarServicio()
    if (!activo) return
    servicioListo.value = true
    estado.value = 'Servicio listo. Buscando tableta...'
    await conectar()
  } catch (e: any) {
    estado.value = mensajeError(e)
  }
})

onUnmounted(() => {
  activo = false
  clearTimeout(reintentoTimer)
  window.removeEventListener('beforeunload', alSalir)
  window.removeEventListener('pagehide', alSalir)
  desconectar()
})

watch([conectada, () => pasoTableta.value], () => {
  const timer = setTimeout(() => enviarPantalla(), 250)
  return () => clearTimeout(timer)
})

const idEmpleado = computed(() => props.empleado?.id ?? null)
let empleadoVisto: number | null = null

watch(
  [idEmpleado, () => props.empleado?.nombre, () => props.empleado?.dni],
  () => {
    if (!props.empleado) {
      if (empleadoVisto !== null) {
        empleadoVisto = null
        solicitud.value = null
        hayFirma.value = false
        fase.value = FASE_INICIO
      }
      return
    }

    const cambio = empleadoVisto !== props.empleado.id
      || nombre.value !== props.empleado.nombre
      || dni.value !== props.empleado.dni

    if (!cambio) return

    empleadoVisto = props.empleado.id
    nombre.value = props.empleado.nombre || ''
    dni.value = props.empleado.dni || ''
    solicitud.value = null
    hayFirma.value = false
    fase.value = PASO_FIRMA
    nextTick(() => {
      const canvas = tintaRef.value
      if (canvas) canvas.getContext('2d')!.clearRect(0, 0, canvas.width, canvas.height)
      firmandoRef.value = false
      pantallaFirmaPintada.value = false
    })
  },
  { immediate: true }
)
</script>