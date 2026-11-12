/* ============================================================
   STU-430 Documentation - Data & Logic
   ============================================================ */

const SECTIONS = [
  {
    id: 'overview',
    title: 'Visión General',
    icon: '📋',
    content: `
      <p>Sistema para registrar empleados y capturar su firma con una tableta <strong>Wacom STU-430</strong>. La identidad y la firma se guardan cifradas; la base de datos solo conserva hashes para búsquedas.</p>

      <h3>Características</h3>
      <ul>
        <li><strong>Captura biométrica:</strong> trazo de la tableta en PNG.</li>
        <li><strong>Flujo en dos fases:</strong> datos → firma → verificación del operador.</li>
        <li><strong>Cifrado:</strong> AES-256-GCM para identidad y firma; HMAC-SHA256 para búsquedas.</li>
        <li><strong>Estados:</strong> Firmado / Pendiente de firma.</li>
        <li><strong>Recuperación:</strong> comando CLI que extrae el PNG desde el contenedor cifrado.</li>
        <li><strong>Reconexión automática:</strong> detecta la tableta ocupada y reintenta.</li>
      </ul>

      <h3>Stack</h3>
      <div class="tech-grid">
        <div class="tech-item"><span class="badge frontend">Frontend</span> Nuxt 4 + Vue 3 + TypeScript</div>
        <div class="tech-item"><span class="badge backend">Backend</span> CodeIgniter 4.7 + PHP 8.3</div>
        <div class="tech-item"><span class="badge db">Base de datos</span> MySQL 8</div>
        <div class="tech-item"><span class="badge tablet">Tablet</span> Wacom STU-430 + SigCaptX</div>
        <div class="tech-item"><span class="badge crypto">Crypto</span> AES-256-GCM + HMAC-SHA256</div>
      </div>

      <div class="callout callout-tip">
        <div class="callout-content">
          <div class="callout-title">Arquitectura</div>
          El frontend corre en el PC con la tableta (puerto 3000) y el backend en Docker (puerto 8090).
          El proxy de Nuxt reenvía <code>/api</code> al backend.
        </div>
      </div>
    `
  },
  {
    id: 'instalacion',
    title: 'Instalación',
    icon: '📦',
    content: `
      <div class="callout callout-danger">
        <div class="callout-content">
          <div class="callout-title">Dependencias obligatorias de la tableta</div>
          Sin <strong>SignatureSDK</strong> y <strong>SigCaptX</strong> la STU-430 no funciona.
          Los instaladores están en <code>Dependencias-install/</code>.
        </div>
      </div>

      <h3>1. Tableta (Windows, como administrador)</h3>
      <ol>
        <li>Desconecta la tableta del USB.</li>
        <li>Instala <code>Dependencias-install/SignatureSDK/Wacom-Signature-SDK-x64-4.10.7.msi</code>.</li>
        <li>Instala <code>Dependencias-install/SigCaptX/Wacom-SigCaptX-1.27.3.exe</code> con el servicio de Windows.</li>
        <li>Conecta la tableta.</li>
        <li>Comprueba en <code>services.msc</code> que <code>WacomSTUSigCaptX</code> esté en ejecución.</li>
      </ol>

      <h3>2. Backend y base de datos</h3>
      <pre class="diagram" data-lang="bash"><code>cp backend/.env.example backend/.env
openssl rand -base64 32   # FIRMA_AES_KEY
openssl rand -hex 32      # FIRMA_HASH_SALT
docker compose up -d --build
docker compose logs -f backend</code></pre>

      <h3>3. Frontend</h3>
      <pre class="diagram" data-lang="bash"><code>cd frontend-nuxt
npm install
npm run dev</code></pre>

      <table class="doc-table">
        <thead><tr><th>Servicio</th><th>URL</th></tr></thead>
        <tbody>
          <tr><td>Frontend</td><td><code>http://localhost:3000</code></td></tr>
          <tr><td>API</td><td><code>http://localhost:8090/api</code></td></tr>
          <tr><td>phpMyAdmin</td><td><code>http://127.0.0.1:8081</code></td></tr>
          <tr><td>MySQL</td><td><code>127.0.0.1:3307</code></td></tr>
        </tbody>
      </table>
    `
  },
  {
    id: 'architecture',
    title: 'Arquitectura',
    icon: '🏗️',
    content: `
      <h3>Componentes</h3>
      <ul>
        <li><strong>Nuxt:</strong> captura Wacom, formulario, listado y descarga de firmas.</li>
        <li><strong>API CodeIgniter:</strong> validación, cifrado, hashes y acceso a MySQL.</li>
        <li><strong>MySQL:</strong> identidad cifrada y firma cifrada, relacionadas por <code>empleado_id</code>.</li>
        <li><strong>SigCaptX:</strong> servicio local que expone la tableta al navegador.</li>
      </ul>

      <h3>Flujo de guardado</h3>
      <ol>
        <li>El operador introduce nombre y DNI.</li>
        <li>El empleado firma en la tableta.</li>
        <li>El frontend envía el PNG en base64 junto con la identidad.</li>
        <li>El backend valida, cifra la identidad y la firma, y guarda hashes para búsquedas.</li>
        <li>La firma anterior se reemplaza en una sola transacción.</li>
      </ol>

      <pre class="diagram" data-lang="text"><code>Nuxt (3000)
   │  HTTP JSON
   ▼
API CodeIgniter (8090)
   │  AES-256-GCM
   ▼
MySQL: empleados 1 ─── 1 firmas_digitales</code></pre>
    `
  },
  {
    id: 'database',
    title: 'Base de Datos',
    icon: '🗄️',
    content: `
      <h3>empleados</h3>
      <table class="doc-table">
        <thead><tr><th>Columna</th><th>Tipo</th><th>Uso</th></tr></thead>
        <tbody>
          <tr><td><code>id</code></td><td>INT UNSIGNED</td><td>Clave primaria</td></tr>
          <tr><td><code>dni_hash</code></td><td>CHAR(64) único</td><td>HMAC-SHA256 del DNI</td></tr>
          <tr><td><code>nombre_dni_hash</code></td><td>CHAR(64) único</td><td>HMAC-SHA256 de nombre + DNI</td></tr>
          <tr><td><code>identidad_cifrada</code></td><td>LONGTEXT NOT NULL</td><td>Identidad cifrada</td></tr>
          <tr><td><code>created_at</code>, <code>updated_at</code></td><td>DATETIME</td><td>Auditoría</td></tr>
        </tbody>
      </table>

      <h3>firmas_digitales</h3>
      <table class="doc-table">
        <thead><tr><th>Columna</th><th>Tipo</th><th>Uso</th></tr></thead>
        <tbody>
          <tr><td><code>id</code></td><td>INT UNSIGNED</td><td>Clave primaria</td></tr>
          <tr><td><code>empleado_id</code></td><td>INT UNSIGNED único</td><td>FK con borrado en cascada</td></tr>
          <tr><td><code>firma_hash</code></td><td>CHAR(64) único</td><td>SHA-256 de identidad + cifrado</td></tr>
          <tr><td><code>firma_cifrada</code></td><td>LONGTEXT</td><td>Contenedor AES-256-GCM</td></tr>
          <tr><td><code>created_at</code></td><td>DATETIME NOT NULL</td><td>Fecha de firma</td></tr>
        </tbody>
      </table>

      <p>No se almacenan nombre, DNI ni PNG en claro. El listado solo descifra la identidad; el PNG se descifra al abrir el detalle o al recuperarlo por CLI.</p>
    `
  },
  {
    id: 'api',
    title: 'API',
    icon: '🔌',
    content: `
      <p>Todas las rutas cuelgan de <code>/api</code> y devuelven JSON.</p>

      <table class="doc-table">
        <thead><tr><th>Método</th><th>Ruta</th><th>Descripción</th></tr></thead>
        <tbody>
          <tr><td>GET</td><td><code>/empleados?q=</code></td><td>Listado o búsqueda por nombre/DNI</td></tr>
          <tr><td>GET</td><td><code>/empleados/:id</code></td><td>Detalle con <code>firma_base64</code></td></tr>
          <tr><td>POST</td><td><code>/empleados</code></td><td>Crear empleado; firma opcional</td></tr>
          <tr><td>PUT</td><td><code>/empleados/:id</code></td><td>Actualizar identidad; descarta firma</td></tr>
          <tr><td>DELETE</td><td><code>/empleados/:id</code></td><td>Eliminar empleado y firma</td></tr>
          <tr><td>PUT</td><td><code>/empleados/:id/firma</code></td><td>Crear o reemplazar firma</td></tr>
          <tr><td>POST</td><td><code>/firmas/iniciar</code></td><td>Generar JSON de entrada</td></tr>
        </tbody>
      </table>

      <h3>Crear con firma</h3>
      <pre class="diagram" data-lang="bash"><code>curl -X POST http://localhost:8090/api/empleados \\
  -H "Content-Type: application/json" \\
  -d '{"nombre":"Ana Torres","dni":"12345678","firma_base64":"iVBORw0KGgo..."}'</code></pre>

      <p>La respuesta final incluye <code>fecha_firma</code>, <code>firma_digital</code> y <code>estado</code>. El listado no transporta el PNG.</p>
    `
  },
  {
    id: 'security',
    title: 'Seguridad',
    icon: '🔐',
    content: `
      <h3>Cifrado</h3>
      <table class="doc-table">
        <tbody>
          <tr><td>Identidad y firma</td><td>AES-256-GCM con IV y tag aleatorios</td></tr>
          <tr><td>Clave</td><td><code>FIRMA_AES_KEY</code> (32 bytes base64)</td></tr>
          <tr><td>Contexto GCM</td><td><code>stu430-firma-digital-v1</code></td></tr>
          <tr><td>Búsqueda por DNI</td><td>HMAC-SHA256 con <code>FIRMA_HASH_SALT</code></td></tr>
          <tr><td>Integridad de firma</td><td>SHA-256 sobre identidad + contenedor</td></tr>
        </tbody>
      </table>

      <div class="callout callout-warning">
        <div class="callout-content">
          <div class="callout-title">Rotación de claves</div>
          Cambiar <code>FIRMA_HASH_SALT</code> invalida las búsquedas hasta rehashear los DNIs.
          Cambiar <code>FIRMA_AES_KEY</code> impide descifrar registros anteriores.
        </div>
      </div>

      <h3>Operación</h3>
      <ul>
        <li><code>backend/.env</code> está fuera de Git y se monta como solo lectura.</li>
        <li>MySQL y phpMyAdmin escuchan solo en <code>127.0.0.1</code>.</li>
        <li>La API no tiene autenticación: no exponerla a Internet sin un proxy autenticado.</li>
        <li>Respaldar la base y conservar las claves fuera del repositorio.</li>
      </ul>
    `
  },
  {
    id: 'commands',
    title: 'Comandos',
    icon: '⌨️',
    content: `
      <h3>Recuperar una firma</h3>
      <pre class="diagram" data-lang="bash"><code>docker compose exec backend php spark obtener-firma \\
  --hash HASH_DE_LA_FIRMA --formato png --salida /tmp/firma.png

docker cp stu430_backend:/tmp/firma.png ./firma.png</code></pre>
      <p>El hash se toma de <code>firmas_digitales.firma_hash</code>.</p>

      <h3>Ver el paquete descifrado en JSON</h3>
      <pre class="diagram" data-lang="bash"><code>docker compose exec backend php spark obtener-firma \\
  --hash HASH_DE_LA_FIRMA --json

docker compose exec backend php spark obtener-firma \\
  --hash HASH_DE_LA_FIRMA --json --salida /tmp/firma.json
docker cp stu430_backend:/tmp/firma.json ./firma.json</code></pre>
      <p>Muestra <code>nombre</code>, <code>dni</code>, <code>fecha_firma</code>, <code>mime</code> y <code>firma_base64</code>. Ojo: el JSON queda en texto claro; para respaldos conserva el sobre cifrado.</p>

      <h3>Pruebas</h3>
      <pre class="diagram" data-lang="bash"><code>docker compose exec backend composer install
docker compose exec backend vendor/bin/phpunit</code></pre>

      <h3>Respaldo</h3>
      <pre class="diagram" data-lang="bash"><code>docker compose exec db mysqldump -ustu430 -pstu430pass stu430 > respaldo.sql</code></pre>

      <h3>Frontend</h3>
      <pre class="diagram" data-lang="bash"><code>cd frontend-nuxt
npm run dev
npm run build</code></pre>
    `
  },
  {
    id: 'troubleshooting',
    title: 'Problemas',
    icon: '🛠️',
    content: `
      <table class="doc-table">
        <thead><tr><th>Síntoma</th><th>Causa probable</th></tr></thead>
        <tbody>
          <tr><td>La tableta no conecta</td><td>SigCaptX detenido u ocupado por otra pestaña</td></tr>
          <tr><td>Error de clave AES</td><td><code>FIRMA_AES_KEY</code> no son 32 bytes base64</td></tr>
          <tr><td>No se encuentra un DNI</td><td>Se cambió <code>FIRMA_HASH_SALT</code> sin rehash</td></tr>
          <tr><td>Error de CORS</td><td>El frontend no corre en <code>localhost:3000</code> o <code>localhost:5173</code></td></tr>
          <tr><td>Migraciones fallan</td><td>Revisar <code>docker compose logs backend</code></td></tr>
        </tbody>
      </table>

      <h3>Verificaciones rápidas</h3>
      <pre class="diagram" data-lang="bash"><code>docker compose ps
curl http://localhost:8090/api/empleados
docker compose exec backend php spark migrate:status</code></pre>
    `
  }
]

