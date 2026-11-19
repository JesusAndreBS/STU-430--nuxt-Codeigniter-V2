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
          <h2>Registro de firmas</h2>
          <p class="subtitulo">Historial de firmas capturadas con la Wacom</p>
        </div>
      </div>
      <span class="badge ok">
        <span class="punto" aria-hidden="true"></span>
        {{ firmas.length }} registro(s)
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
        placeholder="Buscar por nombre, DNI o ID..."
        v-model="q"
      />
    </div>

    <p v-if="cargando" class="estado info">Cargando firmas...</p>
    <p v-if="error" class="estado error">❌ {{ error }}</p>

    <div v-if="!cargando && !error && filtradas.length === 0" class="empty-state">
      <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
        <circle cx="12" cy="7" r="4" />
      </svg>
      <p>{{ q ? 'No hay coincidencias en la búsqueda.' : 'Todavía no hay firmas registradas.' }}</p>
    </div>

    <div v-if="filtradas.length > 0">
      <p class="estado info">
        {{ firmas.length }} registro(s). <strong>Haz clic</strong> para ver detalle o eliminar.
      </p>

      <div class="tabla-wrap">
        <table class="tabla">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nombre</th>
              <th>DNI</th>
              <th>Fecha de firma</th>
              <th>Fecha de creación</th>
              <th class="acciones-col">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="firma in filtradas" :key="firma.id" @click="abrir(firma)">
              <td>{{ firma.id }}</td>
              <td class="nombre-col">{{ firma.nombre || '—' }}</td>
              <td>{{ firma.dni || '—' }}</td>
              <td>{{ firma.fecha_firmado ? fecha(firma.fecha_firmado) : '—' }}</td>
              <td>{{ fecha(firma.fecha_creacion) }}</td>
              <td class="acciones-col">
                <div class="acciones-fila">
                  <button
                    class="btn-icon"
                    @click.stop="descargarPng(firma)"
                    title="Descargar firma (PNG)"
                    aria-label="Descargar firma PNG"
                  >
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                      <polyline points="7 10 12 15 17 10" />
                      <line x1="12" y1="15" x2="12" y2="3" />
                    </svg>
                  </button>
                  <button
                    class="btn-icon danger"
                    @click.stop="borrar(firma)"
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

    <div v-if="detalle" class="modal-fondo" @click="detalle = null">
      <div class="modal" @click.stop>
        <div class="modal-head">
          <h3>Firma #{{ detalle.id }}</h3>
          <span class="badge ok"><span class="punto"></span>Wacom</span>
        </div>

        <p class="modal-datos">
          Nombre: {{ detalle.nombre || '—' }}<br>
          DNI: {{ detalle.dni || '—' }}<br>
          Fecha de firma: {{ detalle.fecha_firmado ? fecha(detalle.fecha_firmado) : '—' }}<br>
          Registrada: {{ fecha(detalle.fecha_creacion) }}
        </p>

        <pre class="blob-json">{{ detalle.wacom_blob }}</pre>

        <div class="modal-acciones">
          <button class="btn" @click="descargarPng(detalle)" type="button">
            Descargar firma (PNG)
          </button>
          <button class="btn danger" @click="borrar(detalle)" type="button">
            Eliminar
          </button>
          <button class="btn ghost" @click="detalle = null" type="button">
            Cerrar
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { descargarPngFirma, eliminarFirma, listarFirmas, obtenerFirma } from '~/composables/api'

const props = defineProps<{
  recargar: number
}>()

const firmas = ref<any[]>([])
const cargando = ref(true)
const error = ref('')
const q = ref('')
const detalle = ref<any>(null)

const filtradas = computed(() => {
  const termino = q.value.trim().toLowerCase()
  if (!termino) return firmas.value
  return firmas.value.filter((firma) => 
    String(firma.id).includes(termino) ||
    (firma.nombre || '').toLowerCase().includes(termino) ||
    (firma.dni || '').includes(termino)
  )
})

const cargar = async () => {
  cargando.value = true

  try {
    firmas.value = await listarFirmas()
    error.value = ''
  } catch (e: any) {
    error.value = e.message
  } finally {
    cargando.value = false
  }
}

watch(() => props.recargar, cargar, { immediate: true })

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

const abrir = async (firma: any) => {
  try {
    detalle.value = await obtenerFirma(firma.id)
  } catch (e: any) {
    error.value = e.message
  }
}

const borrar = async (firma: any) => {
  if (!window.confirm(`¿Eliminar la firma #${firma.id}?`)) return

  try {
    await eliminarFirma(firma.id)
    detalle.value = null
    await cargar()
  } catch (e: any) {
    error.value = e.message
  }
}

const descargarPng = async (firma: any) => {
  try {
    const blob = await descargarPngFirma(firma.id)
    const url = URL.createObjectURL(blob)
    const enlace = document.createElement('a')
    enlace.href = url
    const nombre = (firma.nombre || 'firma').replace(/[^\w\s.-]/g, '').trim()
    enlace.download = `firma_${firma.id}_${nombre}.png`
    document.body.appendChild(enlace)
    enlace.click()
    enlace.remove()
    setTimeout(() => URL.revokeObjectURL(url), 1000)
  } catch (e: any) {
    console.error('Error al descargar PNG:', e)
    alert('Error al descargar la imagen: ' + e.message)
  }
}
</script>
