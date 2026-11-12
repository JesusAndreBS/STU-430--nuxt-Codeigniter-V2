<template>
  <section class="panel">
    <div class="panel-head">
      <div class="titulo-panel">
        <span class="icono-panel" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M16 20v-1.5a3.5 3.5 0 0 0-3.5-3.5h-5A3.5 3.5 0 0 0 4 18.5V20" />
            <circle cx="10" cy="8" r="3.5" />
            <path d="M20 20v-1.5a3.5 3.5 0 0 0-2.6-3.4M15.5 4.6a3.5 3.5 0 0 1 0 6.8" />
          </svg>
        </span>
        <div>
          <h2>Registro de empleados</h2>
          <p class="subtitulo">Gestiona firmas y datos del personal</p>
        </div>
      </div>
      <span :class="['badge', pendientes > 0 ? 'warn' : 'ok']">
        <span class="punto" aria-hidden="true"></span>
        {{ pendientes > 0 ? `${pendientes} pendiente(s) de firma` : 'Todos firmados' }}
      </span>
    </div>

    <div class="buscador-wrap">
      <svg class="buscador-icon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
        <circle cx="11" cy="11" r="8" />
        <path d="M21 21l-4.35-4.35" />
      </svg>
      <input
        type="search"
        class="buscador"
        placeholder="Buscar por nombre o DNI..."
        :value="q"
        @input="onChangeQ($event.target.value)"
      />
    </div>

    <p v-if="cargando" class="estado info">Cargando empleados...</p>
    <p v-if="error" class="estado error">❌ {{ error }}</p>

    <div v-if="!cargando && !error && empleados.length === 0" class="empty-state">
      <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
        <circle cx="12" cy="7" r="4" />
      </svg>
      <p>{{ q ? 'No hay coincidencias en la búsqueda.' : 'Todavía no hay empleados registrados.' }}</p>
    </div>

    <div v-if="empleados.length > 0">
      <p class="estado info">
        {{ empleados.length }} registro(s). <strong>Haz clic</strong> para ver detalle, <strong>editar</strong> o actualizar firma.
      </p>

      <div class="tabla-wrap">
        <table class="tabla">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nombre</th>
              <th>DNI</th>
              <th>Estado</th>
              <th>Registrado</th>
              <th>Última firma</th>
              <th class="acciones-col">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="empleado in empleados"
              :key="empleado.id"
              :class="{ pendiente: !empleado.tiene_firma }"
              @click="abrir(empleado)"
            >
              <td>{{ empleado.id }}</td>
              <td class="nombre-col">{{ empleado.nombre || 'Sin nombre' }}</td>
              <td>{{ empleado.dni || '—' }}</td>
              <td>
                <span v-if="empleado.tiene_firma" class="badge ok"><span class="punto"></span>Firmado</span>
                <span v-else class="badge warn"><span class="punto"></span>Pendiente</span>
              </td>
              <td>{{ fecha(empleado.created_at) }}</td>
              <td>{{ empleado.firmado_at ? fecha(empleado.firmado_at) : '—' }}</td>
              <td class="acciones-col">
                <div class="acciones-fila">
                  <button
                    class="btn-icon"
                    @click.stop="iniciarEdicion(empleado)"
                    title="Editar datos"
                    aria-label="Editar datos"
                  >
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                    </svg>
                  </button>
                  <button
                    class="btn-icon primary"
                    @click.stop="$emit('seleccionar', empleado)"
                    :title="empleado.tiene_firma ? 'Actualizar firma' : 'Agregar firma'"
                    :aria-label="empleado.tiene_firma ? 'Actualizar firma' : 'Agregar firma'"
                  >
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M3 17.5L16.2 4.3a2 2 0 0 1 2.8 0l1.7 1.7a2 2 0 0 1 0 2.8L9.5 22H3z" />
                      <path d="M14 6.5 17.5 10" />
                    </svg>
                  </button>
                  <button
                    v-if="empleado.tiene_firma"
                    class="btn-icon"
                    @click.stop="descargarSoloFirma(empleado)"
                    title="Descargar firma (solo trazo)"
                    aria-label="Descargar firma"
                  >
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                      <polyline points="7 10 12 15 17 10" />
                      <line x1="12" y1="15" x2="12" y2="3" />
                    </svg>
                  </button>
                  <button
                    class="btn-icon danger"
                    @click.stop="borrar(empleado)"
                    title="Eliminar"
                    aria-label="Eliminar"
                  >
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="3 6 5 6 21 6" />
                      <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="detalle" class="modal-fondo" @click="setDetalle(null)">
      <div class="modal" @click.stop>
        <div class="modal-head">
          <h3>{{ detalle.nombre || 'Sin nombre' }}</h3>
          <span v-if="detalle.tiene_firma" class="badge ok"><span class="punto"></span>Firmado</span>
          <span v-else class="badge warn"><span class="punto"></span>Pendiente de firma</span>
        </div>

        <p class="modal-datos">
          DNI: <strong>{{ detalle.dni || '—' }}</strong> · Registrado: {{ fecha(detalle.created_at) }}
          <span v-if="detalle.firmado_at"> · Firmado: {{ fecha(detalle.firmado_at) }}</span>
        </p>

        <div v-if="detalle.firma_base64" class="firma-img">
          <img :src="`data:image/png;base64,${detalle.firma_base64}`" :alt="`Firma de ${detalle.nombre}`" />
        </div>
        <p v-else class="estado error">Este empleado todavía no ha ingresado su firma.</p>

        <div class="modal-acciones">
          <button
            class="btn primary"
            @click="setDetalle(null); $emit('seleccionar', detalle)"
            type="button"
          >
            {{ detalle.tiene_firma ? 'Actualizar firma' : 'Agregar firma' }}
          </button>
          <button v-if="detalle.firma_base64" class="btn" @click="descargarSoloFirma(detalle)" type="button">
            Descargar firma
          </button>
          <button class="btn danger" @click="borrar(detalle)" type="button">
            Eliminar
          </button>
          <button class="btn ghost" @click="setDetalle(null)" type="button">
            Cerrar
          </button>
        </div>
      </div>
    </div>

    <div v-if="editando" class="modal-fondo" @click="setEditando(null); error = ''">
      <div class="modal modal-edit" @click.stop>
        <div class="modal-head">
          <h3>Editar empleado</h3>
        </div>

        <p v-if="error" class="estado error">❌ {{ error }}</p>

        <div class="campos">
          <label class="campo">
            <span>Nombre completo</span>
            <input
              type="text"
              placeholder="Nombres y apellidos"
              v-model="editData.nombre"
              maxlength="120"
              autofocus
            />
          </label>
          <label class="campo">
            <span>DNI</span>
            <input
              type="text"
              placeholder="Documento de identidad"
              v-model="editData.dni"
              maxlength="12"
              inputmode="numeric"
            />
          </label>
        </div>

        <div class="modal-acciones">
          <button class="btn primary" @click="guardarEdicion" :disabled="guardando" type="button">
            {{ guardando ? 'Guardando...' : 'Guardar cambios' }}
          </button>
          <button class="btn" @click="actualizarFirma" type="button">
            Actualizar firma
          </button>
          <button class="btn ghost" @click="setEditando(null); error = ''" type="button">
            Cancelar
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, computed, watch, watchEffect } from 'vue'
import { listarEmpleados, obtenerEmpleado, actualizarEmpleado, eliminarEmpleado } from '~/composables/api'