const nav = document.getElementById('nav')
const content = document.getElementById('content')
const searchInput = document.getElementById('searchInput')
const mobileMenuBtn = document.getElementById('mobileMenuBtn')
const sidebar = document.querySelector('.doc-sidebar')
const sidebarOverlay = document.getElementById('sidebarOverlay')
const readingProgress = document.getElementById('readingProgress')
const lightbox = document.getElementById('lightbox')
const lightboxImg = document.getElementById('lightboxImg')
const lightboxCaption = document.getElementById('lightboxCaption')
const lightboxClose = document.querySelector('.lightbox-close')

let activeSection = 'overview'
let lastScrollY = 0
let ticking = false

function renderNav() {
  const query = searchInput.value.toLowerCase()
  const filtered = SECTIONS.filter(s =>
    s.title.toLowerCase().includes(query) ||
    s.content.toLowerCase().includes(query)
  )

  nav.innerHTML = filtered.map(section => `
    <button class="nav-item ${activeSection === section.id ? 'active' : ''}"
            data-section="${section.id}"
            aria-current="${activeSection === section.id ? 'true' : 'false'}">
      <span class="nav-icon">${section.icon}</span>
      <span class="nav-title">${section.title}</span>
    </button>
  `).join('')
}

function renderContent() {
  const section = SECTIONS.find(s => s.id === activeSection)
  if (!section) return

  content.innerHTML = `
    <section class="doc-section active" id="${section.id}">
      <header class="section-header">
        <h2>${section.icon} ${section.title}</h2>
      </header>
      <div class="section-body">${section.content}</div>
    </section>
  `

  enhanceContent()
}

