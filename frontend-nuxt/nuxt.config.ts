// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: false },

  // Host fijo: evita que Nuxt guarde un hostname no-string en su cache
  // dev-server.json y reviente con "options.hostname.toLowerCase is not a function".
  devServer: {
    host: 'localhost',
    port: 3000
  },

  runtimeConfig: {
    public: {
      // URL base de la API REST. En desarrollo se puede usar '/api' porque
      // Nitro hace de proxy hacia el backend (ver nitro.devProxy).
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8090/api'
    }
  },

  app: {
    head: {
      title: 'Firmas STU-430',
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1' },
        { name: 'description', content: 'Sistema de captura de firmas biométricas Wacom STU-430' }
      ],
      link: [
        { rel: 'icon', type: 'image/x-icon', href: '/favicon.ico' }
      ]
    }
  },

  nitro: {
    devProxy: {
      // El prefijo '/api' se quita de la URL y se reemplaza por la ruta del
      // destino: /api/empleados -> http://localhost:8090/api/empleados
      '/api': {
        target: 'http://localhost:8090/api',
        changeOrigin: true
      }
    }
  },

  components: [
    { path: '~/components', pathPrefix: false }
  ],

  typescript: {
    strict: true,
    typeCheck: false
  }
})