const emit = defineEmits<{
  (e: 'seleccionar', empleado: any): void
  (e: 'change-q', q: string): void
}>()

const props = defineProps<{
  recargar: number
  q: string
}>()

const empleados = ref<any[]>([])
const cargando = ref(true)
const error = ref('')
const detalle = ref<any>(null)
const editando = ref<any>(null)
const editData = ref({ nombre: '', dni: '' })
const guardando = ref(false)

let peticionActual = 0

const cargar = async (termino: string) => {
  const peticion = ++peticionActual

  try {
    const data = await listarEmpleados(termino)
    if (peticion !== peticionActual) return
    empleados.value = data
    error.value = ''
  } catch (e: any) {
    if (peticion !== peticionActual) return
    error.value = e.message
  } finally {
    if (peticion === peticionActual) cargando.value = false
  }
}

const terminoBusqueda = computed(() => {
  const t = (props.q || '').trim()
  const soloDigitos = t.replace(/\D/g, '')
  if (soloDigitos.length >= 6 && soloDigitos.length <= 12 && /^\d+$/.test(soloDigitos)) {
    return soloDigitos
  }
  return t
})

watchEffect((onCleanup) => {
  const timer = setTimeout(() => cargar(terminoBusqueda.value), 300)
  onCleanup(() => clearTimeout(timer))
})