function enhanceContent() {
  // Copy buttons for code blocks
  document.querySelectorAll('pre:not([data-lang])').forEach(pre => {
    if (!pre.querySelector('.copy-code-btn')) {
      const lang = pre.querySelector('code')?.className?.replace('language-', '') || 'text'
      pre.setAttribute('data-lang', lang)
      const btn = document.createElement('button')
      btn.className = 'copy-code-btn'
      btn.textContent = 'Copiar'
      btn.addEventListener('click', () => copyCode(pre, btn))
      pre.appendChild(btn)
    }
  })

  // Lightbox for images
  document.querySelectorAll('.figure img:not([data-lightbox-init])').forEach(img => {
    img.dataset.lightboxInit = 'true'
    img.style.cursor = 'zoom-in'
    img.addEventListener('click', () => openLightbox(img))
  })

  // Tabs
  document.querySelectorAll('.tab-list').forEach(list => {
    list.addEventListener('click', e => {
      const btn = e.target.closest('.tab-btn')
      if (!btn) return
      const tabId = btn.dataset.tab
      list.querySelectorAll('.tab-btn').forEach(b => b.classList.toggle('active', b === btn))
      document.querySelectorAll('.tab-panel').forEach(p => p.classList.toggle('active', p.id === `tab-${tabId}`))
    })
  })

  // Screenshot placeholders
  document.querySelectorAll('.screenshot-placeholder:not([data-clickable])').forEach(ph => {
    ph.dataset.clickable = 'true'
    ph.style.cursor = 'pointer'
    ph.addEventListener('click', () => simulateLoad(ph))
  })
}

