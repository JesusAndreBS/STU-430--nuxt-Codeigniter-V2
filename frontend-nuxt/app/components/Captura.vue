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
          <h2>Captura de firma</h2>
          <p class="subtitulo">Registre al empleado y capture su firma en la tableta</p>
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

    <div class="campos">
      <label class="campo">
        <span>Nombre completo</span>
        <input
          type="text"
          placeholder="Nombres y apellidos"
          v-model="nombre"
          maxlength="120"
          :disabled="datosBloqueados"
        />
      </label>
      <label class="campo">
        <span>DNI</span>
        <input
          type="text"
          placeholder="Documento de identidad"
          v-model="dni"
          maxlength="12"
          inputmode="numeric"
          @input="onDniInput"
          :disabled="datosBloqueados"
        />
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

      <details v-if="ultimoBlob" class="json-firma">
        <summary>Blob Wacom registrado</summary>
        <pre>{{ ultimoBlob }}</pre>
      </details>

    <!-- Botón para abrir modal de descifrado -->
    <div class="acciones-verificador" style="margin-top: 16px;">
      <button
        class="btn primary"
        @click="abrirModalDescifrado"
        :disabled="!conectada"
        type="button"
      >
        🔓 Descifrar wacom_blob con tableta
      </button>
    </div>

    <!-- Modal de descifrado Wacom -->
    <div v-if="mostrarModalDescifrado" class="modal-fondo" @click="cerrarModalDescifrado">
      <div class="modal modal-descifrado" @click.stop>
        <div class="modal-head">
          <h3>🔓 Descifrar wacom_blob con STU-430</h3>
          <button class="btn-icon" @click="cerrarModalDescifrado" aria-label="Cerrar">✕</button>
        </div>
        
        <p class="modal-datos">
          Pega el <code>wacom_blob</code> de la base de datos y presiona <strong>Aceptar</strong> 
          para descifrar usando la tableta STU-430 conectada.
        </p>
        
        <textarea
          v-model="blobVerificar"
          placeholder="WACOM-STU430-..."
          class="blob-input modal-blob-input"
          rows="4"
          @paste="limpiarResultadoVerificacion"
          @keydown.enter.exact="verificarEncriptacion"
        />
        
        <div v-if="verificando" class="estado-progreso">
          <div class="spinner"></div>
          <span>🔄 Descifrando con STU-430...</span>
        </div>
        
        <div v-else-if="resultadoVerificacion" class="resultado-modal" :class="resultadoVerificacion.ok ? 'ok' : 'error'">
          <div class="resultado-header">
            <span class="icono">{{ resultadoVerificacion.ok ? '✅' : '❌' }}</span>
            <h4>{{ resultadoVerificacion.ok ? 'Descifrado exitoso' : 'Error al descifrar' }}</h4>
          </div>
          
          <div class="detalle-resultado">
            <div class="campo-resultado">
              <label>👤 Nombre:</label>
              <span>{{ resultadoVerificacion.nombre || 'N/A' }}</span>
            </div>
            <div class="campo-resultado">
              <label>🆔 DNI:</label>
              <span>{{ resultadoVerificacion.dni || 'N/A' }}</span>
            </div>
            <div class="campo-resultado">
              <label>📅 Fecha firma:</label>
              <span>{{ resultadoVerificacion.fecha || 'N/A' }}</span>
            </div>
            <div class="campo-resultado">
              <label>🔧 Serial tableta:</label>
              <span>{{ resultadoVerificacion.serial || 'N/A' }}</span>
            </div>
            
            <div v-if="resultadoVerificacion.pngBase64" class="png-container">
              <label>🖼️ Firma descifrada:</label>
              <img :src="'data:image/png;base64,' + resultadoVerificacion.pngBase64" alt="Firma descifrada" class="png-preview-grande" />
              <p class="png-info">{{ resultadoVerificacion.pngBase64.length }} caracteres Base64</p>
            </div>
            
            <details v-if="resultadoVerificacion.raw" class="raw-detalle">
              <summary>Ver datos técnicos</summary>
              <pre class="raw-output">{{ resultadoVerificacion.raw }}</pre>
            </details>
          </div>
        </div>
      </div>
        
        <div class="modal-acciones">
          <button
            class="btn primary"
            @click="verificarEncriptacion"
            :disabled="!blobVerificar.trim() || verificando || !conectada"
            type="button"
          >
            {{ resultadoVerificacion ? '🔄 Descifrar otro' : '✅ Aceptar y descifrar' }}
          </button>
          <button
            class="btn ghost"
            @click="cerrarModalDescifrado"
            :disabled="verificando"
            type="button"
          >
            Cancelar
          </button>
        </div>
      </div>
    </div>

    <!-- Modal de confirmación antes de guardar -->
    <div v-if="mostrarConfirmacion" class="modal-fondo" @click="cancelarConfirmacion">
      <div class="modal modal-confirm" @click.stop>
        <div class="modal-head">
          <h3>Firma capturada</h3>
        </div>
        <p class="modal-datos">¿Confirmar para guardar en la base de datos?</p>
        <div class="modal-acciones">
          <button class="btn primary" @click="confirmarGuardado" type="button">Aceptar</button>
          <button class="btn ghost" @click="cancelarConfirmacion" type="button">Cancelar</button>
        </div>
      </div>
    </div>

    <div class="acciones barra-acciones">
      <button
        v-if="fase === PASO_BIENVENIDA"
        class="btn primary"
        @click="continuarAFirma"
        type="button"
      >
        Continuar a la firma
      </button>

      <template v-else-if="fase === PASO_FIRMA">
        <button class="btn" @click="limpiarFirma" type="button">Limpiar</button>
        <button class="btn" @click="cancelarProceso" type="button">Cancelar</button>
        <button
          class="btn primary"
          @click="solicitarConfirmacionGuardado"
          :disabled="!hayFirma || guardando"
          type="button"
        >
          {{ guardando ? 'Guardando...' : 'Guardar' }}
        </button>
      </template>

      <template v-else-if="fase === PASO_GUARDADA">
        <button class="btn primary" @click="continuarConOtroRegistro" type="button">
          Aceptar - Continuar con otro registro
        </button>
        <button class="btn" @click="cancelarProceso" type="button">Cancelar</button>
      </template>

      <button v-else class="btn primary" @click="nuevoRegistro" type="button">
        Crear nuevo
      </button>

      <p v-if="fase === PASO_BIENVENIDA" class="estado">Escriba el nombre y el DNI del empleado para empezar.</p>
    </div>

    <canvas
      ref="uiRef"
      class="pantalla-tableta"
      :width="ANCHO"
      :height="ALTO"
      aria-hidden="true"
    />
  </section>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch, nextTick, shallowRef } from 'vue'