watch(() => props.recargar, () => cargar(props.q))

const pendientes = computed(() => empleados.value.filter((e: any) => !e.tiene_firma).length)

const fecha = (valor: string) => {
  if (!valor) return '—'
  return new Date(valor.replace(' ', 'T')).toLocaleString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const abrir = async (empleado: any) => {
  try {
    detalle.value = await obtenerEmpleado(empleado.id)
  } catch (e: any) {
    error.value = e.message
  }
}

const iniciarEdicion = (empleado: any) => {
  editando.value = empleado
  editData.value = { nombre: empleado.nombre || '', dni: empleado.dni || '' }
  detalle.value = null
}

const guardarEdicion = async () => {
  if (!editando.value || guardando.value) return
  const { nombre, dni } = editData.value
  if (!nombre.trim() || !dni.trim()) return

  guardando.value = true
  try {
    await actualizarEmpleado(editando.value.id, { nombre: nombre.trim(), dni: dni.trim() })
    editando.value = null
    error.value = ''
    cargar(props.q)
  } catch (e: any) {
    error.value = e.message
  } finally {
    guardando.value = false
  }
}

const actualizarFirma = () => {
  if (!editando.value || guardando.value) return
  const empleadoActualizado = { ...editando.value, ...editData.value }
  editando.value = null
  emit('seleccionar', empleadoActualizado)
}

const borrar = async (empleado: any) => {
  if (!window.confirm(`¿Eliminar el registro de ${empleado.nombre}?`)) return
  try {
    await eliminarEmpleado(empleado.id)
    detalle.value = null
    editando.value = null
    cargar(props.q)
  } catch (e: any) {
    error.value = e.message
  }
}

const descargarSoloFirma = async (registro: any) => {
  try {
    // El listado no transporta el PNG: se pide el detalle del empleado.
    const datos = registro.firma_base64 ? registro : await obtenerEmpleado(registro.id)
    if (!datos?.firma_base64) return

    const limpio = String(datos.firma_base64).replace(/^data:image\/png;base64,/, '').replace(/\s/g, '')
    const binario = atob(limpio)
    const bytes = Uint8Array.from(binario, (caracter) => caracter.charCodeAt(0))
    const url = URL.createObjectURL(new Blob([bytes], { type: 'image/png' }))
    const enlace = document.createElement('a')
    const nombre = (registro.nombre || 'empleado').replace(/[^\w\s.-]/g, '').trim()
    enlace.href = url
    enlace.download = `firma_${registro.id}_${nombre}.png`
    document.body.appendChild(enlace)
    enlace.click()
    enlace.remove()
    setTimeout(() => URL.revokeObjectURL(url), 1000)
  } catch (e: any) {
    error.value = e.message
  }
}

const onChangeQ = (value: string) => {
  emit('change-q', value)
}

const setDetalle = (value: any) => {
  detalle.value = value
}

const setEditando = (value: any) => {
  editando.value = value
}
</script>