function simulateLoad(placeholder) {
  const src = placeholder.dataset.src
  if (!src) return
  const testImg = new Image()
  testImg.onload = () => {
    placeholder.src = src
    placeholder.removeAttribute('data-src')
    placeholder.classList.remove('screenshot-placeholder')
    placeholder.classList.add('loaded')
  }
  testImg.onerror = () => {
    placeholder.classList.add('loaded')
    placeholder.innerHTML = `<span>✅ Imagen simulada cargada</span><small>${placeholder.dataset.src}</small>`
  }
  testImg.src = src
}

function openLightbox(img) {
  lightboxImg.src = img.src
  lightboxImg.alt = img.alt
  lightboxCaption.textContent = img.closest('.figure')?.querySelector('figcaption')?.textContent || ''
  lightbox.classList.add('open')
  document.body.style.overflow = 'hidden'
}

function closeLightbox() {
  lightbox.classList.remove('open')
  document.body.style.overflow = ''
}

function copyCode(pre, btn) {
  const code = pre.querySelector('code').textContent
  navigator.clipboard.writeText(code).then(() => {
    btn.textContent = '¡Copiado!'
    btn.classList.add('copied')
    setTimeout(() => {
      btn.textContent = 'Copiar'
      btn.classList.remove('copied')
    }, 2000)
  })
}