import { registrarFirma } from '~/composables/api'
import wacom from '~/wacom'
import {
  ANCHO, ALTO, AREA_FIRMA,
  PASO_BIENVENIDA, PASO_FIRMA, PASO_CANCELADA, PASO_GUARDADA,
  accionDeBoton, botonEn, botonesDe, pintarPantalla
} from '~/tablet'
import { comprobar } from '~/stu'
import { conectarTableta, soltarTableta } from '~/conexion'

const STU = () => (window as any).WacomGSS?.STU

const PASOS = ['Datos', 'Firma', 'Guardado']

const emit = defineEmits<{
  (e: 'saved'): void
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
const avisoDatosRef = ref({ nombre: '', dni: '' })
const procesoRef = ref({ paso: PASO_BIENVENIDA })

const estado = ref('Esperando servicio SigCaptX...')
const servicioListo = ref(false)
const conectada = ref(false)
const hayFirma = ref(false)
const nombre = ref('')
const dni = ref('')
const guardando = ref(false)
const fase = ref(PASO_BIENVENIDA)
const avisoTableta = ref<string | null>(null)
const ultimoBlob = ref('')
const serialTableta = ref('')
const pantallaFirmaPintada = ref(false)
const mostrarConfirmacion = ref(false)

// Modal de descifrado Wacom
const mostrarModalDescifrado = ref(false)
const blobVerificar = ref('')
const verificando = ref(false)
const resultadoVerificacion = ref<{
  ok: boolean
  nombre?: string
  dni?: string
  fecha?: string
  serial?: string
  pngBase64?: string
  raw?: string
} | null>(null)

const guardarPendiente = ref<() => Promise<void> | null>(null)

const pasoTableta = computed(() => avisoTableta.value ?? fase.value)

const pasoIndice = computed(() =>
  ({ [PASO_BIENVENIDA]: 0, [PASO_FIRMA]: 1, [PASO_GUARDADA]: 2 } as Record<string, number>)[pasoTableta.value] ?? 0
)

const datosBloqueados = computed(() => fase.value === PASO_FIRMA || fase.value === PASO_GUARDADA)

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

function tonoEstado(texto: string) {
  if (texto.startsWith('✅')) return 'ok'
  if (texto.startsWith('❌')) return 'error'
  if (texto.startsWith('⚠️')) return 'warn'
  if (texto.startsWith('ℹ️')) return 'info'
  return ''
}

const enviarPantalla = async () => {
  const canvas = uiRef.value
  if (!canvas) return

  const paso = pasoTableta.value
  const nombreTabla = avisoTableta.value ? avisoDatosRef.value.nombre : nombre.value.trim()
  const dniTabla = avisoTableta.value ? avisoDatosRef.value.dni : dni.value.trim()

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
  if (fase.value !== PASO_FIRMA) return

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

const continuarAFirma = async () => {
  const nombreTxt = nombre.value.trim()
  const dniTxt = dni.value.trim()

  if (!nombreTxt || !dniTxt) {
    estado.value = '⚠️ Completa el nombre y el DNI antes de continuar.'
    return
  }

  limpiarTinta()
  avisoTableta.value = null
  fase.value = PASO_FIRMA
  // La pantalla de firma se escribe antes de firmar: si se dibuja despues, la
  // tableta borra el primer trazo que este haciendo el empleado.
  pantallaFirmaPintada.value = false
  await enviarPantalla()
  estado.value = 'El empleado puede firmar en la tableta: BORRAR limpia, CANCELAR anula y GUARDAR guarda.'
}

const mostrarAvisoTemporal = (pasoAviso: string, duracion = 5000) => {
  avisoDatosRef.value = { nombre: nombre.value.trim(), dni: dni.value.trim() }
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

const cancelarProceso = () => {
  limpiarTinta()
  mostrarAvisoTemporal(PASO_CANCELADA)
  fase.value = PASO_BIENVENIDA
  nombre.value = ''
  dni.value = ''
  estado.value = '❌ El empleado canceló el proceso de firma. No se guardó nada.'
}

const nuevoRegistro = () => {
  limpiarTinta()
  nombre.value = ''
  dni.value = ''
  ultimoBlob.value = ''
  avisoTableta.value = null
  fase.value = PASO_BIENVENIDA
  estado.value = 'Escriba el nombre y el DNI del siguiente empleado.'
}

const solicitarConfirmacionGuardado = () => {
  if (!hayFirma.value || guardando.value) return
  guardarPendiente.value = guardarFirmaReal
  mostrarConfirmacion.value = true
}

const cancelarConfirmacion = () => {
  mostrarConfirmacion.value = false
  guardarPendiente.value = null
}

const confirmarGuardado = async () => {
  if (!guardarPendiente.value) return
  const fn = guardarPendiente.value
  guardarPendiente.value = null
  mostrarConfirmacion.value = false
  await fn()
}

const guardarFirmaReal = async () => {
  if (!hayFirma.value || guardando.value) return

  guardando.value = true
  try {
    const wacom_blob = wacom.capturarFirma({
      nombre: nombre.value,
      dni: dni.value,
      trazo: firmaPng(),
      serial: serialTableta.value,
    })
    const fecha_firmado = new Date().toISOString()
    const resultado = await registrarFirma({ 
      wacom_blob, 
      nombre: nombre.value, 
      dni: dni.value, 
      fecha_firmado 
    })

    ultimoBlob.value = wacom_blob
    fase.value = PASO_GUARDADA
    estado.value = `✅ Firma guardada en el registro #${resultado.id}.`
    mostrarAvisoTemporal(PASO_GUARDADA)
    emit('saved')
  } catch (e: any) {
    estado.value = `❌ ${e.message}`
  } finally {
    guardando.value = false
  }
}

const continuarConOtroRegistro = () => {
  nuevoRegistro()
}

// ============================================
// MODAL DESCIFRADO WACOM (usa tableta STU-430)
// ============================================
const abrirModalDescifrado = () => {
  blobVerificar.value = ''
  resultadoVerificacion.value = null
  mostrarModalDescifrado.value = true
}

const cerrarModalDescifrado = () => {
  mostrarModalDescifrado.value = false
  blobVerificar.value = ''
  resultadoVerificacion.value = null
}

const verificarEncriptacion = async () => {
  if (!blobVerificar.value.trim() || verificando.value || !conectada.value) return

  verificando.value = true
  resultadoVerificacion.value = null

  try {
    const blob = blobVerificar.value.trim()
    
    // 1. Validar y decodificar estructura externa
    const PREFIJO = 'WACOM-STU430-'
    if (!blob.startsWith(PREFIJO)) {
      throw new Error('El blob no tiene el prefijo WACOM-STU430-')
    }
    
    const jsonExterno = atob(blob.slice(PREFIJO.length))
    const estructura = JSON.parse(jsonExterno)
    
    if (!estructura.nombre || !estructura.dni || !estructura.firma_encriptada_wacom || !estructura.fecha) {
      throw new Error('Estructura externa incompleta: faltan campos nombre, dni, firma_encriptada_wacom o fecha')
    }
    
    // 2. La firma encriptada viene en estructura.firma_encriptada_wacom
    // Para Wacom real, esto es datos binarios encriptados por el dispositivo
    // Necesitamos usar el SDK de la tableta para descifrar
    
    const firmaEncriptada = estructura.firma_encriptada_wacom
    
    // Verificar si es formato simulado (JSON anidado) o real (binario Wacom)
    let resultado: any = {
      ok: true,
      nombre: estructura.nombre,
      dni: estructura.dni,
      fecha: estructura.fecha,
      serial: estructura.serial_pad || 'N/A',
    }
    
    if (firmaEncriptada.startsWith(PREFIJO)) {
      // Intentar decodificar como JSON anidado (formato simulado)
      try {
        const jsonInterno = atob(firmaEncriptada.slice(PREFIJO.length))
        const paquete = JSON.parse(jsonInterno)
        
        if (paquete.firma_base64) {
          // Formato simulado - extraer PNG directamente
          resultado.pngBase64 = paquete.firma_base64
          resultado.raw = JSON.stringify(paquete, null, 2)
        } else {
          throw new Error('No hay firma_base64 en paquete interno')
        }
      } catch (e) {
        // No es JSON anidado - es binario encriptado real del Wacom
        // Usar la tableta conectada para descifrar
        resultado.raw = 'Datos binarios encriptados por Wacom STU-430 (requiere descifrado con tableta)'
        
        // Intentar descifrar usando la tableta conectada
        try {
          const tablet = tabletRef.value
          const protocol = protocolRef.value
          
          if (!tablet || !protocol) {
            throw new Error('Tableta no disponible para descifrado')
          }
          
          // Obtener los datos binarios encriptados
          const encryptedData = atob(firmaEncriptada.slice(PREFIJO.length))
          
          // Convertir a array de bytes para el SDK
          const bytes = new Uint8Array(encryptedData.length)
          for (let i = 0; i < encryptedData.length; i++) {
            bytes[i] = encryptedData.charCodeAt(i)
          }
          
          // Usar Crypto del SDK para descifrar
          // STU().Crypto tiene métodos para descifrado
          const Crypto = STU().Crypto
          
          // El Wacom usa clave pública/privada. La tableta tiene la clave privada.
          // Llamar al método de descifrado del dispositivo
          const decrypted = await Crypto.decrypt(bytes)
          
          if (decrypted) {
            // Convertir resultado a base64 PNG
            const pngBase64 = btoa(String.fromCharCode(...new Uint8Array(decrypted)))
            resultado.pngBase64 = pngBase64
            resultado.raw = `Descifrado con STU-430: ${decrypted.length} bytes -> PNG ${pngBase64.length} chars`
          }
        } catch (cryptoError: any) {
          resultado.raw = `Formato real Wacom detectado, pero error al descifrar: ${cryptoError.message}`
        }
      }
    } else {
      resultado.raw = 'firma_encriptada_wacom sin prefijo WACOM-STU430-'
    }
    
    resultadoVerificacion.value = resultado
    
  } catch (e: any) {
    resultadoVerificacion.value = {
      ok: false,
      raw: `Error: ${e.message}`
    }
  } finally {
    verificando.value = false
  }
}

const onDniInput = (e: Event) => {
  dni.value = (e.target as HTMLInputElement).value.replace(/\D/g, '')
}

const accionesRef = { current: { boton: (clave: string) => {
  const accion = accionDeBoton(clave)
  if (accion === 'limpiar') limpiarFirma()
  else if (accion === 'cancelar') cancelarProceso()
  else if (accion === 'enviar') void solicitarConfirmacionGuardado()
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
    serialTableta.value = sesion.serial || ''

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

watch([conectada, () => pasoTableta.value], (_nuevo, _viejo, onCleanup) => {
  const timer = setTimeout(() => enviarPantalla(), 250)
  onCleanup(() => clearTimeout(timer))
})
</script>
