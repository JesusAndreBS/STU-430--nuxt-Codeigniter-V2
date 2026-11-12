<template>
  <div class="app">
    <header class="topbar">
      <div class="brand">
        <span class="brand-mark" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="18" height="18" rx="4" />
            <path d="M7 16.5 15.5 8a1.8 1.8 0 0 1 2.5 2.5L9.5 19H7z" />
          </svg>
        </span>
        <div>
          <h1>Firmas STU-430</h1>
          <p>Registro de empleados y captura de firmas con la tableta Wacom</p>
        </div>
      </div>
      <span class="topbar-tag">Panel del operador</span>
    </header>

    <main class="layout">
      <Captura
        :empleado="empleado"
        @saved="recargar++"
        @cancelar="empleado = null"
        @buscar-dni="buscarDNI = $event"
      />
      <RegistroEmpleados
        :recargar="recargar"
        @seleccionar="empleado = $event"
        :q="buscarDNI"
        @change-q="buscarDNI = $event"
      />
    </main>

    <footer class="footer">
      CodeIgniter 4 + MySQL · Nuxt 4
      <a
        href="/documentacion/index.html"
        target="_blank"
        rel="noopener noreferrer"
        style="color: var(--brand-2); font-weight: 600; text-decoration: underline;"
      >
        Documentación
      </a>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import Captura from './components/Captura.vue'
import RegistroEmpleados from './components/RegistroEmpleados.vue'
import '@/assets/css/main.css'

const recargar = ref(0)
const empleado = ref<any>(null)
const buscarDNI = ref('')
</script>