// Navigation events
nav.addEventListener('click', e => {
  const item = e.target.closest('.nav-item')
  if (!item) return
  activeSection = item.dataset.section
  renderNav()
  renderContent()
  window.scrollTo(0, 0)
  if (window.innerWidth <= 1024) {
    sidebar.classList.remove('open')
    sidebarOverlay.classList.remove('visible')
  }
})

// Search
let searchTimeout
searchInput.addEventListener('input', () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(renderNav, 150)
})

// Mobile menu
mobileMenuBtn.addEventListener('click', () => {
  sidebar.classList.toggle('open')
  sidebarOverlay.classList.toggle('visible')
})
sidebarOverlay.addEventListener('click', () => {
  sidebar.classList.remove('open')
  sidebarOverlay.classList.remove('visible')
})

// Reading progress
function updateReadingProgress() {
  const docHeight = document.documentElement.scrollHeight - window.innerHeight
  const progress = window.scrollY / docHeight
  readingProgress.style.transform = `scaleX(${progress})`
}
window.addEventListener('scroll', () => {
  if (!ticking) {
    window.requestAnimationFrame(() => {
      updateReadingProgress()
      ticking = false
    })
    ticking = true
  }
}, { passive: true })

// Theme toggle
const themeToggle = document.getElementById('themeToggle')
if (themeToggle) {
  const isDark = document.documentElement.getAttribute('data-theme') === 'dark'
  document.documentElement.setAttribute('data-theme', isDark ? 'light' : 'dark')
  localStorage.setItem('theme', isDark ? 'light' : 'dark')
  themeToggle.textContent = isDark ? '☀️' : '🌙'
  themeToggle.title = isDark ? 'Modo claro' : 'Modo oscuro'
}

// Lightbox events
lightboxClose?.addEventListener('click', closeLightbox)
lightbox?.addEventListener('click', e => {
  if (e.target === lightbox) closeLightbox()
})
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') closeLightbox()
})

// Initialize
const saved = localStorage.getItem('theme')
const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
const theme = saved || (prefersDark ? 'dark' : 'light')
document.documentElement.setAttribute('data-theme', theme)

renderNav()
renderContent()

// Keyboard shortcut: Cmd/Ctrl + K for search
document.addEventListener('keydown', e => {
  if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
    e.preventDefault()
    searchInput.focus()
  }
})